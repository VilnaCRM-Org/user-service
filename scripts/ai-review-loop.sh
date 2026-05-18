#!/usr/bin/env bash
set -euo pipefail

# ---------------------------------------------------------------------------
# AI Review Loop — runs AI review agents, applies fixes, verifies with CI.
#
# Instead of embedding raw diffs into prompts (which can exceed context
# windows and strips project context), this script gives agents short
# task-oriented prompts and lets them use their built-in tooling (file
# reads, git, CLAUDE.md awareness) to review the codebase directly.
# ---------------------------------------------------------------------------

# --- Configuration (all overridable via environment) ----------------------

log_dir="${AI_REVIEW_LOG_DIR:-var/ai-review}"
review_prompt_file="${AI_REVIEW_REVIEW_PROMPT:-scripts/ai-review-prompts/review.md}"
fix_prompt_file="${AI_REVIEW_FIX_PROMPT:-scripts/ai-review-prompts/fix.md}"
# NOTE: verify_cmd is evaluated via bash -c. Only set this to trusted values.
verify_cmd="${AI_REVIEW_VERIFY_CMD:-make ci}"
max_iter="${AI_REVIEW_MAX_ITER:-3}"
verify_on_pass="${AI_REVIEW_VERIFY_ON_PASS:-true}"
spec_path="${AI_REVIEW_SPEC_PATH:-}"
manual_evidence="${AI_REVIEW_MANUAL_EVIDENCE:-}"
pr_number="${AI_REVIEW_PR_NUMBER:-}"
score_threshold="${AI_REVIEW_SCORE_THRESHOLD:-5}"
nfr_categories="${AI_REVIEW_NFR_CATEGORIES:-Performance, Usability, Maintainability, Availability, Interoperability, Security, Manageability, Automatability, Dependability}"
require_gate_markers="${AI_REVIEW_REQUIRE_GATE_MARKERS:-false}"
require_scorecard_validation="${AI_REVIEW_REQUIRE_SCORECARD_VALIDATION:-${AI_REVIEW_REQUIRE_BMAD_EVIDENCE:-false}}"
require_github_ci_corroboration="${AI_REVIEW_REQUIRE_GITHUB_CI_CORROBORATION:-false}"
required_gate_markers_raw="${AI_REVIEW_REQUIRED_GATE_MARKERS:-FR_NFR_SCORECARD: PASS,NFR_CATALOG_SCORECARD: PASS,MANUAL_TEST_EVIDENCE: PASS,QA_BEST_PRACTICES: PASS,GITHUB_COMPLETION_GATE: PASS,CI_GATE: PASS}"

mkdir -p "$log_dir"

# --- Validate inputs ------------------------------------------------------

for f in "$review_prompt_file" "$fix_prompt_file"; do
  if [[ ! -f "$f" ]]; then
    echo "Prompt file not found: $f" >&2
    exit 1
  fi
done

if [[ ! "$max_iter" =~ ^[0-9]+$ ]]; then
  echo "AI_REVIEW_MAX_ITER must be a non-negative integer, 0=unlimited (got: $max_iter)" >&2
  exit 1
fi

if [[ ! "$score_threshold" =~ ^[1-5]$ ]]; then
  echo "AI_REVIEW_SCORE_THRESHOLD must be an integer from 1 to 5 (got: $score_threshold)" >&2
  exit 1
fi

if [[ -n "$spec_path" && ! -e "$spec_path" ]]; then
  echo "AI_REVIEW_SPEC_PATH does not exist: $spec_path" >&2
  exit 1
fi

if [[ -n "$manual_evidence" && ! -e "$manual_evidence" ]]; then
  echo "AI_REVIEW_MANUAL_EVIDENCE does not exist: $manual_evidence" >&2
  exit 1
fi

# --- Parse agents ---------------------------------------------------------

agents_raw="${AI_REVIEW_AGENTS:-${AI_REVIEW_AGENT:-codex}}"
IFS=',' read -r -a agents_raw_arr <<< "$agents_raw"

agents=()
for agent in "${agents_raw_arr[@]}"; do
  cleaned="$(echo "$agent" | tr -d '[:space:]')"
  [[ -n "$cleaned" ]] && agents+=("$cleaned")
