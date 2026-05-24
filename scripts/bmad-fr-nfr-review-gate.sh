#!/usr/bin/env bash
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
repo_root="$(cd "$script_dir/.." && pwd)"
invocation_dir="$(pwd -P)"

show_usage() {
  cat <<'USAGE'
Usage: scripts/bmad-fr-nfr-review-gate.sh --spec PATH [options]

Runs the BMAD FR/NFR review gate through scripts/ai-review-loop.sh.

Options:
  --spec PATH              BMAD spec bundle or spec file to review against.
  --manual-evidence PATH   Manual test evidence file or directory.
  --pr NUMBER              GitHub PR number. Defaults to agent auto-detection.
  --base REF               Base ref for git diff review.
  --max-iter N             Max AI review/fix iterations.
  --verify-cmd COMMAND     Trusted verification command. Default: make ci.
  --log-dir PATH           Review log directory.
  --agents LIST            Comma-separated agents, e.g. codex,claude.
  --status-context NAME    GitHub status context for published gate results.
  -h, --help               Show this help.

Environment equivalents:
  BMAD_REVIEW_SPEC_PATH, BMAD_REVIEW_MANUAL_EVIDENCE, BMAD_REVIEW_PR,
  BMAD_REVIEW_BASE, BMAD_REVIEW_MAX_ITER, BMAD_REVIEW_VERIFY_CMD,
  BMAD_REVIEW_LOG_DIR, BMAD_REVIEW_AGENTS, BMAD_REVIEW_POST_PR_COMMENT,
  BMAD_REVIEW_POST_GITHUB_STATUS, BMAD_REVIEW_STATUS_CONTEXT,
  BMAD_REVIEW_STATUS_EXCLUDED_CONTEXT
USAGE
}

bmad_nfr_categories="Performance, Usability, Maintainability, Availability, Interoperability, Security, Manageability, Automatability, Dependability"
bmad_required_gate_markers="FR_NFR_SCORECARD: PASS,NFR_CATALOG_SCORECARD: PASS,MANUAL_TEST_EVIDENCE: PASS,QA_BEST_PRACTICES: PASS,GITHUB_COMPLETION_GATE: PASS,CI_GATE: PASS"

spec_path="${BMAD_REVIEW_SPEC_PATH:-${AI_REVIEW_SPEC_PATH:-}}"
manual_evidence="${BMAD_REVIEW_MANUAL_EVIDENCE:-}"
pr_number="${BMAD_REVIEW_PR:-}"
base_ref="${BMAD_REVIEW_BASE:-}"
max_iter="${BMAD_REVIEW_MAX_ITER:-}"
verify_cmd="${BMAD_REVIEW_VERIFY_CMD:-}"
log_dir="${BMAD_REVIEW_LOG_DIR:-}"
agents="${BMAD_REVIEW_AGENTS:-}"
post_pr_comment="${BMAD_REVIEW_POST_PR_COMMENT:-true}"
post_github_status="${BMAD_REVIEW_POST_GITHUB_STATUS:-true}"
status_context="${BMAD_REVIEW_STATUS_CONTEXT:-BMAD FR/NFR Review Gate}"
status_excluded_context="${BMAD_REVIEW_STATUS_EXCLUDED_CONTEXT:-}"

require_option_value() {
  local option="$1"
  local value="${2:-}"

  if [[ -z "$value" || "$value" == -* ]]; then
    echo "Error: $option requires a value." >&2
    show_usage >&2
    exit 1
  fi
}

