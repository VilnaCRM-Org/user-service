#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/../.." && pwd)"
SETTINGS_FILE="${ROOT_DIR}/.devcontainer/codespaces-settings.env"
# shellcheck source=scripts/codespaces/lib/github-auth.sh
. "${SCRIPT_DIR}/lib/github-auth.sh"

if [ -f "${SETTINGS_FILE}" ]; then
    # shellcheck disable=SC1090
    . "${SETTINGS_FILE}"
fi

if [ -f "${HOME}/.config/user-service/agent-secrets.env" ]; then
    # shellcheck disable=SC1091
    . "${HOME}/.config/user-service/agent-secrets.env"
fi

ORG="${1:-${CODESPACE_GITHUB_ORG:-VilnaCRM-Org}}"
: "${CLAUDE_MODEL:=MiniMax-M2.7}"
: "${CLAUDE_BASE_URL:=https://api.minimax.io/anthropic}"
: "${CLAUDE_PERMISSION_MODE:=bypassPermissions}"
: "${CLAUDE_TOOL_SMOKE_MODE:=enforce}"

cs_require_command gh
cs_require_command jq
cs_require_command claude
cs_require_command bats

echo "Checking GitHub authentication..."
cs_ensure_gh_auth

echo "Checking GitHub token scopes (if available)..."
scopes_headers="$(gh api -i /user 2>/dev/null || true)"
scopes="$({
    printf '%s' "${scopes_headers}" \
        | tr -d '\r' \
        | awk -F': ' 'tolower($1)=="x-oauth-scopes"{print $2; exit}'
} || true)"

if [ -n "${scopes}" ]; then
    echo "Available token scopes: ${scopes}"
    normalized_scopes="$(echo "${scopes}" | tr -d ' ')"
    for required_scope in repo read:org; do
        if [[ ",${normalized_scopes}," != *",${required_scope},"* ]]; then
            echo "Warning: expected scope '${required_scope}' is missing." >&2
        fi
    done
else
    echo "Note: scope header unavailable for this token."
fi

echo "Listing repositories in org '${ORG}'..."
repo_count="$(gh repo list "${ORG}" --limit 1 --json name --jq 'length' 2>/dev/null || true)"
if ! [[ "${repo_count:-}" =~ ^[0-9]+$ ]]; then
    repo_count=0
fi
if [ "${repo_count}" -le 0 ]; then
    echo "Error: failed to list repositories for org '${ORG}'." >&2
    exit 1
fi
echo "Repository listing ok (${repo_count} repositories visible)."

echo "Checking current PR CI status..."
pr_number="$(gh pr view --json number --jq '.number' 2>/dev/null || true)"
if [ -n "${pr_number}" ]; then
    checks_json="$(gh pr checks "${pr_number}" --json name,state 2>/dev/null)" || {
        cat >&2 <<EOM
Error: failed to query checks for PR #${pr_number}.
Ensure your authentication can read pull request checks/actions metadata for this repository.
EOM
        exit 1
    }
    non_success_count="$(printf '%s' "${checks_json}" | jq '[.[].state | select(. != "SUCCESS" and . != "SKIPPED" and . != "NEUTRAL")] | length')" || {
        cat >&2 <<EOM
Error: failed to parse PR checks JSON for PR #${pr_number}.
Received payload was not valid JSON.
EOM
        exit 1
    }
    echo "PR #${pr_number} checks query ok (non-success states: ${non_success_count})."
else
    echo "No PR detected for current branch. Skipping PR checks."
fi

echo "Checking git push permissions on current branch..."
current_branch="$(git symbolic-ref --quiet --short HEAD 2>/dev/null || true)"
if [ -z "${current_branch}" ]; then
    cat >&2 <<'EOM'
Error: current git checkout is in detached HEAD state.
Check out a branch before running push verification.
EOM
    exit 1
fi
if ! git push --dry-run origin "${current_branch}" >/dev/null 2>&1; then
    cat >&2 <<EOM
Error: git push dry-run failed for branch '${current_branch}'.
Ensure your token has write permissions for repository contents.
EOM
    exit 1
fi
echo "Git push dry-run ok for branch '${current_branch}'."

echo "Checking Bats availability..."
bats --version

if [ -z "${ANTHROPIC_AUTH_TOKEN:-}" ] && [ -n "${MINIMAX_API_KEY:-}" ]; then
    export ANTHROPIC_AUTH_TOKEN="${MINIMAX_API_KEY}"
fi
if [ -z "${MINIMAX_API_KEY:-}" ] && [ -n "${ANTHROPIC_AUTH_TOKEN:-}" ]; then
    export MINIMAX_API_KEY="${ANTHROPIC_AUTH_TOKEN}"
fi
if [ -z "${MINIMAX_API_KEY:-}" ]; then
    cat >&2 <<'EOM'
Error: MINIMAX_API_KEY is not set.
Provide MINIMAX_API_KEY as a Codespaces secret.
EOM
    exit 1
fi

CLAUDE_SETTINGS_JSON="${HOME}/.claude/settings.json"
if [ ! -f "${CLAUDE_SETTINGS_JSON}" ]; then
    echo "Error: Claude settings file is missing: ${CLAUDE_SETTINGS_JSON}" >&2
    exit 1