done

if [[ ${#agents[@]} -eq 0 ]]; then
  echo "No review agents configured. Set AI_REVIEW_AGENT or AI_REVIEW_AGENTS." >&2
  exit 1
fi

fix_agent="${AI_REVIEW_FIX_AGENT:-${agents[0]}}"

# --- CLI configuration ----------------------------------------------------

codex_cmd="${AI_REVIEW_CODEX_CMD:-codex}"
claude_cmd="${AI_REVIEW_CLAUDE_CMD:-claude}"
review_sandbox="${AI_REVIEW_REVIEW_SANDBOX:-read-only}"
fix_sandbox="${AI_REVIEW_FIX_SANDBOX:-workspace-write}"

codex_flags=()
[[ -n "${AI_REVIEW_CODEX_FLAGS:-}" ]] && read -r -a codex_flags <<< "$AI_REVIEW_CODEX_FLAGS"
claude_flags=()
[[ -n "${AI_REVIEW_CLAUDE_FLAGS:-}" ]] && read -r -a claude_flags <<< "$AI_REVIEW_CLAUDE_FLAGS"

agent_env=(
  env
  -u AI_REVIEW_VERIFY_CMD
  -u AI_REVIEW_VERIFY_ON_PASS
  -u AI_REVIEW_REVIEW_PROMPT
  -u AI_REVIEW_FIX_PROMPT
  -u AI_REVIEW_SPEC_PATH
  -u AI_REVIEW_MANUAL_EVIDENCE
  -u AI_REVIEW_PR_NUMBER
  -u AI_REVIEW_SCORE_THRESHOLD
  -u AI_REVIEW_NFR_CATEGORIES
  -u AI_REVIEW_REQUIRE_GATE_MARKERS
  -u AI_REVIEW_REQUIRE_SCORECARD_VALIDATION
  -u AI_REVIEW_REQUIRE_BMAD_EVIDENCE
  -u AI_REVIEW_REQUIRE_GITHUB_CI_CORROBORATION
  -u AI_REVIEW_REQUIRED_GATE_MARKERS
  -u AI_REVIEW_BASE_REF
  -u AI_REVIEW_BASE
  -u AI_REVIEW_MAX_ITER
  -u AI_REVIEW_AGENT
  -u AI_REVIEW_AGENTS
  -u AI_REVIEW_FIX_AGENT
  -u AI_REVIEW_LOG_DIR
  -u AI_REVIEW_CLAUDE_USE_BUILTIN_REVIEW
  -u AI_REVIEW_REVIEW_SANDBOX
  -u AI_REVIEW_FIX_SANDBOX
  -u AI_REVIEW_CODEX_CMD
  -u AI_REVIEW_CLAUDE_CMD
  -u AI_REVIEW_CODEX_FLAGS
  -u AI_REVIEW_CLAUDE_FLAGS
  -u BMAD_REVIEW_VERIFY_CMD
  -u BMAD_REVIEW_SPEC_PATH
  -u BMAD_REVIEW_MANUAL_EVIDENCE
  -u BMAD_REVIEW_PR
  -u BMAD_REVIEW_BASE
  -u BMAD_REVIEW_MAX_ITER
  -u BMAD_REVIEW_LOG_DIR
  -u BMAD_REVIEW_AGENTS
)

# --- Agent validation -----------------------------------------------------

ensure_codex_output_last_message() {
  if ! "$codex_cmd" exec --help 2>&1 | grep -q -- '--output-last-message'; then
    echo "Codex CLI is missing --output-last-message; update Codex CLI." >&2
    exit 1
  fi
}

require_command() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "$2 is required but not installed: $1" >&2
    return 1
  fi
}

validate_agent() {
  case "$1" in
    codex)
      require_command "$codex_cmd" "Codex CLI (codex)" || exit 1
      ensure_codex_output_last_message
      ;;
    claude)
      require_command "$claude_cmd" "Claude CLI (claude)" || exit 1
      ;;
    *)
      echo "Unknown agent: $1 (supported: codex, claude)" >&2
      exit 1
      ;;
  esac
}

