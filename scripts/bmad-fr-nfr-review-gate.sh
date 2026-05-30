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
  --impact-context PATH    Graphify/codebase-memory/Deptrac/manual graph impact context.
  --status-context NAME    GitHub status context for published gate results.
  -h, --help               Show this help.

Environment equivalents:
  BMAD_REVIEW_SPEC_PATH, BMAD_REVIEW_MANUAL_EVIDENCE, BMAD_REVIEW_PR,
  BMAD_REVIEW_BASE, BMAD_REVIEW_MAX_ITER, BMAD_REVIEW_VERIFY_CMD,
  BMAD_REVIEW_LOG_DIR, BMAD_REVIEW_AGENTS, BMAD_REVIEW_POST_PR_COMMENT,
  BMAD_REVIEW_IMPACT_CONTEXT,
  BMAD_REVIEW_POST_GITHUB_STATUS, BMAD_REVIEW_STATUS_CONTEXT,
  BMAD_REVIEW_STATUS_EXCLUDED_CONTEXT
USAGE
}

bmad_nfr_categories="Performance, Usability, Maintainability, Availability, Interoperability, Security, Manageability, Automatability, Dependability"
bmad_quality_dimensions="Functional Suitability, Performance Resource Sustainability, Compatibility Coexistence, Interaction Capability Accessibility, Reliability Resilience, Security Privacy Accountability, Maintainability Testability, Flexibility Portability, Safety Harm Prevention, Data Quality Integrity, Operational Excellence Releaseability, Observability Diagnosability, Supply-Chain Integrity, Compliance Governance, Sustainability Resource Impact, AI Automation Governance"
bmad_impact_surfaces="Runtime paths, Architecture and layer boundaries, Domain model, Persistence and database, Public API and schema, Async events and queues, Configuration and environment, Dependencies and lockfiles, CI and workflows, Tests and fixtures, Documentation, Operations and observability, Security and privacy, Backward compatibility"
bmad_required_gate_markers="FR_NFR_SCORECARD: PASS,NFR_CATALOG_SCORECARD: PASS,EXPANDED_QUALITY_SCORECARD: PASS,WHOLE_CODEBASE_IMPACT: PASS,GRAPH_IMPACT_CONTEXT: PASS,MANUAL_TEST_EVIDENCE: PASS,QA_BEST_PRACTICES: PASS,GITHUB_COMPLETION_GATE: PASS,CI_GATE: PASS"

spec_path="${BMAD_REVIEW_SPEC_PATH:-${AI_REVIEW_SPEC_PATH:-}}"
manual_evidence="${BMAD_REVIEW_MANUAL_EVIDENCE:-}"
pr_number="${BMAD_REVIEW_PR:-}"
base_ref="${BMAD_REVIEW_BASE:-}"
max_iter="${BMAD_REVIEW_MAX_ITER:-}"
verify_cmd="${BMAD_REVIEW_VERIFY_CMD:-}"
log_dir="${BMAD_REVIEW_LOG_DIR:-}"
agents="${BMAD_REVIEW_AGENTS:-}"
impact_context="${BMAD_REVIEW_IMPACT_CONTEXT:-}"
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
    --impact-context)
      require_option_value "$1" "${2:-}"
      impact_context="$2"
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

if [[ -n "$impact_context" ]]; then
  impact_context="$(resolve_path "$impact_context")"
  if [[ ! -e "$impact_context" ]]; then
    echo "Error: Impact context path not found: $impact_context" >&2
    exit 1
  fi
fi

resolve_default_base_ref() {
  local detected_base=""
  local pr_view_cmd=(gh pr view)

  if [[ -n "$base_ref" ]]; then
    return
  fi

  if command -v gh >/dev/null 2>&1; then
    if [[ -n "$pr_number" ]]; then
      pr_view_cmd+=("$pr_number")
    fi
    detected_base="$("${pr_view_cmd[@]}" --json baseRefName --jq .baseRefName 2>/dev/null || true)"
  fi

  if [[ -n "$detected_base" && "$detected_base" != "null" ]]; then
    base_ref="origin/$detected_base"
  else
    base_ref="origin/main"
  fi
}

