#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck disable=SC1091
# shellcheck source=scripts/agent-learning/lib.sh
. "${SCRIPT_DIR}/lib.sh"

usage() {
    cat <<'USAGE'
Usage:
  scripts/agent-learning/record-intervention.sh --trace-id ID --type TYPE --summary TEXT [options]

Options:
  --trace-id ID       Source trace id.
  --type TYPE         reprompt, manual-diff, test-failure, or tool-retry.
  --summary TEXT      Short human-readable learning signal summary.
  --skill PATH        Skill reference. Defaults to the source trace skill_ref when available.
  --reprompt TEXT     Follow-up prompt that corrected the run.
  --good-output TEXT  Desired output or behavior.
  --diff-file PATH    Manual diff that corrected the run.
  --labels CSV        Comma-separated labels for episode grouping.
  --signal-id ID      Stable signal id. Defaults to a deterministic hash.
  --store-dir PATH    Artifact store directory. Defaults to AGENT_LEARNING_DIR or .agent-learning.
  --output PATH       Signal JSON output path.
USAGE
}

trace_id=""
signal_type=""
summary=""
skill_ref=""
reprompt=""
good_output=""
diff_content=""
labels_csv=""
signal_id=""
output_file=""
option_value=""

while [ "$#" -gt 0 ]; do
    case "$1" in
        --trace-id)
            option_value="${2:-}"
            al_require_option_value "$1" "${option_value}"
            trace_id="${option_value}"
            shift 2
            ;;
        --type)
            option_value="${2:-}"
            al_require_option_value "$1" "${option_value}"
            signal_type="${option_value}"
            shift 2
            ;;
        --summary)
            option_value="${2:-}"
            al_require_option_value "$1" "${option_value}"
            summary="${option_value}"
            shift 2
            ;;
        --skill)
            option_value="${2:-}"
            al_require_option_value "$1" "${option_value}"
            skill_ref="${option_value}"
            shift 2
            ;;
        --reprompt)
            option_value="${2:-}"
            al_require_option_value "$1" "${option_value}"
            reprompt="${option_value}"
            shift 2
            ;;
        --good-output)
            option_value="${2:-}"
            al_require_option_value "$1" "${option_value}"
            good_output="${option_value}"
            shift 2
            ;;
        --diff-file)
            option_value="${2:-}"
            al_require_option_value "$1" "${option_value}"
            diff_content="$(cat "${option_value}")"
            shift 2
            ;;
        --labels)
            option_value="${2:-}"
            al_require_option_value "$1" "${option_value}"
            labels_csv="${option_value}"
            shift 2
            ;;
        --signal-id)
            option_value="${2:-}"
            al_require_option_value "$1" "${option_value}"
            signal_id="${option_value}"
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
        *)
            echo "Error: unknown option '$1'." >&2
            usage >&2
            exit 2
            ;;
    esac
done

al_require_command jq

if [ -z "${trace_id}" ] || [ -z "${signal_type}" ] || [ -z "${summary}" ]; then
    echo "Error: --trace-id, --type, and --summary are required." >&2
    exit 2
fi

case "${signal_type}" in
    reprompt|manual-diff|test-failure|tool-retry) ;;
    *)
        echo "Error: unsupported intervention type '${signal_type}'." >&2
        exit 2
        ;;
esac

store_dir="$(al_store_dir)"
trace_file="${store_dir}/traces/${trace_id}.json"
if [ -z "${skill_ref}" ] && [ -f "${trace_file}" ]; then
    skill_ref="$(jq -r '.skill_ref // ""' "${trace_file}")"
fi

created_at="$(al_now)"
if [ -z "${signal_id}" ]; then
    signal_id="signal-$(al_stable_id "${trace_id}|${signal_type}|${summary}|${skill_ref}|${reprompt}|${good_output}|${diff_content}|${labels_csv}")"
fi

if [ -z "${output_file}" ]; then
    output_file="${store_dir}/interventions/${signal_id}.json"
fi
mkdir -p "$(dirname "${output_file}")"

labels_json="$(al_labels_json "${labels_csv}")"

jq -n \
    --arg schema_version "agent-learning.signal.v1" \
    --arg signal_id "${signal_id}" \
    --arg trace_id "${trace_id}" \
    --arg created_at "${created_at}" \
    --arg skill_ref "${skill_ref}" \
    --arg type "${signal_type}" \
    --arg summary "${summary}" \
    --arg reprompt "${reprompt}" \
    --arg good_output "${good_output}" \
    --arg diff "${diff_content}" \
    --argjson labels "${labels_json}" \
    '{
        schema_version: $schema_version,
        signal_id: $signal_id,
        trace_id: $trace_id,
        created_at: $created_at,
        skill_ref: $skill_ref,
        type: $type,
        summary: $summary,
        reprompt: $reprompt,
        good_output: $good_output,
        diff: $diff,
        labels: $labels
    }' >"${output_file}"

echo "Intervention recorded: $(al_relative_path "${output_file}")"