for agent in "${agents[@]}"; do validate_agent "$agent"; done
validate_agent "$fix_agent"

# --- Base branch detection ------------------------------------------------

base_branch="${AI_REVIEW_BASE:-}"
base_branch_explicit=false
if [[ -n "$base_branch" ]]; then
  base_branch_explicit=true
fi
if [[ -z "$base_branch" ]] && command -v gh >/dev/null 2>&1; then
  base_branch=$(gh pr view --json baseRefName -q .baseRefName 2>/dev/null || true)
fi

if [[ -z "$base_branch" ]]; then
  base_branch="main"
  echo "Warning: Unable to detect PR base branch. Falling back to origin/$base_branch." >&2
fi

review_base=""
fetch_remote=""
fetch_branch=""

if git show-ref --verify --quiet "refs/heads/$base_branch"; then
  review_base="refs/heads/$base_branch"
elif git show-ref --verify --quiet "refs/remotes/$base_branch"; then
  review_base="refs/remotes/$base_branch"
elif [[ "$base_branch_explicit" == "true" && "$base_branch" =~ ^HEAD([~^][0-9]*)*$ ]]; then
  review_base="$base_branch"
elif [[ "$base_branch_explicit" == "true" && "$base_branch" =~ ^[0-9a-fA-F]{7,40}$ ]] \
  && git rev-parse --verify "${base_branch}^{commit}" >/dev/null 2>&1; then
  review_base="$base_branch"
elif [[ "$base_branch_explicit" == "true" && "$base_branch" == refs/* ]]; then
  review_base="$base_branch"
elif [[ "$base_branch" == */* ]] && git remote get-url "${base_branch%%/*}" >/dev/null 2>&1; then
  fetch_remote="${base_branch%%/*}"
  fetch_branch="${base_branch#*/}"
  review_base="refs/remotes/$base_branch"
else
  fetch_remote="origin"
  fetch_branch="$base_branch"
  review_base="refs/remotes/origin/$base_branch"
fi

# Ensure the base ref is available locally
if ! git rev-parse --verify "${review_base}^{commit}" >/dev/null 2>&1; then
  if [[ -z "$fetch_remote" && "$review_base" == refs/remotes/* ]]; then
    remote_branch="${review_base#refs/remotes/}"
    fetch_remote="${remote_branch%%/*}"
    fetch_branch="${remote_branch#*/}"
  fi

  if [[ -n "$fetch_remote" && -n "$fetch_branch" ]]; then
    echo "Fetching $fetch_remote/$fetch_branch..." >&2
    git fetch "$fetch_remote" "+refs/heads/$fetch_branch:refs/remotes/$fetch_remote/$fetch_branch" >/dev/null 2>&1 || true
  fi

  if ! git rev-parse --verify "${review_base}^{commit}" >/dev/null 2>&1; then
    echo "Error: Base branch $review_base is not available." >&2
    exit 1
  fi
fi

# --- Prompt builders ------------------------------------------------------
# Prompts use {BASE_REF} as a placeholder. Diffs are NOT embedded — agents
# read the codebase directly using their built-in tools (git, file reads,
# CLAUDE.md awareness), which gives better review quality than raw diffs.

apply_prompt_placeholders() {
  local template="$1"
  local spec_value manual_value pr_value

  spec_value="${spec_path:-NOT_PROVIDED}"
  manual_value="${manual_evidence:-NOT_PROVIDED}"
  pr_value="${pr_number:-AUTO_DETECT}"

  template="${template//\{BASE_REF\}/$(escape_prompt_replacement "$review_base")}"
  template="${template//\{SPEC_PATH\}/$(escape_prompt_replacement "$spec_value")}"
  template="${template//\{MANUAL_EVIDENCE\}/$(escape_prompt_replacement "$manual_value")}"
  template="${template//\{PR_NUMBER\}/$(escape_prompt_replacement "$pr_value")}"
  template="${template//\{SCORE_THRESHOLD\}/$(escape_prompt_replacement "$score_threshold")}"
  template="${template//\{NFR_CATEGORIES\}/$(escape_prompt_replacement "$nfr_categories")}"
  printf "%s\n" "$template"
}

escape_prompt_replacement() {
  local value="$1"

  value="${value//\\/\\\\}"
  value="${value//&/\\&}"
  printf "%s" "$value"
}

build_review_prompt() {
  local template
  template="$(cat "$review_prompt_file")"
  apply_prompt_placeholders "$template"
}

build_fix_prompt() {
  local review_log="$1"
  local ci_log="${2:-}"
  local template
  template="$(cat "$fix_prompt_file")"
  template="$(apply_prompt_placeholders "$template")"
  printf "%s\n\nREVIEW_OUTPUT:\n%s\n" "$template" "$(cat "$review_log")"
  if [[ -n "$ci_log" && -f "$ci_log" ]]; then
    printf "\nCI_OUTPUT:\n%s\n" "$(cat "$ci_log")"
  fi
}

# --- Status parsing -------------------------------------------------------
# For gated reviews, requires the first line to match exactly. For generic
# reviews, scans the first 10 lines and strips markdown fences/whitespace.
# Returns PASS, FAIL, or UNKNOWN. The caller decides how to handle UNKNOWN.

parse_status_line() {
  local file="$1"
  local line

  if [[ "$require_gate_markers" == "true" ]]; then
    if ! IFS= read -r line < "$file" && [[ -z "$line" ]]; then
      echo "UNKNOWN"
      return
    fi
    line="${line%$'\r'}"
    case "$line" in
      "STATUS: PASS") echo "PASS"; return ;;
      "STATUS: FAIL") echo "FAIL"; return ;;
    esac
    echo "UNKNOWN"
    return
  fi

  while IFS= read -r line || [[ -n "$line" ]]; do
    line="$(echo "$line" | sed 's/^[[:space:]`#*-]*//' | tr -d '\r')"
    case "$line" in
      "STATUS: PASS"*) echo "PASS"; return ;;
      "STATUS: FAIL"*) echo "FAIL"; return ;;
    esac
  done < <(head -n 10 "$file")
  echo "UNKNOWN"
}