ensure_impact_base_ref() {
  local impact_base remote branch

  impact_base="$base_ref"
  if git -C "$repo_root" rev-parse --verify "${impact_base}^{commit}" >/dev/null 2>&1; then
    printf "%s\n" "$impact_base"
    return
  fi

  if [[ "$impact_base" == */* ]] && git -C "$repo_root" remote get-url "${impact_base%%/*}" >/dev/null 2>&1; then
    remote="${impact_base%%/*}"
    branch="${impact_base#*/}"
    git -C "$repo_root" fetch "$remote" "+refs/heads/$branch:refs/remotes/$remote/$branch" >/dev/null 2>&1 || true
  elif [[ "$impact_base" != refs/* && ! "$impact_base" =~ ^HEAD([~^][0-9]*)*$ && ! "$impact_base" =~ ^[0-9a-fA-F]{7,40}$ ]]; then
    git -C "$repo_root" fetch origin "+refs/heads/$impact_base:refs/remotes/origin/$impact_base" >/dev/null 2>&1 || true
    if git -C "$repo_root" rev-parse --verify "origin/${impact_base}^{commit}" >/dev/null 2>&1; then
      impact_base="origin/$impact_base"
    fi
  fi

  if ! git -C "$repo_root" rev-parse --verify "${impact_base}^{commit}" >/dev/null 2>&1; then
    echo "Error: Graph impact context requires a locally resolvable base ref: $impact_base" >&2
    exit 1
  fi

  printf "%s\n" "$impact_base"
}

write_symbol_relationships() {
  local changed_file="$1"
  local basename_without_ext symbol

  basename_without_ext="$(basename "$changed_file")"
  basename_without_ext="${basename_without_ext%.*}"
  symbol="$basename_without_ext"

  if [[ "$changed_file" == *.php && -f "$repo_root/$changed_file" ]]; then
    symbol="$(sed -nE 's/^[[:space:]]*(final[[:space:]]+|abstract[[:space:]]+)?(class|interface|trait|enum)[[:space:]]+([A-Za-z_][A-Za-z0-9_]*).*/\3/p' "$repo_root/$changed_file" | head -n 1)"
    symbol="${symbol:-$basename_without_ext}"
  fi

  echo "### $changed_file"
  echo
  echo "- Symbol/query: \`$symbol\`"
  echo "- Direct references (bounded):"
  if [[ -n "$symbol" ]]; then
    {
      rg -n --fixed-strings "$symbol" "$repo_root" \
        --glob '!vendor/**' \
        --glob '!var/**' \
        --glob '!node_modules/**' \
        --glob '!graphify-out/**' \
        --glob "!$changed_file" \
        | sed "s#^$repo_root/##" \
        | head -n 20
    } || true
  fi
  echo
}

generate_impact_context() {
  local output_dir output_file impact_base graph_report graph_json changed_files_file

  output_dir="${log_dir:-$repo_root/var/ai-review}"
  mkdir -p "$output_dir"
  output_file="$output_dir/codebase-graph-impact-context.md"
  impact_base="$(ensure_impact_base_ref)"
  graph_report="$repo_root/graphify-out/GRAPH_REPORT.md"
  graph_json="$repo_root/graphify-out/graph.json"
  changed_files_file="$output_dir/codebase-graph-impact-changed-files.txt"
  git -C "$repo_root" diff --name-only "$impact_base"...HEAD > "$changed_files_file"

  {
    echo "# BMAD Required Graph Impact Context"
    echo
    echo "- Base ref: $impact_base"
    echo "- Head ref: $(git -C "$repo_root" rev-parse HEAD 2>/dev/null || echo UNKNOWN)"
    echo "- Generated: $(date -u +%Y-%m-%dT%H:%M:%SZ)"
    echo "- Requirement: BMAD review must use graph or relationship evidence before scoring whole-codebase impact."
    echo
    echo "## Changed Files"
    echo
    git -C "$repo_root" diff --name-status "$impact_base"...HEAD || true
    echo
    echo "## Knowledge Graph Artifacts"
    echo
    if [[ -f "$graph_report" ]]; then
      echo "- Graphify report: $graph_report"
    else
      echo "- Graphify report: not present. Additional generation command: graphify extract . --force"
    fi
    if [[ -f "$graph_json" ]]; then
      echo "- Graphify graph JSON: $graph_json"
    else
      echo "- Graphify graph JSON: not present."
    fi
    if [[ -f "$repo_root/deptrac.yaml" ]]; then
      echo "- Deptrac architecture config: $repo_root/deptrac.yaml"
      echo "- Suggested architecture graph command: make deptrac or vendor deptrac graphviz formatter in CI/container context."
    fi
    echo "- codebase-memory-mcp can be used as a local MCP/CLI graph for caller/callee and impact queries when installed."
    echo
    echo "## Required Local Relationship Graph"
    echo
    echo "The wrapper generated this bounded relationship graph from changed files and direct symbol references. Reviewers must validate these edges against source before scoring."
    echo
    while IFS= read -r changed_file || [[ -n "$changed_file" ]]; do
      [[ -z "$changed_file" ]] && continue
      write_symbol_relationships "$changed_file"
    done < "$changed_files_file"
    echo
    echo "## Reviewer Instruction"
    echo
    echo "Use this graph context as required starting evidence. The BMAD reviewer must still inspect related files through git, rg, tests, specs, docs, dependency metadata, and graph artifacts before scoring whole-codebase impact."
    if [[ -f "$graph_report" ]]; then
      echo
      echo "## Graphify Report Excerpt"
      echo
      sed -n '1,160p' "$graph_report"
    fi
  } > "$output_file"

  printf "%s\n" "$output_file"
}

resolve_default_base_ref

if [[ -z "$impact_context" ]]; then
  impact_context="$(generate_impact_context)"
fi

export AI_REVIEW_REVIEW_PROMPT="$script_dir/ai-review-prompts/bmad-fr-nfr-review.md"
export AI_REVIEW_FIX_PROMPT="$script_dir/ai-review-prompts/bmad-fr-nfr-fix.md"
export AI_REVIEW_SPEC_PATH="$spec_path"
export AI_REVIEW_SCORE_THRESHOLD="5"
export AI_REVIEW_NFR_CATEGORIES="$bmad_nfr_categories"
export AI_REVIEW_QUALITY_DIMENSIONS="$bmad_quality_dimensions"
export AI_REVIEW_IMPACT_SURFACES="$bmad_impact_surfaces"
export AI_REVIEW_IMPACT_CONTEXT="$impact_context"
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