fi
configured_model="$(jq -r '.env.ANTHROPIC_MODEL // empty' "${CLAUDE_SETTINGS_JSON}")"
configured_base_url="$(jq -r '.env.ANTHROPIC_BASE_URL // empty' "${CLAUDE_SETTINGS_JSON}")"
configured_token="$(jq -r '.env.ANTHROPIC_AUTH_TOKEN // empty' "${CLAUDE_SETTINGS_JSON}")"
configured_permission_mode="$(jq -r '.permissions.defaultMode // empty' "${CLAUDE_SETTINGS_JSON}")"
if [ -z "${configured_token}" ]; then
    echo "Error: Claude settings are missing env.ANTHROPIC_AUTH_TOKEN." >&2
    exit 1
fi
if [ "${configured_model}" != "${CLAUDE_MODEL}" ]; then
    echo "Error: Claude model '${CLAUDE_MODEL}' is not configured in ${CLAUDE_SETTINGS_JSON}." >&2
    exit 1
fi
if [ "${configured_base_url}" != "${CLAUDE_BASE_URL}" ]; then
    echo "Error: Claude base URL '${CLAUDE_BASE_URL}' is not configured in ${CLAUDE_SETTINGS_JSON}." >&2
    exit 1
fi
if [ "${configured_permission_mode}" != "${CLAUDE_PERMISSION_MODE}" ]; then
    echo "Error: Claude permission mode '${CLAUDE_PERMISSION_MODE}' is not configured in ${CLAUDE_SETTINGS_JSON}." >&2
    exit 1
fi

tmp_claude_basic=""
tmp_claude_tools=""
tmp_tool_workspace=""
tmp_tool_marker_file=""
tool_marker=""
cleanup() {
    [ -n "${tmp_claude_basic}" ] && rm -f "${tmp_claude_basic}"
    [ -n "${tmp_claude_tools}" ] && rm -f "${tmp_claude_tools}"
    [ -n "${tmp_tool_marker_file}" ] && rm -f "${tmp_tool_marker_file}"
    [ -n "${tmp_tool_workspace}" ] && rm -rf "${tmp_tool_workspace}"
}
trap cleanup EXIT

tmp_claude_basic="$(mktemp)"
tmp_claude_tools="$(mktemp)"
tmp_tool_workspace="$(mktemp -d)"
tmp_tool_marker_file="${tmp_tool_workspace}/claude-tools-marker.txt"
if command -v uuidgen >/dev/null 2>&1; then
    tool_marker="$(uuidgen | tr '[:upper:]' '[:lower:]' | tr -d '-')"
else
    tool_marker="$(tr -d '-' < /proc/sys/kernel/random/uuid)"
fi

claude_args=(
    -p
    --model "${CLAUDE_MODEL}"
)

echo "Running Claude basic smoke task..."
if ! timeout 180s claude "${claude_args[@]}" "Reply with exactly one line: claude-ok:minimax-basic" >"${tmp_claude_basic}" 2>&1; then
    echo "Error: Claude basic smoke task failed." >&2
    sed -n '1,120p' "${tmp_claude_basic}" >&2
    exit 1
fi
if ! grep -q "claude-ok:minimax-basic" "${tmp_claude_basic}"; then
    echo "Error: Claude basic smoke task did not return expected output." >&2
    sed -n '1,120p' "${tmp_claude_basic}" >&2
    exit 1
fi
echo "Claude basic smoke task ok."

echo "Running Claude tool-calling smoke task..."
tool_smoke_failed=0
claude_tool_prompt="This is a harmless local smoke test in your own temporary workspace. Use bash exactly once and run: echo ${tool_marker} > ./claude-tools-marker.txt. Then reply with exactly one line: claude-ok:minimax-tools"
if ! (
    cd "${tmp_tool_workspace}" && timeout 240s claude "${claude_args[@]}" "${claude_tool_prompt}"
) >"${tmp_claude_tools}" 2>&1; then
    tool_smoke_failed=1
fi

if [ "${tool_smoke_failed}" -eq 1 ]; then
    if [ "${CLAUDE_TOOL_SMOKE_MODE}" = "skip" ]; then
        echo "Skipping Claude tool-calling smoke task failure (CLAUDE_TOOL_SMOKE_MODE=skip)." >&2
    else
        echo "Error: Claude tool-calling smoke task failed." >&2
        sed -n '1,160p' "${tmp_claude_tools}" >&2
        exit 1
    fi
else
    if ! grep -q "claude-ok:minimax-tools" "${tmp_claude_tools}"; then
        echo "Error: Claude tool-calling smoke task did not return expected output." >&2
        sed -n '1,160p' "${tmp_claude_tools}" >&2
        exit 1
    fi
    actual_marker="$(tr -d '\r\n' < "${tmp_tool_marker_file}" 2>/dev/null || true)"
    if [ "${actual_marker}" != "${tool_marker}" ]; then
        echo "Error: Claude tool-calling smoke task did not produce expected marker file content." >&2
        exit 1
    fi
    echo "Claude tool-calling smoke task ok."
fi

echo "All GH/Claude verification checks passed."