review_has_required_gate_markers() {
  local file="$1"
  local marker
  IFS=',' read -r -a required_gate_markers <<< "$required_gate_markers_raw"

  for marker in "${required_gate_markers[@]}"; do
    marker="$(echo "$marker" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')"
    [[ -z "$marker" ]] && continue
    if ! grep -Fxq -- "$marker" < <(tr -d '\r' < "$file"); then
      echo "Warning: PASS output is missing required gate marker: $marker" >&2
      return 1
    fi
  done

  return 0
}

review_section_content() {
  local file="$1"
  local section="$2"

  awk -v section="$section" '
    {
      line = $0
      sub(/\r$/, "", line)
    }
    line ~ "^(#+[[:space:]]*)?" section ":" { in_section = 1; print; next }
    in_section && line ~ /^(#+[[:space:]]*)?(Requirement Scorecard|NFR Catalog Scorecard|Manual Test Evidence|QA Verification|GitHub Completion Gate|CI Gate|Required Fixes):/ { exit }
    in_section { print }
  ' "$file"
}

review_section_has_score() {
  local file="$1"
  local section="$2"
  local threshold_regex="$3"

  review_section_content "$file" "$section" | grep -Eq -- "$threshold_regex"
}

review_section_has_text_with_score() {
  local file="$1"
  local section="$2"
  local text="$3"
  local threshold_regex="$4"

  review_section_content "$file" "$section" \
    | awk -v category="$text" -v threshold_regex="$threshold_regex" '
      {
        line = $0
        sub(/\r$/, "", line)
        normalized = line
        sub(/^[[:space:]]*/, "", normalized)
        sub(/^[-*][[:space:]]*/, "", normalized)
        if (index(normalized, category ":") == 1 && line ~ threshold_regex) {
          found = 1
        }

        if (normalized ~ /^\|/) {
          table_row = normalized
          sub(/^\|/, "", table_row)
          sub(/\|[[:space:]]*$/, "", table_row)
          cell_count = split(table_row, cells, /\|/)
          for (i = 1; i <= cell_count; i++) {
            gsub(/^[[:space:]]+|[[:space:]]+$/, "", cells[i])
          }

          first_cell = cells[1]
          if (tolower(first_cell) == "category") {
            score_col = 0
            for (i = 1; i <= cell_count; i++) {
              if (tolower(cells[i]) == "score") {
                score_col = i
              }
            }
          } else if (first_cell == category && score_col > 0 && cells[score_col] ~ threshold_regex) {
            found = 1
          }
        }
      }
      END { exit found ? 0 : 1 }
    '
}

score_at_or_above_threshold_regex() {
  local scores=() score joined

  for ((score = score_threshold; score <= 5; score++)); do
    scores+=("$score")
  done

  local IFS='|'
  joined="${scores[*]}"
  echo "(^|[^0-9/])(${joined})/5([^0-9/]|$)"
}

review_has_scorecard_evidence() {
  local file="$1"
  local evidence_marker section score below_threshold_regex threshold_regex nfr_category
  local score_sections=(
    "Requirement Scorecard"
    "NFR Catalog Scorecard"
    "Manual Test Evidence"
    "QA Verification"
    "GitHub Completion Gate"
    "CI Gate"
  )
  local nfr_category_arr=()

  for evidence_marker in \
    "FR_NFR_MIN_SCORE: ${score_threshold}/5" \
    "NFR_CATALOG_MIN_SCORE: ${score_threshold}/5" \
    "GITHUB_COMPLETION_STATE: APPROVED" \
    "CI_CHECK_ROLLUP: PASSING"; do
    if ! grep -Fxq -- "$evidence_marker" < <(tr -d '\r' < "$file"); then
      echo "Warning: BMAD PASS output is missing required evidence marker: $evidence_marker" >&2
      return 1
    fi
  done

  for section in \
    "Requirement Scorecard:" \
    "NFR Catalog Scorecard:" \
    "Manual Test Evidence:" \
    "QA Verification:" \
    "GitHub Completion Gate:" \
    "CI Gate:"; do
    if ! grep -Fq -- "$section" "$file"; then
      echo "Warning: BMAD PASS output is missing required section: $section" >&2
      return 1
    fi
  done

  for section in "${score_sections[@]}"; do
    for ((score = 0; score < score_threshold; score++)); do
      below_threshold_regex="(^|[^0-9/])${score}/5([^0-9/]|$)"
      if review_section_has_score "$file" "$section" "$below_threshold_regex"; then
        echo "Warning: BMAD PASS output contains a score below ${score_threshold}/5 in $section." >&2
        return 1
      fi
    done
  done

  threshold_regex="$(score_at_or_above_threshold_regex)"
  for section in "${score_sections[@]}"; do
    if ! review_section_has_score "$file" "$section" "$threshold_regex"; then
      echo "Warning: BMAD PASS output lacks ${score_threshold}/5 evidence in $section." >&2
      return 1
    fi
  done

  IFS=',' read -r -a nfr_category_arr <<< "$nfr_categories"
  for nfr_category in "${nfr_category_arr[@]}"; do
    nfr_category="$(echo "$nfr_category" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')"
    [[ -z "$nfr_category" ]] && continue
    if ! review_section_has_text_with_score "$file" "NFR Catalog Scorecard" "$nfr_category" "$threshold_regex"; then
      echo "Warning: BMAD PASS output lacks ${score_threshold}/5 evidence for NFR category: $nfr_category." >&2
      return 1
    fi
  done

  return 0
}

review_has_github_ci_corroboration() {
  local pr_view_cmd=(gh pr view)
  local pr_checks_cmd=(gh pr checks)
  local pr_summary check_summary checks_status is_draft review_decision check_count check_blockers
  local pr_number_detected pr_url pr_head_oid local_head_oid
  local owner repo pr_path unresolved_threads query page_summary page_unresolved has_next cursor

  if ! command -v gh >/dev/null 2>&1; then
    echo "Warning: GitHub CI corroboration requires gh CLI." >&2
    return 1
  fi

  if [[ -n "$pr_number" ]]; then
    pr_view_cmd+=("$pr_number")
    pr_checks_cmd+=("$pr_number")
  fi

  if ! pr_summary="$("${pr_view_cmd[@]}" \
    --json number,isDraft,reviewDecision,url,headRefOid \
    --jq '[.number, .isDraft, .reviewDecision, .url, .headRefOid] | @tsv' 2>/dev/null)"; then
    echo "Warning: Unable to query GitHub PR state for BMAD gate." >&2
    return 1
  fi

  IFS=$'\t' read -r pr_number_detected is_draft review_decision pr_url pr_head_oid <<< "$pr_summary"

  if [[ -z "$pr_number_detected" || -z "$pr_url" || -z "$pr_head_oid" ]]; then
    echo "Warning: GitHub PR state is incomplete for BMAD gate." >&2
    return 1
  fi
  if ! local_head_oid="$(git rev-parse HEAD 2>/dev/null)"; then
    echo "Warning: Unable to resolve local HEAD for BMAD gate." >&2
    return 1
  fi
  if [[ "$local_head_oid" != "$pr_head_oid" ]]; then
    echo "Warning: GitHub PR head $pr_head_oid does not match local HEAD $local_head_oid." >&2
    return 1
  fi
  if [[ "$is_draft" == "true" ]]; then
    echo "Warning: GitHub PR is still draft." >&2
    return 1
  fi
  if [[ "$review_decision" != "APPROVED" ]]; then
    echo "Warning: GitHub review decision is not APPROVED: ${review_decision:-UNKNOWN}" >&2
    return 1
  fi

  if check_summary="$("${pr_checks_cmd[@]}" \
    --required \
    --json name,bucket \
    --jq '[length, ([.[] | select(.bucket != "pass") | .name] | join(","))] | @tsv' 2>/dev/null)"; then
    checks_status=0
  else
    checks_status=$?
  fi

  if ((checks_status == 0)); then
    IFS=$'\t' read -r check_count check_blockers <<< "$check_summary"
  else
    check_count=0
    check_blockers=""
  fi

  if [[ "$check_count" =~ ^[0-9]+$ ]] && ((check_count > 0)); then
    if [[ -n "$check_blockers" ]]; then
      echo "Warning: GitHub required PR checks are not fully passing: $check_blockers" >&2
      return 1
    fi
  elif check_summary="$("${pr_checks_cmd[@]}" \
    --json name,bucket \
    --jq '[length, ([.[] | select(.bucket != "pass") | .name] | join(","))] | @tsv' 2>/dev/null)"; then
    IFS=$'\t' read -r check_count check_blockers <<< "$check_summary"
    if ! [[ "$check_count" =~ ^[0-9]+$ ]] || ((check_count == 0)); then
      echo "Warning: GitHub PR check rollup is empty." >&2
      return 1
    fi
    if [[ -n "$check_blockers" ]]; then
      echo "Warning: GitHub PR checks are not fully passing: $check_blockers" >&2
      return 1
    fi
  else
    echo "Warning: Unable to query GitHub PR checks for BMAD gate." >&2
    return 1
  fi

  pr_path="${pr_url#*://}"
  pr_path="${pr_path#*/}"
  owner="${pr_path%%/*}"
  pr_path="${pr_path#*/}"
  repo="${pr_path%%/*}"
  if [[ -z "$owner" || -z "$repo" || "$owner" == "$pr_url" || "$repo" == "$pr_url" ]]; then
    echo "Warning: Unable to parse GitHub repository from PR URL: $pr_url" >&2
    return 1
  fi

  unresolved_threads=0
  cursor=""
  while :; do
    if [[ -n "$cursor" ]]; then
      query="query(\$owner:String!, \$repo:String!, \$number:Int!, \$cursor:String!) { repository(owner:\$owner, name:\$repo) { pullRequest(number:\$number) { reviewThreads(first:100, after:\$cursor) { pageInfo { hasNextPage endCursor } nodes { isResolved isOutdated } } } } }"
      if ! page_summary="$(gh api graphql \
        -f query="$query" \
        -f owner="$owner" \
        -f repo="$repo" \
        -F number="$pr_number_detected" \
        -f cursor="$cursor" \
        --jq '[([.data.repository.pullRequest.reviewThreads.nodes[]? | select(.isResolved == false and .isOutdated != true)] | length), (.data.repository.pullRequest.reviewThreads.pageInfo.hasNextPage | tostring), (.data.repository.pullRequest.reviewThreads.pageInfo.endCursor // "")] | @tsv' 2>/dev/null)"; then
        echo "Warning: Unable to query GitHub review threads for BMAD gate." >&2
        return 1
      fi
    else
      query="query(\$owner:String!, \$repo:String!, \$number:Int!) { repository(owner:\$owner, name:\$repo) { pullRequest(number:\$number) { reviewThreads(first:100) { pageInfo { hasNextPage endCursor } nodes { isResolved isOutdated } } } } }"
      if ! page_summary="$(gh api graphql \
        -f query="$query" \
        -f owner="$owner" \
        -f repo="$repo" \
        -F number="$pr_number_detected" \
        --jq '[([.data.repository.pullRequest.reviewThreads.nodes[]? | select(.isResolved == false and .isOutdated != true)] | length), (.data.repository.pullRequest.reviewThreads.pageInfo.hasNextPage | tostring), (.data.repository.pullRequest.reviewThreads.pageInfo.endCursor // "")] | @tsv' 2>/dev/null)"; then
        echo "Warning: Unable to query GitHub review threads for BMAD gate." >&2
        return 1
      fi
    fi

    IFS=$'\t' read -r page_unresolved has_next cursor <<< "$page_summary"
    unresolved_threads=$((unresolved_threads + page_unresolved))

    if [[ "$has_next" != "true" ]]; then
      break
    fi
    if [[ -z "$cursor" ]]; then
      echo "Warning: GitHub review threads pagination did not return a cursor." >&2
      return 1
    fi
  done

  if [[ "$unresolved_threads" != "0" ]]; then
    echo "Warning: GitHub PR has unresolved review threads: $unresolved_threads" >&2
    return 1
  fi

  return 0
}

run_verify() {
  local output_file="$1"

  bash -c "$verify_cmd" >"$output_file" 2>&1
}

# --- Agent runners --------------------------------------------------------

run_review() {
  local agent="$1"
  local output_file="$2"
  local prompt

  case "$agent" in
    codex)
      prompt="$(build_review_prompt)"
      printf "%s" "$prompt" \
        | "${agent_env[@]}" "$codex_cmd" exec \
            ${codex_flags[@]+"${codex_flags[@]}"} \
            --sandbox "$review_sandbox" \
            --output-last-message "$output_file" - \
          >"${output_file}.log" 2>&1
      ;;
    claude)
      prompt="$(build_review_prompt)"
      if [[ "${AI_REVIEW_CLAUDE_USE_BUILTIN_REVIEW:-true}" == "true" \
        && "$review_prompt_file" == "scripts/ai-review-prompts/review.md" \
        && "$require_gate_markers" != "true" \
        && -z "$spec_path" ]]; then
        "${agent_env[@]}" "$claude_cmd" -p "/review" \
          ${claude_flags[@]+"${claude_flags[@]}"} \
          --append-system-prompt "After completing the review, your FIRST line of output MUST be exactly STATUS: PASS or STATUS: FAIL. Then list any issues found." \
          --output-format text \
          >"$output_file" 2>"${output_file}.log"
      else
        "${agent_env[@]}" "$claude_cmd" -p "$prompt" \
          ${claude_flags[@]+"${claude_flags[@]}"} \
          --append-system-prompt "After completing the review, your FIRST line of output MUST be exactly STATUS: PASS or STATUS: FAIL." \
          --output-format text \
          >"$output_file" 2>"${output_file}.log"
      fi
      ;;
  esac
}

