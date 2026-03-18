#!/usr/bin/env bash
set -euo pipefail

# Secure bootstrap for autonomous agent tooling in GitHub Codespaces.
# This script only uses environment variables and does not write secrets to repository files.

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/../.." && pwd)"
SETTINGS_FILE="${ROOT_DIR}/.devcontainer/codespaces-settings.env"
# shellcheck source=scripts/codespaces/lib/github-auth.sh
. "${SCRIPT_DIR}/lib/github-auth.sh"

if [ -f "${SETTINGS_FILE}" ]; then
    # shellcheck disable=SC1090
    . "${SETTINGS_FILE}"
fi

readonly CLAUDE_SETTINGS_JSON="${HOME}/.claude/settings.json"
readonly AGENT_BASHRC_FILE="${HOME}/.bashrc"
readonly AGENT_BASHRC_START="# BEGIN USER-SERVICE AGENT ENV"
readonly AGENT_BASHRC_END="# END USER-SERVICE AGENT ENV"

: "${CLAUDE_MODEL:=MiniMax-M2.7}"
: "${CLAUDE_BASE_URL:=https://api.minimax.io/anthropic}"
# Safe-by-default Codespaces profile.
: "${CLAUDE_PERMISSION_MODE:=default}"
: "${CLAUDE_ALLOW_UNSAFE_MODE:=0}"

: "${GH_HOST:=github.com}"
: "${GH_GIT_PROTOCOL:=ssh}"
: "${GH_PROMPT:=disabled}"
: "${CODESPACE_GIT_IDENTITY_NAME:=vilnacrm ai bot}"
: "${CODESPACE_GIT_IDENTITY_EMAIL:=info@vilnacrm.com}"

TMP_FILES=()
CS_GIT_IDENTITY_NAME=""
CS_GIT_IDENTITY_EMAIL=""
DETECTED_GIT_IDENTITY_NAME=""
DETECTED_GIT_IDENTITY_EMAIL=""

cleanup_tmp_files() {
    local tmp_file
    for tmp_file in "${TMP_FILES[@]}"; do
        rm -f "${tmp_file}"
    done
}

track_tmp_file() {
    TMP_FILES+=("$1")
}

cs_warn_if_missing_command() {
    local command_name="$1"

    if ! command -v "${command_name}" >/dev/null 2>&1; then
        echo "Warning: optional command '${command_name}' is not installed." >&2
        return 1
    fi

    return 0
}

trap cleanup_tmp_files EXIT

ensure_shell_agent_exports() {
    local tmp_bashrc
    tmp_bashrc="$(mktemp)"
    track_tmp_file "${tmp_bashrc}"

    touch "${AGENT_BASHRC_FILE}"

    awk -v start="${AGENT_BASHRC_START}" -v end="${AGENT_BASHRC_END}" '
        $0 == start {skip=1; next}
        $0 == end {skip=0; next}
        skip == 0 {print}
    ' "${AGENT_BASHRC_FILE}" > "${tmp_bashrc}"

    cat >> "${tmp_bashrc}" <<'EOM'

# BEGIN USER-SERVICE AGENT ENV
export ANTHROPIC_BASE_URL="${ANTHROPIC_BASE_URL:-https://api.minimax.io/anthropic}"
export ANTHROPIC_MODEL="${ANTHROPIC_MODEL:-MiniMax-M2.7}"
if [ -z "${ANTHROPIC_AUTH_TOKEN:-}" ] && [ -n "${MINIMAX_API_KEY:-}" ]; then
    export ANTHROPIC_AUTH_TOKEN="${MINIMAX_API_KEY}"
fi
if [ -z "${GH_TOKEN:-}" ]; then
    if [ -n "${GH_AUTOMATION_TOKEN:-}" ]; then
        export GH_TOKEN="${GH_AUTOMATION_TOKEN}"
    elif [ -n "${GITHUB_TOKEN:-}" ]; then
        export GH_TOKEN="${GITHUB_TOKEN}"
    fi
fi
if [ -z "${GITHUB_TOKEN:-}" ] && [ -n "${GH_TOKEN:-}" ]; then
    export GITHUB_TOKEN="${GH_TOKEN}"
fi
# END USER-SERVICE AGENT ENV
EOM

    cat "${tmp_bashrc}" > "${AGENT_BASHRC_FILE}"
    rm -f "${tmp_bashrc}"
}

