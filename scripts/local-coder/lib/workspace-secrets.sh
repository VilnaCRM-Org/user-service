#!/usr/bin/env bash
# shellcheck shell=bash

cs_load_host_workspace_secrets() {
    local secrets_root="${OPENCLAW_HOST_SECRETS_DIR:-/run/openclaw-host-secrets}"
    local env_file="${secrets_root}/openclaw.env"
    local openai_value=""
    local token=""
    local token_file=""

    if [ -z "${OPENAI_API_KEY:-}" ] && [ -f "${env_file}" ]; then
        openai_value="$(
            env -i ENV_FILE="${env_file}" sh -c '. "$ENV_FILE" >/dev/null 2>&1; printf %s "${OPENAI_API_KEY:-}"'
        )"
        if [ -n "${openai_value}" ]; then
            export OPENAI_API_KEY="${openai_value}"
        fi
    fi

    if [ -z "${GH_AUTOMATION_TOKEN:-}" ] && [ -z "${GH_TOKEN:-}" ] && [ -z "${GITHUB_TOKEN:-}" ]; then
        for token_file in \
            "${secrets_root}/gh_pat" \
            "${secrets_root}/gh_token" \
            "${secrets_root}/gh_user_token"; do
            if [ ! -f "${token_file}" ]; then
                continue
            fi
            token="$(tr -d '\r\n' < "${token_file}")"
            if [ -n "${token}" ]; then
                export GH_AUTOMATION_TOKEN="${token}"
                break
            fi
        done
    fi

    if [ -n "${GH_AUTOMATION_TOKEN:-}" ] && [ -z "${GH_TOKEN:-}" ]; then
        export GH_TOKEN="${GH_AUTOMATION_TOKEN}"
    fi
    if [ -n "${GH_TOKEN:-}" ] && [ -z "${GITHUB_TOKEN:-}" ]; then
        export GITHUB_TOKEN="${GH_TOKEN}"
    fi

    cs_sync_host_codex_auth
}

cs_sync_host_codex_auth() {
    local secrets_root="${OPENCLAW_HOST_SECRETS_DIR:-/run/openclaw-host-secrets}"
    local source_file="${OPENCLAW_HOST_CODEX_AUTH_FILE:-${secrets_root}/codex-auth.json}"
    local fallback_source_file="/run/openclaw-host-codex/auth.json"
    local target_file="${OPENCLAW_WORKSPACE_CODEX_AUTH_FILE:-${HOME}/.codex/auth.json}"
    local target_dir
    local tmp_file=""

    if [ ! -f "${source_file}" ] && [ -f "${fallback_source_file}" ]; then
        source_file="${fallback_source_file}"
    fi

    if [ ! -f "${source_file}" ]; then
        return 0
    fi

    target_dir="$(dirname "${target_file}")"

    if ! mkdir -p "${target_dir}"; then
        echo "Warning: unable to prepare Codex auth directory at '${target_dir}'." >&2
        return 0
    fi

    chmod 700 "${target_dir}" >/dev/null 2>&1 || true

    if ! tmp_file="$(mktemp "${target_dir}/.auth.json.XXXXXX")"; then
        echo "Warning: unable to stage Codex auth into '${target_file}'." >&2
        return 0
    fi

    if ! cp "${source_file}" "${tmp_file}"; then
        rm -f "${tmp_file}" >/dev/null 2>&1 || true
        echo "Warning: unable to copy Codex auth from host into the workspace." >&2
        return 0
    fi

    chmod 600 "${tmp_file}" >/dev/null 2>&1 || true

    if ! mv -f "${tmp_file}" "${target_file}"; then
        rm -f "${tmp_file}" >/dev/null 2>&1 || true
        echo "Warning: unable to install Codex auth into '${target_file}'." >&2
        return 0
    fi

    chmod 600 "${target_file}" >/dev/null 2>&1 || true
}