run_fix() {
  local agent="$1"
  local output_file="$2"
  local review_log="$3"
  local ci_log="${4:-}"
  local prompt
  prompt="$(build_fix_prompt "$review_log" "$ci_log")"

  case "$agent" in
    codex)
      printf "%s" "$prompt" \
        | "${agent_env[@]}" "$codex_cmd" exec \
            ${codex_flags[@]+"${codex_flags[@]}"} \
            --sandbox "$fix_sandbox" \
            --output-last-message "$output_file" - \
          >"${output_file}.log" 2>&1
      ;;
    claude)
      "${agent_env[@]}" "$claude_cmd" -p "$prompt" \
        ${claude_flags[@]+"${claude_flags[@]}"} \
        --output-format text \
        >"$output_file" 2>"${output_file}.log"
      ;;
  esac
}

# --- Main loop ------------------------------------------------------------

iter=1
ci_log=""
last_verify_ok=true

if [[ "$require_github_ci_corroboration" == "true" ]] \
  && ! review_has_github_ci_corroboration; then
  echo "GitHub corroboration failed before AI review. Fix PR review/check state and rerun." >&2
  exit 1
fi

while :; do
  if [[ "$max_iter" -ne 0 && "$iter" -gt "$max_iter" ]]; then
    echo "Reached AI_REVIEW_MAX_ITER=$max_iter without PASS." >&2
    exit 1
  fi

  all_pass=true
  fail_log=""

  for agent in "${agents[@]}"; do
    ts=$(date +%Y%m%d_%H%M%S)
    review_log="$log_dir/review-${agent}-iter${iter}-${ts}.md"
    gate_failure_reason=""
    run_review "$agent" "$review_log"
    cp "$review_log" "$log_dir/review-latest-${agent}.md"

    status=$(parse_status_line "$review_log")
    if [[ "$status" == "UNKNOWN" ]]; then
      echo "Warning: Agent $agent did not produce STATUS line; treating as FAIL." >&2
      status="FAIL"
    fi

    if [[ "$status" == "PASS" && "$require_gate_markers" == "true" ]]; then
      if ! review_has_required_gate_markers "$review_log"; then
        gate_failure_reason="missing required gate markers"
        status="FAIL"
      elif [[ "$require_scorecard_validation" == "true" ]] \
        && ! review_has_scorecard_evidence "$review_log"; then
        gate_failure_reason="missing or invalid scorecard evidence"
        status="FAIL"
      elif [[ "$require_github_ci_corroboration" == "true" ]] \
        && ! review_has_github_ci_corroboration; then
        gate_failure_reason="GitHub corroboration failed"
        status="FAIL"
      fi
    fi

    if [[ "$status" == "FAIL" ]]; then
      all_pass=false
      fail_log="${fail_log:-$log_dir/review-fail-iter${iter}-${ts}.md}"
      {
        echo "=== Agent: $agent ==="
        [[ -n "$gate_failure_reason" ]] && echo "Gate validation failure: $gate_failure_reason"
        cat "$review_log"
        echo
      } >> "$fail_log"
    fi
  done

  cp "${fail_log:-$log_dir/review-latest-${agents[-1]}.md}" \
    "$log_dir/review-latest.md" 2>/dev/null || true

  if [[ "$all_pass" == true ]]; then
    if [[ "$verify_on_pass" == "true" ]]; then
      verify_ts=$(date +%Y%m%d_%H%M%S)
      ci_log="$log_dir/ci-pass-iter${iter}-${verify_ts}.log"
      if ! run_verify "$ci_log"; then
        echo "Verification failed after AI review PASS (see $ci_log)." >&2
        exit 1
      fi
      last_verify_ok=true
    fi
    if [[ "$last_verify_ok" != true ]]; then
      echo "AI review PASS, but last verification failed. Fix verification failures first." >&2
      exit 1
    fi
    echo "AI review PASS." >&2
    exit 0
  fi

  # --- Fix phase ---
  fix_ts=$(date +%Y%m%d_%H%M%S)
  fix_log="$log_dir/fix-${fix_agent}-iter${iter}-${fix_ts}.md"
  run_fix "$fix_agent" "$fix_log" "$fail_log" "$ci_log"
  cp "$fix_log" "$log_dir/fix-latest.md"

  # --- Verify phase ---
  ci_log="$log_dir/ci-iter${iter}-${fix_ts}.log"
  if ! run_verify "$ci_log"; then
    echo "Warning: Verification failed (see $ci_log)." >&2
    last_verify_ok=false
  else
    last_verify_ok=true
  fi

  iter=$((iter + 1))
done
