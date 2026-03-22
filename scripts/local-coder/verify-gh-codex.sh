#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/../.." && pwd)"
# shellcheck source=scripts/local-coder/lib/github-auth.sh
. "${ROOT_DIR}/scripts/local-coder/lib/github-auth.sh"
# shellcheck source=scripts/local-coder/lib/workspace-secrets.sh
. "${ROOT_DIR}/scripts/local-coder/lib/workspace-secrets.sh"

for SETTINGS_FILE in \
    "${ROOT_DIR}/.devcontainer/workspace-settings.env" \
    "${ROOT_DIR}/.devcontainer/codespaces-settings.env"; do
    if [ -f "${SETTINGS_FILE}" ]; then
        # shellcheck disable=SC1090
        . "${SETTINGS_FILE}"
        break
    fi
done

if [ -f "${HOME}/.config/user-service/agent-secrets.env" ]; then
    # shellcheck disable=SC1091
    . "${HOME}/.config/user-service/agent-secrets.env"
fi
if [ -f "${HOME}/.config/openclaw/agent-secrets.env" ]; then
    # shellcheck disable=SC1091
    . "${HOME}/.config/openclaw/agent-secrets.env"
fi

cs_load_host_workspace_secrets

ORG="${1:-${WORKSPACE_GITHUB_ORG:-${CODESPACE_GITHUB_ORG:-VilnaCRM-Org}}}"
: "${CODEX_SMOKE_MODEL:=}"
: "${CODEX_TOOL_SMOKE_MODE:=enforce}"

cs_require_command gh
cs_require_command jq
cs_require_command codex
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

if codex login status >/dev/null 2>&1; then
    unset OPENAI_API_KEY
elif [ -z "${OPENAI_API_KEY:-}" ]; then
    cat >&2 <<'EOM'
Error: Codex authentication is not configured.
Provide OPENAI_API_KEY in your workspace secrets or run:
  codex login
EOM
    exit 1
fi

tmp_codex_basic_output=""
tmp_codex_basic_message=""
tmp_codex_tools_output=""
tmp_codex_tools_message=""
tmp_tool_workspace=""
tmp_tool_marker_file=""
tool_marker=""
cleanup() {
    [ -n "${tmp_codex_basic_output}" ] && rm -f "${tmp_codex_basic_output}"
    [ -n "${tmp_codex_basic_message}" ] && rm -f "${tmp_codex_basic_message}"
    [ -n "${tmp_codex_tools_output}" ] && rm -f "${tmp_codex_tools_output}"
    [ -n "${tmp_codex_tools_message}" ] && rm -f "${tmp_codex_tools_message}"
    [ -n "${tmp_tool_marker_file}" ] && rm -f "${tmp_tool_marker_file}"
    [ -n "${tmp_tool_workspace}" ] && rm -rf "${tmp_tool_workspace}"
}
trap cleanup EXIT

tmp_codex_basic_output="$(mktemp)"
tmp_codex_basic_message="$(mktemp)"
tmp_codex_tools_output="$(mktemp)"
tmp_codex_tools_message="$(mktemp)"
tmp_tool_workspace="$(mktemp -d)"
tmp_tool_marker_file="${tmp_tool_workspace}/codex-tools-marker.txt"
if command -v uuidgen >/dev/null 2>&1; then
    tool_marker="$(uuidgen | tr '[:upper:]' '[:lower:]' | tr -d '-')"
else
    tool_marker="$(tr -d '-' < /proc/sys/kernel/random/uuid)"
fi

echo "Running Codex basic smoke task..."
codex_basic_cmd=(timeout 180s codex exec --json --output-last-message "${tmp_codex_basic_message}" "Reply with exactly one line: codex-ok:openai-basic")
if [ -n "${CODEX_SMOKE_MODEL}" ]; then
    codex_basic_cmd+=(--model "${CODEX_SMOKE_MODEL}")
fi
if ! "${codex_basic_cmd[@]}" >"${tmp_codex_basic_output}" 2>&1; then
    echo "Error: Codex basic smoke task failed." >&2
    sed -n '1,120p' "${tmp_codex_basic_output}" >&2
    exit 1
fi
if ! grep -q "codex-ok:openai-basic" "${tmp_codex_basic_message}"; then
    echo "Error: Codex basic smoke task did not return expected output." >&2
    sed -n '1,120p' "${tmp_codex_basic_output}" >&2
    exit 1
fi
echo "Codex basic smoke task ok."

echo "Running Codex tool-calling smoke task..."
tool_smoke_failed=0
codex_tool_prompt="This is a harmless local smoke test in your own temporary workspace. Use bash exactly once and run: echo ${tool_marker} > ./codex-tools-marker.txt. Then reply with exactly one line: codex-ok:openai-tools"
codex_tool_cmd=(timeout 240s codex exec --json --output-last-message "${tmp_codex_tools_message}" --dangerously-bypass-approvals-and-sandbox --skip-git-repo-check -C "${tmp_tool_workspace}" "${codex_tool_prompt}")
if [ -n "${CODEX_SMOKE_MODEL}" ]; then
    codex_tool_cmd+=(--model "${CODEX_SMOKE_MODEL}")
fi
if ! "${codex_tool_cmd[@]}" >"${tmp_codex_tools_output}" 2>&1; then
    tool_smoke_failed=1
fi

if [ "${tool_smoke_failed}" -eq 1 ]; then
    if [ "${CODEX_TOOL_SMOKE_MODE}" = "skip" ]; then
        echo "Skipping Codex tool-calling smoke task failure (CODEX_TOOL_SMOKE_MODE=skip)." >&2
    else
        echo "Error: Codex tool-calling smoke task failed." >&2
        sed -n '1,160p' "${tmp_codex_tools_output}" >&2
        exit 1
    fi
else
    if ! grep -q "codex-ok:openai-tools" "${tmp_codex_tools_message}"; then
        echo "Error: Codex tool-calling smoke task did not return expected output." >&2
        sed -n '1,160p' "${tmp_codex_tools_output}" >&2
        exit 1
    fi
    actual_marker="$(tr -d '\r\n' < "${tmp_tool_marker_file}" 2>/dev/null || true)"
    if [ "${actual_marker}" != "${tool_marker}" ]; then
        echo "Error: Codex tool-calling smoke task did not produce expected marker file content." >&2
        exit 1
    fi
    echo "Codex tool-calling smoke task ok."
fi

echo "All GH/Codex verification checks passed."
