#!/usr/bin/env bash
set -euo pipefail

al_root_dir() {
    git rev-parse --show-toplevel 2>/dev/null || pwd
}

al_store_dir() {
    local dir
    dir="${AGENT_LEARNING_DIR:-.agent-learning}"

    case "${dir}" in
        /*) printf '%s\n' "${dir}" ;;
        *) printf '%s/%s\n' "$(al_root_dir)" "${dir}" ;;
    esac
}

al_require_command() {
    local command_name
    command_name="$1"

    if ! command -v "${command_name}" >/dev/null 2>&1; then
        echo "Error: ${command_name} is required for agent-learning commands." >&2
        exit 1
    fi
}

al_require_option_value() {
    local option_name option_value
    option_name="$1"
    option_value="$2"

    if [ -z "${option_value}" ]; then
        echo "Error: ${option_name} requires a value." >&2
        exit 2
    fi
}

al_now() {
    if [ -n "${AGENT_LEARNING_NOW:-}" ]; then
        printf '%s\n' "${AGENT_LEARNING_NOW}"
        return
    fi

    date -u +"%Y-%m-%dT%H:%M:%SZ"
}

al_stable_id() {
    local input
    input="$1"

    if command -v sha256sum >/dev/null 2>&1; then
        printf '%s' "${input}" | sha256sum | awk '{print substr($1, 1, 16)}'
        return
    fi

    printf '%s' "${input}" | shasum -a 256 | awk '{print substr($1, 1, 16)}'
}

al_skill_version() {
    local skill_ref
    skill_ref="$1"

    if [ -f "${skill_ref}" ]; then
        git hash-object "${skill_ref}" 2>/dev/null \
            || al_file_hash_prefix "${skill_ref}"
        return
    fi

    printf '%s\n' "unversioned"
}

al_file_hash_prefix() {
    local path
    path="$1"

    if command -v sha256sum >/dev/null 2>&1; then
        sha256sum "${path}" | awk '{print substr($1, 1, 16)}'
        return
    fi

    shasum -a 256 "${path}" | awk '{print substr($1, 1, 16)}'
}

al_relative_path() {
    local path root
    path="$1"
    root="$(al_root_dir)"

    case "${path}" in
        "${root}"/*) printf '%s\n' "${path#"${root}/"}" ;;
        *) printf '%s\n' "${path}" ;;
    esac
}

al_export_proxy_env() {
    if [ -n "${AGENT_LIGHTNING_BASE_URL:-}" ] && [ -z "${OPENAI_BASE_URL:-}" ]; then
        export OPENAI_BASE_URL="${AGENT_LIGHTNING_BASE_URL}"
        return
    fi
}

al_proxy_source() {
    if [ -n "${AGENT_LIGHTNING_BASE_URL:-}" ] && [ "${OPENAI_BASE_URL:-}" = "${AGENT_LIGHTNING_BASE_URL}" ]; then
        printf '%s\n' "agent_lightning"
        return
    fi

    if [ -n "${OPENAI_BASE_URL:-}" ]; then
        printf '%s\n' "openai_base_url"
        return
    fi

    printf '%s\n' "default"
}

al_labels_json() {
    local labels_csv
    labels_csv="$1"

    if [ -z "${labels_csv}" ]; then
        printf '[]\n'
        return
    fi

    printf '%s' "${labels_csv}" \
        | jq -R 'split(",") | map(gsub("^\\s+|\\s+$"; "")) | map(select(length > 0))'
}
