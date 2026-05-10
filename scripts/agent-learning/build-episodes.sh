#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck disable=SC1091
# shellcheck source=scripts/agent-learning/lib.sh
. "${SCRIPT_DIR}/lib.sh"

usage() {
    cat <<'USAGE'
Usage:
  scripts/agent-learning/build-episodes.sh [options]

Options:
  --store-dir PATH          Artifact store directory. Defaults to AGENT_LEARNING_DIR or .agent-learning.
  --trace-dir PATH          Trace JSON directory. Defaults to STORE/traces.
  --intervention-dir PATH   Intervention JSON directory. Defaults to STORE/interventions.
  --output PATH             Episode JSONL output. Defaults to STORE/episodes.jsonl.
USAGE
}

trace_dir=""
intervention_dir=""
output_file=""
option_value=""

while [ "$#" -gt 0 ]; do
    case "$1" in
        --store-dir)
            option_value="${2:-}"
            al_require_option_value "$1" "${option_value}"
            export AGENT_LEARNING_DIR="${option_value}"
            shift 2
            ;;
        --trace-dir)
            option_value="${2:-}"
            al_require_option_value "$1" "${option_value}"
            trace_dir="${option_value}"
            shift 2
            ;;
        --intervention-dir)
            option_value="${2:-}"
            al_require_option_value "$1" "${option_value}"
            intervention_dir="${option_value}"
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

store_dir="$(al_store_dir)"
trace_dir="${trace_dir:-${store_dir}/traces}"
intervention_dir="${intervention_dir:-${store_dir}/interventions}"
output_file="${output_file:-${store_dir}/episodes.jsonl}"

mkdir -p "$(dirname "${output_file}")"
: >"${output_file}"

if [ ! -d "${intervention_dir}" ]; then
    echo "Episodes written: $(al_relative_path "${output_file}") (0 records)"
    exit 0
fi

while IFS= read -r signal_file; do
    if ! signal_meta="$(jq -r '[.trace_id // "", .signal_id // ""] | @tsv' "${signal_file}" 2>/dev/null)"; then
        echo "Warning: skipping malformed intervention ${signal_file}" >&2
        continue
    fi

    IFS=$'\t' read -r trace_id signal_id <<<"${signal_meta}"
    if [ -z "${trace_id}" ] || [ -z "${signal_id}" ]; then
        echo "Warning: skipping invalid intervention ${signal_file}" >&2
        continue
    fi

    trace_file="${trace_dir}/${trace_id}.json"
    if [ ! -f "${trace_file}" ]; then
        echo "Warning: skipping ${signal_id}; missing trace ${trace_id}" >&2
        continue
    fi

    if ! jq empty "${trace_file}" >/dev/null 2>&1; then
        echo "Warning: skipping ${signal_id}; malformed trace ${trace_id}" >&2
        continue
    fi

    episode_id="episode-$(al_stable_id "${trace_id}|${signal_id}")"
    jq -cn \
        --slurpfile trace "${trace_file}" \
        --slurpfile signal "${signal_file}" \
        --arg episode_id "${episode_id}" \
        '
        ($trace[0]) as $t
        | ($signal[0]) as $s
        | {
            schema_version: "agent-learning.episode.v1",
            episode_id: $episode_id,
            source: {
                trace_id: $t.trace_id,
                signal_id: $s.signal_id
            },
            created_at: $s.created_at,
            skill_ref: ($s.skill_ref // $t.skill_ref),
            skill_version: ($t.skill_version // "unversioned"),
            labels: ((($s.labels // []) + [$s.type]) | unique),
            input: {
                user_input: $t.prompt,
                resolved_prompt: $t.resolved_prompt,
                command: $t.command,
                openai_base_url: $t.openai_base_url
            },
            bad_output: ($t.final_output // ""),
            good_output: ($s.good_output // $s.reprompt // $s.diff // $s.summary // ""),
            intervention: {
                type: $s.type,
                summary: $s.summary,
                diff: $s.diff
            }
        }' >>"${output_file}"
done < <(find "${intervention_dir}" -type f -name '*.json' -print | sort)

record_count="$(wc -l <"${output_file}" | tr -d '[:space:]')"
echo "Episodes written: $(al_relative_path "${output_file}") (${record_count} records)"
