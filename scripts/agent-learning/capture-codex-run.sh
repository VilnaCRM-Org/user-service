#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck disable=SC1091
# shellcheck source=scripts/agent-learning/lib.sh
. "${SCRIPT_DIR}/lib.sh"

usage() {
    cat <<'USAGE'
Usage:
  scripts/agent-learning/capture-codex-run.sh --skill PATH --prompt TEXT [options] -- COMMAND [ARGS...]

Options:
  --skill PATH          Skill prompt/module reference for the run.
  --skill-version TEXT  Explicit skill version. Defaults to the skill file git hash.
  --prompt TEXT         User prompt or task input.
  --prompt-file PATH    Read prompt from a file.
  --trace-id TEXT       Stable trace id. Defaults to a generated id.
  --store-dir PATH      Artifact store directory. Defaults to AGENT_LEARNING_DIR or .agent-learning.
  --output PATH         Trace JSON output path. Defaults to STORE/traces/TRACE_ID.json.

Environment:
  AGENT_LIGHTNING_BASE_URL  Convenience alias for OPENAI_BASE_URL.
  OPENAI_BASE_URL           OpenAI-compatible proxy endpoint used by Codex.
USAGE
}

skill_ref=""
skill_version=""
prompt=""
trace_id=""
output_file=""
cmd=()
option_value=""

while [ "$#" -gt 0 ]; do
    case "$1" in
        --skill)
            option_value="${2:-}"
            al_require_option_value "$1" "${option_value}"
            skill_ref="${option_value}"
            shift 2
            ;;
        --skill-version)
            option_value="${2:-}"
            al_require_option_value "$1" "${option_value}"
            skill_version="${option_value}"
            shift 2
            ;;
        --prompt)
            option_value="${2:-}"
            al_require_option_value "$1" "${option_value}"
            prompt="${option_value}"
            shift 2
            ;;
        --prompt-file)
            option_value="${2:-}"
            al_require_option_value "$1" "${option_value}"
            prompt="$(cat "${option_value}")"
            shift 2
            ;;
        --trace-id)
            option_value="${2:-}"
            al_require_option_value "$1" "${option_value}"
            trace_id="${option_value}"
            shift 2
            ;;
        --store-dir)
            option_value="${2:-}"
            al_require_option_value "$1" "${option_value}"
            export AGENT_LEARNING_DIR="${option_value}"
            shift 2
            ;;
        --output)
            option_value="${2:-}"
            al_require_option_value "$1" "${option_value}"
            output_file="${option_value}"
            shift 2
            ;;
        --help|-h)
            usage
            exit 0
            ;;
        --)
            shift
            cmd=("$@")
            break
            ;;
        *)
            echo "Error: unknown option '$1'." >&2
            usage >&2
            exit 2
            ;;
    esac
done

al_require_command jq

if [ -z "${skill_ref}" ]; then
    echo "Error: --skill is required." >&2
    exit 2
fi

if [ -z "${prompt}" ]; then
    echo "Error: --prompt or --prompt-file is required." >&2
    exit 2
fi

if [ "${#cmd[@]}" -eq 0 ]; then
    al_require_command codex
    cmd=(codex exec "${prompt}")
fi

store_dir="$(al_store_dir)"
mkdir -p "${store_dir}/traces" "${store_dir}/artifacts"

created_at="$(al_now)"
if [ -z "${trace_id}" ]; then
    trace_id="trace-$(al_stable_id "${skill_ref}|${prompt}|${created_at}|$$")"
fi

if [ -z "${skill_version}" ]; then
    skill_version="$(al_skill_version "${skill_ref}")"
fi

stdout_file="${store_dir}/artifacts/${trace_id}.stdout"
stderr_file="${store_dir}/artifacts/${trace_id}.stderr"
al_export_proxy_env
proxy_source="$(al_proxy_source)"

set +e
"${cmd[@]}" >"${stdout_file}" 2>"${stderr_file}"
exit_code=$?
set -e

command_json="$(jq -n --args '$ARGS.positional' "${cmd[@]}")"
stdout_artifact="$(al_relative_path "${stdout_file}")"
stderr_artifact="$(al_relative_path "${stderr_file}")"

if [ -z "${output_file}" ]; then
    output_file="${store_dir}/traces/${trace_id}.json"
fi
mkdir -p "$(dirname "${output_file}")"

jq -n \
    --arg schema_version "agent-learning.trace.v1" \
    --arg trace_id "${trace_id}" \
    --arg created_at "${created_at}" \
    --arg skill_ref "${skill_ref}" \
    --arg skill_version "${skill_version}" \
    --arg prompt "${prompt}" \
    --arg resolved_prompt "${prompt}" \
    --arg cwd "$(pwd)" \
    --arg openai_base_url "${OPENAI_BASE_URL:-}" \
    --arg proxy_source "${proxy_source}" \
    --arg stdout_artifact "${stdout_artifact}" \
    --arg stderr_artifact "${stderr_artifact}" \
    --argjson command "${command_json}" \
    --argjson exit_code "${exit_code}" \
    --rawfile stdout "${stdout_file}" \
    --rawfile stderr "${stderr_file}" \
    '{
        schema_version: $schema_version,
        trace_id: $trace_id,
        created_at: $created_at,
        skill_ref: $skill_ref,
        skill_version: $skill_version,
        prompt: $prompt,
        resolved_prompt: $resolved_prompt,
        command: $command,
        cwd: $cwd,
        openai_base_url: $openai_base_url,
        proxy_source: $proxy_source,
        tool_calls: [],
        tool_results: [],
        stdout_artifact: $stdout_artifact,
        stderr_artifact: $stderr_artifact,
        final_output: $stdout,
        error: (if $exit_code == 0 then null else $stderr end),
        exit_code: $exit_code
    }' >"${output_file}"

echo "Trace recorded: $(al_relative_path "${output_file}")"
exit "${exit_code}"
