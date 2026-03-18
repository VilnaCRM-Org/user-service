#!/usr/bin/env bash
set -euo pipefail

ORG="${1:-${CODESPACE_GITHUB_ORG:-VilnaCRM-Org}}"
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

: "${CLAUDE_MODEL:=MiniMax-M2.7}"
: "${CLAUDE_BASE_URL:=https://api.minimax.io/anthropic}"
: "${CLAUDE_PERMISSION_MODE:=bypassPermissions}"

cs_require_command gh
cs_require_command claude
cs_require_command bats
cs_require_command jq

echo "Running startup smoke tests..."

echo "Checking Bats availability..."
bats --version

echo "Checking GitHub authentication..."
cs_ensure_gh_auth

echo "Checking repository listing for org '${ORG}'..."
repo_count="$(gh repo list "${ORG}" --limit 1 --json name --jq 'length' 2>/dev/null || true)"
if ! [[ "${repo_count:-}" =~ ^[0-9]+$ ]]; then
    repo_count=0
fi
if [ "${repo_count}" -lt 1 ]; then
    echo "Error: unable to list repositories for org '${ORG}'." >&2
    exit 1
fi
echo "GitHub CLI smoke test passed."

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
echo "Checking Claude profile configuration..."
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

echo "Running Claude smoke task with model '${CLAUDE_MODEL}'..."
tmp_claude_output=""
cleanup() {
    [ -n "${tmp_claude_output}" ] && rm -f "${tmp_claude_output}"
}
trap cleanup EXIT

tmp_claude_output="$(mktemp)"
claude_args=(
    -p
    --model "${CLAUDE_MODEL}"
)

if ! timeout 180s claude "${claude_args[@]}" "Reply with exactly one line: claude-startup-ok" >"${tmp_claude_output}" 2>&1; then
    echo "Error: Claude smoke task failed." >&2
    sed -n '1,120p' "${tmp_claude_output}" >&2
    exit 1
fi

if ! grep -q "claude-startup-ok" "${tmp_claude_output}"; then
    echo "Error: Claude smoke task did not return expected output." >&2
    sed -n '1,120p' "${tmp_claude_output}" >&2
    exit 1
fi

echo "Claude startup smoke test passed."
echo "Startup smoke tests completed successfully."
