#!/usr/bin/env bash
# shellcheck shell=bash
#
# Requires: cs_require_command from scripts/local-coder/lib/github-auth.sh

# Load BMALPH-related defaults without overriding caller-provided environment.
cs_bmalph_load_defaults() {
    : "${BMALPH_NPM_PACKAGE:=bmalph}"
    : "${BMALPH_DEFAULT_PLATFORM:=codex}"
    : "${BMALPH_DEFAULT_PROJECT_NAME:=user-service}"
    : "${BMALPH_DEFAULT_PROJECT_DESCRIPTION:=VilnaCRM User Service}"
    : "${BMALPH_PLANNING_ARTIFACTS:=specs}"
    : "${BMALPH_TRANSITION_ARTIFACTS:=_bmad-output/planning-artifacts}"
    : "${BMALPH_ACTIVE_SPEC_BUNDLE:=}"
    : "${CS_USER_NPM_GLOBAL_BIN:=${HOME}/.npm-global/bin}"
}

# Map a BMALPH platform to the init output marker used for dry-run verification.
cs_bmalph_expected_platform_marker() {
    local platform="${1:-${BMALPH_DEFAULT_PLATFORM:-codex}}"

    case "${platform}" in
        codex)
            printf '.agents/skills/'
            ;;
        claude-code)
            printf '.claude/commands/'
            ;;
        *)
            printf 'bmalph/config.json'
            ;;
    esac
}

# Return the companion CLI expected to be present for a given BMALPH platform.
cs_bmalph_platform_cli_hint() {
    local platform="${1:-${BMALPH_DEFAULT_PLATFORM:-codex}}"

    case "${platform}" in
        codex)
            printf 'codex'
            ;;
        claude-code)
            printf 'claude'
            ;;
        *)
            printf ''
            ;;
    esac
}

# Ensure the BMALPH CLI is installed and reachable in the current shell PATH.
cs_ensure_bmalph_cli() {
    local cs_npm_prefix

    cs_bmalph_load_defaults
    cs_npm_prefix="${CS_USER_NPM_GLOBAL_BIN%/bin}"
    export PATH="${CS_USER_NPM_GLOBAL_BIN}:${PATH}"

    if command -v bmalph >/dev/null 2>&1; then
        return 0
    fi

    cs_require_command npm || return 1

    mkdir -p "${cs_npm_prefix}"
    npm config set prefix "${cs_npm_prefix}" >/dev/null 2>&1 || true
    npm install -g "${BMALPH_NPM_PACKAGE}"

    if ! command -v bmalph >/dev/null 2>&1; then
        cat >&2 <<'EOM'
Error: BMALPH CLI installation completed but 'bmalph' is still not in PATH.
Ensure npm global bin is available in the current shell.
EOM
        return 1
    fi
}

# Run a disposable BMALPH init dry-run and assert the expected platform marker.
cs_verify_bmalph_dry_run() {
    local platform="${1:-${BMALPH_DEFAULT_PLATFORM:-codex}}"
    local project_name="${2:-${BMALPH_DEFAULT_PROJECT_NAME:-user-service}}"
    local project_description="${3:-${BMALPH_DEFAULT_PROJECT_DESCRIPTION:-VilnaCRM User Service}}"
    local expected_marker
    local tmp_project_dir
    local tmp_output

    cs_bmalph_load_defaults
    export PATH="${CS_USER_NPM_GLOBAL_BIN}:${PATH}"
    cs_require_command bmalph || return 1

    expected_marker="$(cs_bmalph_expected_platform_marker "${platform}")"
    tmp_project_dir="$(mktemp -d)"
    tmp_output="$(mktemp)"

    # Remove temporary files created during dry-run verification.
    trap "trap - RETURN; rm -rf -- \"${tmp_project_dir}\" \"${tmp_output}\"" RETURN

    if ! bmalph -C "${tmp_project_dir}" init \
        --platform "${platform}" \
        --name "${project_name}" \
        --description "${project_description}" \
        --dry-run >"${tmp_output}" 2>&1; then
        echo "Error: BMALPH dry-run verification failed for platform '${platform}'." >&2
        sed -n '1,160p' "${tmp_output}" >&2
        return 1
    fi

    if ! grep -Fq -- "${expected_marker}" "${tmp_output}"; then
        echo "Error: BMALPH dry-run verification did not report expected platform output '${expected_marker}'." >&2
        sed -n '1,160p' "${tmp_output}" >&2
        return 1
    fi
}

