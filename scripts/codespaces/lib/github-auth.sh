#!/usr/bin/env bash
# shellcheck shell=bash

cs_require_command() {
    local command_name="$1"
    if ! command -v "${command_name}" >/dev/null 2>&1; then
        echo "Error: required command '${command_name}' is not installed." >&2
        return 1
    fi
}

cs_load_gh_token_from_aliases() {
    local default_token_var="GH_AUTOMATION_TOKEN"
    local token_var="${GH_TOKEN_VAR:-$default_token_var}"

    if [ -n "${GH_TOKEN:-}" ]; then
        return 0
    fi

    if [ -n "${!token_var:-}" ]; then
        export GH_TOKEN="${!token_var}"
        return 0
    elif [ -n "${GITHUB_TOKEN:-}" ]; then
        export GH_TOKEN="${GITHUB_TOKEN}"
        return 0
    fi

    return 1
}

cs_detect_user_auth() {
    if gh api user >/dev/null 2>&1; then
        printf 'user'
        return 0
    fi

    return 1
}

cs_ensure_gh_auth() {
    local auth_mode

    cs_require_command gh

    if auth_mode="$(cs_detect_user_auth)"; then
        export CS_GH_AUTH_MODE="${auth_mode}"
        gh auth setup-git >/dev/null 2>&1 || true
        return 0
    fi

    if cs_load_gh_token_from_aliases && auth_mode="$(cs_detect_user_auth)"; then
        export CS_GH_AUTH_MODE="${auth_mode}"
        gh auth setup-git >/dev/null 2>&1 || true
        return 0
    fi

    cat >&2 <<'EOM'
Error: GitHub authentication is not available.
Provide one of:
  - GH_TOKEN
  - GH_AUTOMATION_TOKEN
  - GITHUB_TOKEN
or run interactive login in the Codespace:
  gh auth login -h github.com -w
EOM
    return 1
}
