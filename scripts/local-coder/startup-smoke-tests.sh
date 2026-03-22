#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/../.." && pwd)"
# shellcheck source=scripts/local-coder/lib/github-auth.sh
. "${ROOT_DIR}/scripts/local-coder/lib/github-auth.sh"
# shellcheck source=scripts/local-coder/lib/workspace-secrets.sh
. "${ROOT_DIR}/scripts/local-coder/lib/workspace-secrets.sh"

SETTINGS_FILE="${ROOT_DIR}/.devcontainer/workspace-settings.env"
if [ -f "${SETTINGS_FILE}" ]; then
    # shellcheck disable=SC1090
    . "${SETTINGS_FILE}"
fi

if [ -f "${HOME}/.config/user-service/agent-secrets.env" ]; then
    # shellcheck disable=SC1091
    . "${HOME}/.config/user-service/agent-secrets.env"
fi
if [ -f "${HOME}/.config/openclaw/agent-secrets.env" ]; then
    # shellcheck disable=SC1091
    . "${HOME}/.config/openclaw/agent-secrets.env"
fi

cs_load_host_workspace_secrets

ORG="${1:-${WORKSPACE_GITHUB_ORG:-VilnaCRM-Org}}"
: "${CODEX_SMOKE_MODEL:=}"

cs_require_command gh
cs_require_command codex
cs_require_command bats

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

# If Codex already has stored credentials, prefer that session over API-key auth.
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

echo "Running Codex CLI smoke task..."
tmp_codex_output=""
tmp_codex_message=""
cleanup() {
    [ -n "${tmp_codex_output}" ] && rm -f "${tmp_codex_output}"
    [ -n "${tmp_codex_message}" ] && rm -f "${tmp_codex_message}"
}
trap cleanup EXIT

tmp_codex_output="$(mktemp)"
tmp_codex_message="$(mktemp)"
codex_cmd=(timeout 180s codex exec --json --output-last-message "${tmp_codex_message}" "Reply with exactly one line: codex-startup-ok")
if [ -n "${CODEX_SMOKE_MODEL}" ]; then
    codex_cmd+=(--model "${CODEX_SMOKE_MODEL}")
fi

if ! "${codex_cmd[@]}" >"${tmp_codex_output}" 2>&1; then
    echo "Error: Codex CLI smoke task failed." >&2
    sed -n '1,120p' "${tmp_codex_output}" >&2
    exit 1
fi

if ! grep -Fxq "codex-startup-ok" "${tmp_codex_message}"; then
    echo "Error: Codex CLI smoke task did not return expected output." >&2
    sed -n '1,120p' "${tmp_codex_output}" >&2
    exit 1
fi

echo "Codex startup smoke test passed."
echo "Startup smoke tests completed successfully."