resolve_path() {
  local path="$1"

  if [[ "$path" == /* ]]; then
    printf "%s\n" "$path"
  else
    printf "%s/%s\n" "$invocation_dir" "$path"
  fi
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --spec)
      require_option_value "$1" "${2:-}"
      spec_path="$2"
      shift 2
      ;;
    --manual-evidence)
      require_option_value "$1" "${2:-}"
      manual_evidence="$2"
      shift 2
      ;;
    --pr)
      require_option_value "$1" "${2:-}"
      pr_number="$2"
      shift 2
      ;;
    --base)
      require_option_value "$1" "${2:-}"
      base_ref="$2"
      shift 2
      ;;
    --max-iter)
      require_option_value "$1" "${2:-}"
      max_iter="$2"
      shift 2
      ;;
    --verify-cmd)
      require_option_value "$1" "${2:-}"
      verify_cmd="$2"
      shift 2
      ;;
    --log-dir)
      require_option_value "$1" "${2:-}"
      log_dir="$2"
      shift 2
      ;;
    --agents)
      require_option_value "$1" "${2:-}"
      agents="$2"
      shift 2
      ;;
    --status-context)
      require_option_value "$1" "${2:-}"
      status_context="$2"
      shift 2
      ;;
    -h|--help)
      show_usage
      exit 0
      ;;
    *)
      echo "Unknown option: $1" >&2
      show_usage >&2
      exit 1
      ;;
  esac
done

status_excluded_context="${status_excluded_context:-$status_context}"

if [[ -z "$spec_path" ]]; then
  echo "Error: --spec or BMAD_REVIEW_SPEC_PATH is required." >&2
  exit 1
fi

spec_path="$(resolve_path "$spec_path")"

if [[ ! -e "$spec_path" ]]; then
  echo "Error: Spec path not found: $spec_path" >&2
  exit 1
fi

if [[ -n "$manual_evidence" ]]; then
  manual_evidence="$(resolve_path "$manual_evidence")"
  if [[ ! -e "$manual_evidence" ]]; then
    echo "Error: Manual evidence path not found: $manual_evidence" >&2
    exit 1
  fi
fi

[[ -n "$log_dir" ]] && log_dir="$(resolve_path "$log_dir")"

export AI_REVIEW_REVIEW_PROMPT="$script_dir/ai-review-prompts/bmad-fr-nfr-review.md"
export AI_REVIEW_FIX_PROMPT="$script_dir/ai-review-prompts/bmad-fr-nfr-fix.md"
export AI_REVIEW_SPEC_PATH="$spec_path"
export AI_REVIEW_SCORE_THRESHOLD="5"
export AI_REVIEW_NFR_CATEGORIES="$bmad_nfr_categories"
export AI_REVIEW_REQUIRE_GATE_MARKERS="true"
# ai-review-loop requires scored evidence for every pinned NFR category.
export AI_REVIEW_REQUIRE_SCORECARD_VALIDATION=true
# ai-review-loop prechecks and revalidates GitHub review/check state against the local HEAD.
export AI_REVIEW_REQUIRE_GITHUB_CI_CORROBORATION=true
export AI_REVIEW_REQUIRED_GATE_MARKERS="$bmad_required_gate_markers"
export AI_REVIEW_POST_PR_COMMENT="$post_pr_comment"
export AI_REVIEW_POST_GITHUB_STATUS="$post_github_status"
export AI_REVIEW_RESULT_LABEL="BMAD FR/NFR Review Gate"
export AI_REVIEW_GITHUB_STATUS_CONTEXT="$status_context"
export AI_REVIEW_GITHUB_STATUS_EXCLUDED_CONTEXT="$status_excluded_context"
export AI_REVIEW_VERIFY_ON_PASS="true"
export AI_REVIEW_CLAUDE_USE_BUILTIN_REVIEW="false"

if [[ -n "$manual_evidence" ]]; then
  export AI_REVIEW_MANUAL_EVIDENCE="$manual_evidence"
else
  unset AI_REVIEW_MANUAL_EVIDENCE
fi

if [[ -n "$pr_number" ]]; then
  export AI_REVIEW_PR_NUMBER="$pr_number"
else
  unset AI_REVIEW_PR_NUMBER
fi

if [[ -n "$base_ref" ]]; then
  export AI_REVIEW_BASE="$base_ref"
else
  unset AI_REVIEW_BASE
fi

if [[ -n "$max_iter" ]]; then
  export AI_REVIEW_MAX_ITER="$max_iter"
else
  unset AI_REVIEW_MAX_ITER
fi

if [[ -n "$verify_cmd" ]]; then
  export AI_REVIEW_VERIFY_CMD="$verify_cmd"
else
  unset AI_REVIEW_VERIFY_CMD
fi

if [[ -n "$log_dir" ]]; then
  export AI_REVIEW_LOG_DIR="$log_dir"
else
  unset AI_REVIEW_LOG_DIR
fi

unset AI_REVIEW_AGENT
unset AI_REVIEW_FIX_AGENT

if [[ -n "$agents" ]]; then
  export AI_REVIEW_AGENTS="$agents"
else
  unset AI_REVIEW_AGENTS
fi

cd "$repo_root"
exec "$script_dir/ai-review-loop.sh"