write_claude_settings() {
    local tmp_settings

    tmp_settings="$(mktemp)"
    track_tmp_file "${tmp_settings}"

    if command -v jq >/dev/null 2>&1 \
        && [ -f "${CLAUDE_SETTINGS_JSON}" ] \
        && jq -e '.' "${CLAUDE_SETTINGS_JSON}" >/dev/null 2>&1; then
        if jq \
            --arg base_url "${ANTHROPIC_BASE_URL}" \
            --arg model "${ANTHROPIC_MODEL}" \
            --arg permission_mode "${CLAUDE_PERMISSION_MODE}" \
            '.env = (.env // {})
            | del(.env.ANTHROPIC_AUTH_TOKEN)
            | .env.ANTHROPIC_BASE_URL = $base_url
            | .env.ANTHROPIC_MODEL = $model
            | .model = $model
            | .permissions = (.permissions // {})
            | .permissions.defaultMode = $permission_mode
            | .permissions.ask = (.permissions.ask // [])' \
            "${CLAUDE_SETTINGS_JSON}" > "${tmp_settings}"; then
            :
        else
            : > "${tmp_settings}"
        fi
    fi

    if [ ! -s "${tmp_settings}" ]; then
        jq -n \
            --arg base_url "${ANTHROPIC_BASE_URL}" \
            --arg model "${ANTHROPIC_MODEL}" \
            --arg permission_mode "${CLAUDE_PERMISSION_MODE}" \
            '{
                model: $model,
                permissions: {
                    defaultMode: $permission_mode,
                    ask: []
                },
                env: {
                    ANTHROPIC_BASE_URL: $base_url,
                    ANTHROPIC_MODEL: $model
                }
            }' > "${tmp_settings}"
    fi

    mkdir -p "$(dirname "${CLAUDE_SETTINGS_JSON}")"
    chmod 600 "${tmp_settings}"
    mv "${tmp_settings}" "${CLAUDE_SETTINGS_JSON}"
}

validate_claude_safety_settings() {
    if [ "${CLAUDE_PERMISSION_MODE}" = "bypassPermissions" ] \
        && [ "${CLAUDE_ALLOW_UNSAFE_MODE}" != "1" ]; then
        cat >&2 <<'EOM'
Error: refusing unsafe Claude defaults.
Detected:
  CLAUDE_PERMISSION_MODE=bypassPermissions

Set CLAUDE_ALLOW_UNSAFE_MODE=1 to confirm bypassPermissions, or override to safer settings.
EOM
        exit 1
    fi
}

configure_git_identity() {
    local name email configured_name configured_email var

    CS_GIT_IDENTITY_NAME=""
    CS_GIT_IDENTITY_EMAIL=""
    DETECTED_GIT_IDENTITY_NAME=""
    DETECTED_GIT_IDENTITY_EMAIL=""

    for var in GIT_AUTHOR_NAME GIT_AUTHOR_EMAIL GIT_COMMITTER_NAME GIT_COMMITTER_EMAIL; do
        if [ -z "${!var:-}" ]; then
            unset "${var}" || true
        fi
    done

    name="${GIT_AUTHOR_NAME:-${GIT_COMMITTER_NAME:-}}"
    email="${GIT_AUTHOR_EMAIL:-${GIT_COMMITTER_EMAIL:-}}"
    configured_name="$(git config --global --get user.name 2>/dev/null || true)"
    configured_email="$(git config --global --get user.email 2>/dev/null || true)"

    if [ -z "${name}" ]; then
        name="${configured_name}"
    fi
    if [ -z "${email}" ]; then
        email="${configured_email}"
    fi

    if [ -n "${name}" ] && [ -n "${email}" ]; then
        CS_GIT_IDENTITY_NAME="${name}"
        CS_GIT_IDENTITY_EMAIL="${email}"
        DETECTED_GIT_IDENTITY_NAME="${name}"
        DETECTED_GIT_IDENTITY_EMAIL="${email}"
        return 0
    fi

    name="${CODESPACE_GIT_IDENTITY_NAME}"
    email="${CODESPACE_GIT_IDENTITY_EMAIL}"

    git config --global user.name "${name}"
    git config --global user.email "${email}"

    export GIT_AUTHOR_NAME="${name}"
    export GIT_AUTHOR_EMAIL="${email}"
    export GIT_COMMITTER_NAME="${name}"
    export GIT_COMMITTER_EMAIL="${email}"

    CS_GIT_IDENTITY_NAME="${name}"
    CS_GIT_IDENTITY_EMAIL="${email}"
    DETECTED_GIT_IDENTITY_NAME="${name}"
    DETECTED_GIT_IDENTITY_EMAIL="${email}"
}

cs_require_command gh
cs_warn_if_missing_command claude || true
cs_ensure_gh_auth

if [ -z "${MINIMAX_API_KEY:-}" ] && [ -n "${ANTHROPIC_AUTH_TOKEN:-}" ]; then
    MINIMAX_API_KEY="${ANTHROPIC_AUTH_TOKEN}"
fi

if [ -z "${MINIMAX_API_KEY:-}" ]; then
    cat >&2 <<'EOM'
Error: MINIMAX_API_KEY is not set.
Provide MINIMAX_API_KEY as a Codespaces secret.
EOM
    exit 1
fi

export MINIMAX_API_KEY
export ANTHROPIC_AUTH_TOKEN="${MINIMAX_API_KEY}"
export ANTHROPIC_BASE_URL="${CLAUDE_BASE_URL}"
export ANTHROPIC_MODEL="${CLAUDE_MODEL}"

validate_claude_safety_settings
configure_git_identity
ensure_shell_agent_exports
write_claude_settings

gh config set git_protocol "${GH_GIT_PROTOCOL}" --host "${GH_HOST}" >/dev/null 2>&1 || true
gh config set prompt "${GH_PROMPT}" >/dev/null 2>&1 || true

echo "Secure agent environment is ready."
echo "GH auth: available (mode: ${CS_GH_AUTH_MODE:-unknown})."
echo "Git identity configured:"
echo "  - name: ${DETECTED_GIT_IDENTITY_NAME:-<unset>}"
echo "  - email: ${DETECTED_GIT_IDENTITY_EMAIL:-<unset>}"
echo "Claude configured:"
echo "  - settings: ${CLAUDE_SETTINGS_JSON}"
echo "  - model: ${CLAUDE_MODEL}"
echo "  - base URL: ${CLAUDE_BASE_URL}"
echo "  - permission mode: ${CLAUDE_PERMISSION_MODE}"
echo "  - shell exports: ${AGENT_BASHRC_FILE}"