# Rewrite repo-specific BMAD config defaults after init or upgrade restores local files.
cs_bmalph_configure_planning_artifacts() {
    local target_dir="${1:?Missing project directory}"
    local planning_artifacts="${2:-${BMALPH_PLANNING_ARTIFACTS:-specs}}"
    local config_path="${target_dir}/_bmad/config.yaml"
    local tmp_config

    cs_bmalph_load_defaults

    if [ ! -f "${config_path}" ]; then
        return 0
    fi

    tmp_config="$(mktemp)"
    trap "trap - RETURN; rm -f -- \"${tmp_config}\"" RETURN

    awk -v value="${planning_artifacts}" '
        BEGIN { found = 0 }
        /^planning_artifacts:/ {
            print "planning_artifacts: " value
            found = 1
            next
        }
        { print }
        END {
            if (found == 0) {
                print "planning_artifacts: " value
            }
        }
    ' "${config_path}" >"${tmp_config}"

    mv "${tmp_config}" "${config_path}"

    case "${planning_artifacts}" in
        /*)
            mkdir -p "${planning_artifacts}"
            ;;
        *)
            mkdir -p "${target_dir}/${planning_artifacts}"
            ;;
    esac
}

cs_bmalph_resolve_project_path() {
    local target_dir="${1:?Missing project directory}"
    local path="${2:?Missing path}"

    case "${path}" in
        /*)
            printf '%s\n' "${path}"
            ;;
        *)
            printf '%s/%s\n' "${target_dir}" "${path}"
            ;;
    esac
}

cs_bmalph_source_has_required_transition_artifacts() {
    local source_dir="${1:?Missing source directory}"
    local prd_matches
    local architecture_matches
    local story_matches
    local readiness_matches

    shopt -s nullglob
    prd_matches=("${source_dir}"/*prd*.md)
    architecture_matches=("${source_dir}"/*architect*.md)
    story_matches=("${source_dir}"/*epic*.md "${source_dir}"/*story*.md "${source_dir}"/*stories*.md)
    readiness_matches=("${source_dir}"/*readiness*.md)
    shopt -u nullglob

    [ "${#prd_matches[@]}" -gt 0 ] \
        && [ "${#architecture_matches[@]}" -gt 0 ] \
        && [ "${#story_matches[@]}" -gt 0 ] \
        && [ "${#readiness_matches[@]}" -gt 0 ]
}

cs_bmalph_prepare_transition_artifacts() {
    local target_dir="${1:?Missing project directory}"
    local planning_artifacts="${2:-${BMALPH_PLANNING_ARTIFACTS:-specs}}"
    local transition_artifacts="${3:-${BMALPH_TRANSITION_ARTIFACTS:-_bmad-output/planning-artifacts}}"
    local active_bundle="${4:-${BMALPH_ACTIVE_SPEC_BUNDLE:-}}"
    local source_path
    local source_dir
    local transition_dir

    cs_bmalph_load_defaults

    if [ -n "${active_bundle}" ]; then
        source_path="${planning_artifacts%/}/${active_bundle#/}"
    else
        source_path="${planning_artifacts%/}"
    fi

    source_dir="$(cs_bmalph_resolve_project_path "${target_dir}" "${source_path}")"
    transition_dir="$(cs_bmalph_resolve_project_path "${target_dir}" "${transition_artifacts}")"

    if [ ! -d "${source_dir}" ] || [ "${source_dir}" = "${transition_dir}" ]; then
        return 0
    fi

    if ! cs_bmalph_source_has_required_transition_artifacts "${source_dir}"; then
        return 0
    fi

    rm -rf "${transition_dir}"
    mkdir -p "${transition_dir}"
    find "${source_dir}" -maxdepth 1 -type f \( -name '*.md' -o -name '*.yaml' -o -name '*.yml' \) \
        -exec cp {} "${transition_dir}/" \;

    printf '%s\n' "${transition_artifacts}"
}
