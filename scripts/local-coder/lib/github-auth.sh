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

    cs_require_command gh || return 1

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
or run interactive login in the workspace:
  gh auth login -h github.com -w
EOM
    return 1
}

cs_git_remote_url_to_https() {
    local url="${1:?Missing remote URL}"
    local host="${2:?Missing GitHub host}"

    case "${url}" in
        "https://${host}/"*)
            printf '%s\n' "${url}"
            ;;
        "git@${host}:"*)
            printf 'https://%s/%s\n' "${host}" "${url#git@${host}:}"
            ;;
        "ssh://git@${host}/"*)
            printf 'https://%s/%s\n' "${host}" "${url#ssh://git@${host}/}"
            ;;
        *)
            return 1
            ;;
    esac
}

cs_git_remote_url_to_ssh() {
    local url="${1:?Missing remote URL}"
    local host="${2:?Missing GitHub host}"

    case "${url}" in
        "git@${host}:"*)
            printf '%s\n' "${url}"
            ;;
        "ssh://git@${host}/"*)
            printf 'git@%s:%s\n' "${host}" "${url#ssh://git@${host}/}"
            ;;
        "https://${host}/"*)
            printf 'git@%s:%s\n' "${host}" "${url#https://${host}/}"
            ;;
        *)
            return 1
            ;;
    esac
}

cs_align_git_remote_protocol() {
    local remote_name="${1:-origin}"
    local host="${2:-${GH_HOST:-github.com}}"
    local protocol="${3:-${GH_GIT_PROTOCOL:-https}}"
    local fetch_url=""
    local push_url=""
    local normalized_fetch_url=""
    local normalized_push_url=""

    cs_require_command git || return 1

    if ! git rev-parse --git-dir >/dev/null 2>&1; then
        return 0
    fi

    fetch_url="$(git remote get-url "${remote_name}" 2>/dev/null || true)"
    if [ -z "${fetch_url}" ]; then
        return 0
    fi

    push_url="$(git remote get-url --push "${remote_name}" 2>/dev/null || true)"

    case "${protocol}" in
        https)
            normalized_fetch_url="$(cs_git_remote_url_to_https "${fetch_url}" "${host}" 2>/dev/null || true)"
            normalized_push_url="$(cs_git_remote_url_to_https "${push_url:-${fetch_url}}" "${host}" 2>/dev/null || true)"
            ;;
        ssh)
            normalized_fetch_url="$(cs_git_remote_url_to_ssh "${fetch_url}" "${host}" 2>/dev/null || true)"
            normalized_push_url="$(cs_git_remote_url_to_ssh "${push_url:-${fetch_url}}" "${host}" 2>/dev/null || true)"
            ;;
        *)
            return 0
            ;;
    esac

    if [ -n "${normalized_fetch_url}" ] && [ "${normalized_fetch_url}" != "${fetch_url}" ]; then
        git remote set-url "${remote_name}" "${normalized_fetch_url}"
    fi

    if [ -n "${normalized_push_url}" ] && [ "${normalized_push_url}" != "${push_url}" ]; then
        git remote set-url --push "${remote_name}" "${normalized_push_url}"
    fi
}
