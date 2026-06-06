#!/usr/bin/env bash
set -Eeuo pipefail

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
github_status_enabled="${AI_REVIEW_GITHUB_STATUS:-true}"
github_status_context="${AI_REVIEW_GITHUB_STATUS_CONTEXT:-BMAD FR/NFR Review Gate}"
github_pr_number="${AI_REVIEW_GITHUB_PR:-${AI_REVIEW_PR_NUMBER:-}}"
default_github_required_checks="PHPUnit,Behat,K6,Infection,Schemathesis,Spectral Lint,Openapi-diff@GraphQL spec backward comparability,openapi-diff@openapi-diff,Psalm,PHP Insights checks,Deptrac,lint,symfony-checks,Run Bats Core Tests,Cache Integration Tests,Memory leak tests,test-and-report,qlty check,qlty fmt,CodeRabbit,security/snyk (Kravalg)"
github_required_checks_raw="${AI_REVIEW_REQUIRED_CHECKS:-$default_github_required_checks}"
github_repo=""
github_head_sha=""
github_pr_url=""
current_head_github_checks_result="pass"

codex_flags=()
[[ -n "${AI_REVIEW_CODEX_FLAGS:-}" ]] && read -r -a codex_flags <<< "$AI_REVIEW_CODEX_FLAGS"
claude_flags=()
[[ -n "${AI_REVIEW_CLAUDE_FLAGS:-}" ]] && read -r -a claude_flags <<< "$AI_REVIEW_CLAUDE_FLAGS"

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
if [[ -z "$base_branch" ]] && command -v gh >/dev/null 2>&1; then
  if [[ -n "$github_pr_number" ]]; then
    base_repo="$(gh repo view --json nameWithOwner --jq .nameWithOwner 2>/dev/null || true)"
    if [[ -n "$base_repo" ]]; then
      base_branch=$(gh pr view "$github_pr_number" --repo "$base_repo" --json baseRefName -q .baseRefName 2>/dev/null || true)
    fi
  else
    base_branch=$(gh pr view --json baseRefName -q .baseRefName 2>/dev/null || true)
  fi
fi

if [[ -z "$base_branch" ]]; then
  base_branch="main"
  echo "Warning: Unable to detect PR base branch. Falling back to origin/$base_branch." >&2
fi

review_base="$base_branch"
if git rev-parse --verify "$base_branch" >/dev/null 2>&1; then
  review_base="$base_branch"
elif [[ "$base_branch" != */* ]]; then
  if git show-ref --verify --quiet "refs/heads/$base_branch"; then
    review_base="$base_branch"
  else
    review_base="origin/$base_branch"
  fi
fi

# Ensure the base ref is available locally
if ! git rev-parse --verify "$review_base" >/dev/null 2>&1; then
  remote="origin"
  branch="$review_base"
  if [[ "$review_base" == */* ]]; then
    remote="${review_base%%/*}"
    branch="${review_base#*/}"
  fi
  echo "Fetching $remote/$branch..." >&2
  git fetch "$remote" "$branch" >/dev/null 2>&1 || true
  if ! git rev-parse --verify "$review_base" >/dev/null 2>&1; then
    echo "Error: Base branch $review_base is not available." >&2
    exit 1
  fi
fi

# --- GitHub status reporting ---------------------------------------------

detect_github_pr() {
  if [[ "$github_status_enabled" != "true" ]]; then
    return
  fi

  if ! command -v gh >/dev/null 2>&1; then
    echo "Warning: gh is unavailable; BMAD GitHub status updates disabled." >&2
    github_status_enabled=false
    return
  fi

  github_repo="$(gh repo view --json nameWithOwner --jq .nameWithOwner 2>/dev/null || true)"
  if [[ -n "$github_repo" && -n "$github_pr_number" ]]; then
    github_head_sha="$(gh pr view "$github_pr_number" --repo "$github_repo" --json headRefOid --jq .headRefOid 2>/dev/null || true)"
    github_pr_url="$(gh pr view "$github_pr_number" --repo "$github_repo" --json url --jq .url 2>/dev/null || true)"
  else
    github_head_sha="$(gh pr view --json headRefOid --jq .headRefOid 2>/dev/null || true)"
    github_pr_url="$(gh pr view --json url --jq .url 2>/dev/null || true)"
  fi

  if [[ -z "$github_repo" || -z "$github_head_sha" || -z "$github_pr_url" ]]; then
    echo "Warning: Unable to detect GitHub PR; BMAD status updates disabled." >&2
    github_status_enabled=false
  fi
}

post_github_status() {
  local state="$1"
  local description="$2"

  if [[ "$github_status_enabled" != "true" ]]; then
    return
  fi

  if ! gh api "repos/$github_repo/statuses/$github_head_sha" \
    -f state="$state" \
    -f context="$github_status_context" \
    -f description="$description" \
    -f target_url="$github_pr_url" >/dev/null; then
    echo "Warning: Failed to post BMAD GitHub status: $state" >&2
  fi
}

refresh_github_pr_head() {
  local current_head_sha
  local current_pr_url

  if [[ "$github_status_enabled" != "true" ]]; then
    return 0
  fi

  if [[ -n "$github_repo" && -n "$github_pr_number" ]]; then
    current_head_sha="$(gh pr view "$github_pr_number" --repo "$github_repo" --json headRefOid --jq .headRefOid 2>/dev/null || true)"
    current_pr_url="$(gh pr view "$github_pr_number" --repo "$github_repo" --json url --jq .url 2>/dev/null || true)"
  else
    current_head_sha="$(gh pr view --json headRefOid --jq .headRefOid 2>/dev/null || true)"
    current_pr_url="$(gh pr view --json url --jq .url 2>/dev/null || true)"
  fi

  if [[ -z "$current_head_sha" || -z "$current_pr_url" ]]; then
    return 1
  fi

  github_head_sha="$current_head_sha"
  github_pr_url="$current_pr_url"
}

github_pr_check_rows() {
  local pr_args=()

  if [[ -n "$github_pr_number" ]]; then
    pr_args+=("$github_pr_number")
  fi

  if [[ -n "$github_repo" ]]; then
    pr_args+=(--repo "$github_repo")
  fi

  gh pr checks "${pr_args[@]}" \
    --json name,workflow,state,bucket \
    --jq '.[] | [.name, (.workflow // "-"), .state, .bucket] | @tsv'
}

normalize_github_check_value() {
  echo "$1" | sed -E 's/^[[:space:]]+//; s/[[:space:]]+$//' | tr '[:upper:]' '[:lower:]'
}

github_check_is_successful() {
  local state="$1"
  local bucket="$2"
  local normalized_state
  local normalized_bucket

  normalized_state="$(echo "$state" | tr '[:lower:]' '[:upper:]')"
  normalized_bucket="$(normalize_github_check_value "$bucket")"

  [[ "$normalized_bucket" == "pass" || "$normalized_state" == "PASS" || "$normalized_state" == "SUCCESS" ]]
}

github_check_is_pending() {
  local state="$1"
  local bucket="$2"
  local normalized_state
  local normalized_bucket

  normalized_state="$(echo "$state" | tr '[:lower:]' '[:upper:]')"
  normalized_bucket="$(normalize_github_check_value "$bucket")"

  [[ "$normalized_bucket" == "pending" || "$normalized_state" =~ ^(PENDING|QUEUED|IN_PROGRESS|WAITING|REQUESTED|EXPECTED)$ ]]
}

github_required_check_was_seen() {
  local required_check="$1"
  local checks="$2"
  local required_name
  local required_workflow=""
  local normalized_required_name
  local normalized_required_workflow=""
  local check_name
  local check_workflow
  local check_state
  local check_bucket
  local normalized_check_name
  local normalized_check_workflow

  if [[ "$required_check" == *@* ]]; then
    required_name="${required_check%%@*}"
    required_workflow="${required_check#*@}"
    normalized_required_workflow="$(normalize_github_check_value "$required_workflow")"
  else
    required_name="$required_check"
  fi

  normalized_required_name="$(normalize_github_check_value "$required_name")"

  while IFS=$'\t' read -r check_name check_workflow check_state check_bucket; do
    [[ -z "$check_name" ]] && continue
    normalized_check_name="$(normalize_github_check_value "$check_name")"
    normalized_check_workflow="$(normalize_github_check_value "$check_workflow")"
    [[ "$normalized_check_workflow" == "-" ]] && normalized_check_workflow=""

    if [[ "$normalized_check_name" == "$normalized_required_name" ]] \
      && { [[ -z "$normalized_required_workflow" ]] || [[ "$normalized_check_workflow" == "$normalized_required_workflow" ]]; }; then
      return 0
    fi
  done <<< "$checks"

  return 1
}

validate_current_head_github_checks() {
  local checks
  local check_name
  local check_workflow
  local check_state
  local check_bucket
  local ok=true
  local any_pending_check=false
  local required_check
  local normalized_github_status_context
  local -a required_checks=()

  if [[ "$github_status_enabled" != "true" ]]; then
    return 0
  fi

  current_head_github_checks_result="pass"

  if ! checks="$(github_pr_check_rows 2>/dev/null)"; then
    echo "Current-head GitHub checks could not be retrieved." >&2
    current_head_github_checks_result="pending"
    return 1
  fi

  if [[ -z "$checks" ]]; then
    echo "Current-head GitHub checks are missing." >&2
    current_head_github_checks_result="pending"
    return 1
  fi

  normalized_github_status_context="$(normalize_github_check_value "$github_status_context")"

  while IFS=$'\t' read -r check_name check_workflow check_state check_bucket; do
    [[ -z "$check_name" ]] && continue
    if [[ "$(normalize_github_check_value "$check_name")" == "$normalized_github_status_context" ]]; then
      continue
    fi

    if ! github_check_is_successful "$check_state" "$check_bucket"; then
      echo "Current-head GitHub check is not passing: $check_name ($check_state)." >&2
      ok=false
      if github_check_is_pending "$check_state" "$check_bucket"; then
        any_pending_check=true
        [[ "$current_head_github_checks_result" != "fail" ]] && current_head_github_checks_result="pending"
      else
        current_head_github_checks_result="fail"
      fi
    fi
  done <<< "$checks"

  if [[ -n "$github_required_checks_raw" ]]; then
    IFS=',' read -r -a required_checks <<< "$github_required_checks_raw"
    for required_check in "${required_checks[@]}"; do
      required_check="$(echo "$required_check" | sed -E 's/^[[:space:]]+//; s/[[:space:]]+$//')"
      [[ -z "$required_check" ]] && continue

      if ! github_required_check_was_seen "$required_check" "$checks"; then
        echo "Current-head GitHub check is missing: $required_check." >&2
        ok=false
        if [[ "$any_pending_check" == true ]]; then
          [[ "$current_head_github_checks_result" != "fail" ]] && current_head_github_checks_result="pending"
        else
          current_head_github_checks_result="fail"
        fi
      fi
    done
  fi

  [[ "$ok" == true ]]
}

fail_unexpected() {
  local exit_code=$?

  trap - ERR
  post_github_status "failure" "Strict BMAD review loop failed before completion."
  exit "$exit_code"
}

detect_github_pr
post_github_status "pending" "Strict BMAD FR/NFR review running; human approvals are not gate inputs."
trap fail_unexpected ERR

# --- Prompt builders ------------------------------------------------------
# Prompts use {BASE_REF} as a placeholder. Diffs are NOT embedded — agents
# read the codebase directly using their built-in tools (git, file reads,
# CLAUDE.md awareness), which gives better review quality than raw diffs.

build_review_prompt() {
  local template
  template="$(cat "$review_prompt_file")"
  echo "${template//\{BASE_REF\}/$review_base}"
}

build_fix_prompt() {
  local review_log="$1"
  local ci_log="${2:-}"
  local template
  template="$(cat "$fix_prompt_file")"
  template="${template//\{BASE_REF\}/$review_base}"
  printf "%s\n\nREVIEW_OUTPUT:\n%s\n" "$template" "$(cat "$review_log")"
  if [[ -n "$ci_log" && -f "$ci_log" ]]; then
    printf "\nCI_OUTPUT:\n%s\n" "$(cat "$ci_log")"
  fi
}

# --- Status parsing -------------------------------------------------------
# Scans the first 10 lines, strips markdown fences/whitespace. Returns
# PASS, FAIL, or UNKNOWN. The caller decides how to handle UNKNOWN.

parse_status_line() {
  local file="$1"
  local line
  while IFS= read -r line; do
    line="$(echo "$line" | sed 's/^[[:space:]`#*-]*//' | tr -d '\r')"
    case "$line" in
      "STATUS: PASS"*) echo "PASS"; return ;;
      "STATUS: FAIL"*) echo "FAIL"; return ;;
    esac
  done < <(head -n 10 "$file")
  echo "UNKNOWN"
}

parse_marker_value() {
  local file="$1"
  local marker="$2"
  local line

  line="$(grep -E "^[[:space:]]*${marker}:" "$file" | head -n 1 || true)"
  if [[ -z "$line" ]]; then
    return 1
  fi

  echo "$line" \
    | sed -E "s/^[[:space:]]*${marker}:[[:space:]]*//; s/[[:space:]]+$//" \
    | tr -d '\r'
}

strict_marker_state() {
  local file="$1"
  local marker
  local value
  local state="PASS"

  local required_pass_markers=(
    STATUS
    FR_NFR_SCORECARD
    TEST_CASE_MATRIX
    AUTO_TEST_COVERAGE
    CI_COVERAGE
    FLAKY_TEST_RISK
    SYSTEM_QUALITY_ATTRIBUTES_SCORECARD
    WHOLE_CODEBASE_IMPACT
    GRAPH_IMPACT_CONTEXT
    GITHUB_COMPLETION_GATE
  )

  for marker in "${required_pass_markers[@]}"; do
    if ! value="$(parse_marker_value "$file" "$marker")"; then
      echo "Strict marker missing: $marker" >&2
      state="FAIL"
      continue
    fi

    case "$value" in
      PASS)
        ;;
      PENDING_REMOTE)
        if [[ "$marker" == "CI_COVERAGE" ]]; then
          echo "Strict marker $marker is $value; waiting for remote CI." >&2
          if [[ "$state" == "PASS" ]]; then
            state="PENDING"
          fi
        else
          echo "Strict marker $marker is $value, expected PASS." >&2
          state="FAIL"
        fi
        ;;
      PENDING_REMOTE_CI)
        if [[ "$marker" == "GITHUB_COMPLETION_GATE" ]]; then
          echo "Strict marker $marker is $value; waiting for remote CI." >&2
          if [[ "$state" == "PASS" ]]; then
            state="PENDING"
          fi
        else
          echo "Strict marker $marker is $value, expected PASS." >&2
          state="FAIL"
        fi
        ;;
      *)
        echo "Strict marker $marker is $value, expected PASS." >&2
        state="FAIL"
        ;;
    esac
  done

  echo "$state"
}

validate_strict_markers() {
  [[ "$(strict_marker_state "$1")" == "PASS" ]]
}

report_has_required_content() {
  local file="$1"
  local attribute
  local field
  local literal
  local pattern
  local ok=true
  local quality_row
  local -a quality_fields

  local required_literals=(
    "Reviewed SHA and PR URL:"
    "Findings table:"
    "| Severity | File/Line | Requirement/NFR | Evidence | Fix | Verification |"
    "Test evidence table:"
    "| Suite/Command | Evidence | Result |"
    "Current-head GitHub check summary:"
    "| Check | State | Evidence |"
    "Flaky-test risk review:"
    "System quality attribute scorecard:"
    "| Attribute | Score | Evidence | Improvement/Fix | Blocker |"
    "Graph/code-search whole-codebase impact evidence:"
  )

  local required_patterns=(
    "FR/NFR counts: FRs: [0-9]+; NFRs: [0-9]+; generated cases: [0-9]+; covered cases: [0-9]+; uncovered cases: [0-9]+; findings fixed: [0-9]+\\."
  )

  local required_quality_attributes=(
    accountability
    accuracy
    correctness
    auditability
    confidentiality
    integrity
    availability
    compatibility
    deployability
    efficiency
    interoperability
    reliability
    resilience
    responsiveness
    maintainability
    observability
    operability
    performance
    safety
    scalability
    securability
    "standards compliance"
    survivability
    testability
    traceability
    vulnerability
  )

  for literal in "${required_literals[@]}"; do
    if ! grep -Fiq "$literal" "$file"; then
      echo "Strict review report missing required content: $literal" >&2
      ok=false
    fi
  done

  for pattern in "${required_patterns[@]}"; do
    if ! grep -Eiq "$pattern" "$file"; then
      echo "Strict review report missing required content pattern: $pattern" >&2
      ok=false
    fi
  done

  for attribute in "${required_quality_attributes[@]}"; do
    pattern="\\|[[:space:]]*${attribute}[[:space:]]*\\|[[:space:]]*(0|1|2|3|4|5|N/A)[[:space:]]*\\|[^|]+\\|[^|]+\\|[^|]+\\|"
    quality_row="$(grep -Ei "$pattern" "$file" | head -n 1 || true)"
    if [[ -z "$quality_row" ]]; then
      echo "Strict review report missing quality attribute row: $attribute" >&2
      ok=false
      continue
    fi

    IFS='|' read -r -a quality_fields <<< "$quality_row"
    for field in 3 4 5; do
      if [[ -z "${quality_fields[$field]:-}" || ! "${quality_fields[$field]}" =~ [^[:space:]] ]]; then
        echo "Strict review report quality attribute row has an empty required field: $attribute" >&2
        ok=false
      fi
    done
  done

  pattern="\\|[[:space:]]*all remaining listed attributes[[:space:]]*\\|[[:space:]]*N/A[[:space:]]*\\|[^|]+\\|[^|]+\\|[^|]+\\|"
  quality_row="$(grep -Ei "$pattern" "$file" | head -n 1 || true)"
  if [[ -z "$quality_row" ]]; then
    echo "Strict review report missing grouped N/A row for remaining quality attributes." >&2
    ok=false
  else
    IFS='|' read -r -a quality_fields <<< "$quality_row"
    for field in 3 4 5; do
      if [[ -z "${quality_fields[$field]:-}" || ! "${quality_fields[$field]}" =~ [^[:space:]] ]]; then
        echo "Strict review report grouped N/A row has an empty required field." >&2
        ok=false
      fi
    done
  fi

  if ! quality_scorecard_is_consistent "$file"; then
    ok=false
  fi

  if ! findings_table_has_required_locations "$file"; then
    ok=false
  fi

  [[ "$ok" == true ]]
}

quality_scorecard_is_consistent() {
  local file="$1"
  local line
  local attribute
  local blocker
  local improvement
  local normalized_blocker
  local normalized_improvement
  local score
  local normalized_score
  local normalized_attribute
  local in_scorecard=false
  local ok=true
  local table_line
  local -a quality_fields

  while IFS= read -r line; do
    if [[ "$line" == "System quality attribute scorecard:"* ]]; then
      in_scorecard=true
      continue
    fi

    if [[ "$in_scorecard" != true ]]; then
      continue
    fi

    if [[ "$line" == "Graph/code-search whole-codebase impact evidence:"* ]]; then
      break
    fi

    table_line="$(echo "$line" | sed -E 's/^[[:space:]]+//')"
    [[ "$table_line" != \|* ]] && continue
    [[ "$table_line" == *"| Attribute |"* ]] && continue
    [[ "$table_line" =~ ^\|[[:space:]-]+\| ]] && continue

    IFS='|' read -r -a quality_fields <<< "$table_line"
    attribute="$(echo "${quality_fields[1]:-}" | sed -E 's/^[[:space:]]+//; s/[[:space:]]+$//')"
    score="$(echo "${quality_fields[2]:-}" | sed -E 's/^[[:space:]]+//; s/[[:space:]]+$//')"
    improvement="$(echo "${quality_fields[4]:-}" | sed -E 's/^[[:space:]]+//; s/[[:space:]]+$//')"
    blocker="$(echo "${quality_fields[5]:-}" | sed -E 's/^[[:space:]]+//; s/[[:space:]]+$//')"
    normalized_attribute="$(echo "$attribute" | tr '[:upper:]' '[:lower:]')"
    normalized_improvement="$(echo "$improvement" | tr '[:upper:]' '[:lower:]')"
    normalized_blocker="$(echo "$blocker" | tr '[:upper:]' '[:lower:]')"
    normalized_score="$(echo "$score" | tr '[:lower:]' '[:upper:]')"

    [[ -z "$attribute" || "$normalized_attribute" == "all remaining listed attributes" ]] && continue

    case "$normalized_score" in
      0|1|2|3)
        echo "Strict review report quality attribute score below pass threshold: $attribute ($score)." >&2
        ok=false
        ;;
      4)
        if quality_attribute_requires_security_max_score "$normalized_attribute"; then
          if ! quality_security_exception_is_tracked "$normalized_improvement" "$normalized_blocker"; then
            echo "Strict review report security quality attribute below strict threshold: $attribute ($score)." >&2
            ok=false
          fi
        elif ! quality_improvement_is_substantive "$normalized_improvement"; then
          echo "Strict review report quality attribute score 4 lacks a concrete improvement statement: $attribute." >&2
          ok=false
        fi
        ;;
      5)
        ;;
      N/A)
        if quality_attribute_requires_security_max_score "$normalized_attribute"; then
          if ! quality_security_exception_is_tracked "$normalized_improvement" "$normalized_blocker"; then
            echo "Strict review report security quality attribute cannot be N/A in a passing report: $attribute." >&2
            ok=false
          fi
        fi
        ;;
    esac
  done < "$file"

  [[ "$ok" == true ]]
}

quality_improvement_is_substantive() {
  local normalized_improvement="$1"

  case "$normalized_improvement" in
    ""|"none"|"none."|"n/a"|"n/a."|"na"|"na."|"no"|"no.")
      return 1
      ;;
  esac

  [[ "$normalized_improvement" =~ (improv|fix|tighten|add|implement|track|no[[:space:]-]+practical|not[[:space:]-]+practical) ]]
}

quality_security_exception_is_tracked() {
  local normalized_improvement="$1"
  local normalized_blocker="$2"
  local normalized_text="$normalized_improvement $normalized_blocker"

  [[ "$normalized_text" =~ (out[[:space:]-]+of[[:space:]-]+scope|outside[[:space:]-]+pr[[:space:]-]+scope) ]] \
    && [[ "$normalized_text" =~ track ]] \
    && [[ "$normalized_text" =~ owner ]] \
    && [[ "$normalized_text" =~ risk ]]
}

quality_attribute_requires_security_max_score() {
  case "$1" in
    accountability|auditability|availability|confidentiality|integrity|safety|securability|survivability|vulnerability)
      return 0
      ;;
  esac

  return 1
}

findings_table_has_required_locations() {
  local file="$1"
  local line
  local in_findings=false
  local location
  local normalized_location
  local normalized_line
  local normalized_severity
  local ok=true
  local severity
  local table_line

  while IFS= read -r line; do
    if [[ "$line" == "Findings table:"* ]]; then
      in_findings=true
      continue
    fi

    if [[ "$in_findings" != true ]]; then
      continue
    fi

    if [[ "$line" == "Test evidence table:"* ]]; then
      break
    fi

    table_line="$(echo "$line" | sed -E 's/^[[:space:]]+//')"
    [[ "$table_line" != \|* ]] && continue
    [[ "$table_line" == *"| Severity |"* ]] && continue
    [[ "$table_line" =~ ^\|[[:space:]-]+\| ]] && continue

    severity="$(echo "$table_line" | cut -d '|' -f 2 | sed -E 's/^[[:space:]]+//; s/[[:space:]]+$//')"
    normalized_severity="$(echo "$severity" | tr '[:upper:]' '[:lower:]')"
    normalized_line="$(echo "$table_line" | tr '[:upper:]' '[:lower:]')"

    case "$normalized_severity" in
      ""|"none"|"n/a"|"na")
        continue
        ;;
      "pending remote"|"pending remote ci"|"remote"|"remote ci"|"remote check")
        if [[ "$normalized_line" == *"remote"* && "$normalized_line" =~ (ci|check|current-head) ]]; then
          continue
        fi
        ;;
    esac

    location="$(echo "$table_line" | cut -d '|' -f 3 | sed -E 's/^[[:space:]]+//; s/[[:space:]]+$//')"
    normalized_location="$(echo "$location" | tr '[:upper:]' '[:lower:]')"
    case "$normalized_location" in
      ""|"none"|"n/a"|"na")
        echo "Strict review report actionable finding is missing File/Line evidence." >&2
        ok=false
        ;;
    esac
  done < "$file"

  [[ "$ok" == true ]]
}

report_has_actionable_findings() {
  local file="$1"
  local line
  local in_findings=false
  local severity
  local normalized_severity
  local normalized_line
  local table_line

  while IFS= read -r line; do
    if [[ "$line" == "Findings table:"* ]]; then
      in_findings=true
      continue
    fi

    if [[ "$in_findings" != true ]]; then
      continue
    fi

    if [[ "$line" == "Test evidence table:"* ]]; then
      break
    fi

    table_line="$(echo "$line" | sed -E 's/^[[:space:]]+//')"
    [[ "$table_line" != \|* ]] && continue
    [[ "$table_line" == *"| Severity |"* ]] && continue
    [[ "$table_line" =~ ^\|[[:space:]-]+\| ]] && continue

    severity="$(echo "$table_line" | cut -d '|' -f 2 | sed -E 's/^[[:space:]]+//; s/[[:space:]]+$//')"
    normalized_severity="$(echo "$severity" | tr '[:upper:]' '[:lower:]')"
    normalized_line="$(echo "$table_line" | tr '[:upper:]' '[:lower:]')"

    case "$normalized_severity" in
      ""|"none"|"n/a"|"na")
        continue
        ;;
      "pending remote"|"pending remote ci"|"remote"|"remote ci"|"remote check")
        if [[ "$normalized_line" == *"remote"* && "$normalized_line" =~ (ci|check|current-head) ]]; then
          continue
        fi
        ;;
    esac

    return 0
  done < "$file"

  return 1
}

strict_failure_is_remote_pending() {
  local file="$1"
  local marker
  local value
  local has_pending=false
  local ok=true

  local required_markers=(
    STATUS
    FR_NFR_SCORECARD
    TEST_CASE_MATRIX
    AUTO_TEST_COVERAGE
    CI_COVERAGE
    FLAKY_TEST_RISK
    SYSTEM_QUALITY_ATTRIBUTES_SCORECARD
    WHOLE_CODEBASE_IMPACT
    GRAPH_IMPACT_CONTEXT
    GITHUB_COMPLETION_GATE
  )

  for marker in "${required_markers[@]}"; do
    if ! value="$(parse_marker_value "$file" "$marker")"; then
      ok=false
      continue
    fi

    case "$marker:$value" in
      STATUS:FAIL|STATUS:PASS)
        ;;
      CI_COVERAGE:PENDING_REMOTE|GITHUB_COMPLETION_GATE:PENDING_REMOTE_CI)
        has_pending=true
        ;;
      *:PASS)
        ;;
      *)
        ok=false
        ;;
    esac
  done

  if [[ "$ok" == true && "$has_pending" == true ]]; then
    if report_has_actionable_findings "$file"; then
      echo "Remote-CI pending report contains actionable local findings." >&2
      return 1
    fi

    return 0
  fi

  return 1
}

exit_with_status() {
  local exit_code="$1"
  local state="$2"
  local description="$3"

  post_github_status "$state" "$description"
  exit "$exit_code"
}

validate_success_status_target() {
  local local_head
  local worktree_status

  if [[ "$github_status_enabled" != "true" ]]; then
    return
  fi

  if ! refresh_github_pr_head; then
    echo "AI review PASS, but current PR head could not be verified." >&2
    exit_with_status 2 "pending" "Strict BMAD review passed locally; current PR head could not be verified."
  fi

  worktree_status="$(git status --porcelain --untracked-files=all)"
  if [[ -n "$worktree_status" ]]; then
    echo "AI review PASS, but the working tree has uncommitted changes." >&2
    exit_with_status 1 "failure" "Strict BMAD review passed locally, but fixes are uncommitted."
  fi

  local_head="$(git rev-parse HEAD)"
  if [[ "$github_head_sha" != "$local_head" ]]; then
    echo "AI review PASS, but local HEAD $local_head does not match current PR head $github_head_sha." >&2
    exit_with_status 2 "pending" "Strict BMAD review passed locally; push local HEAD and wait for current-head CI."
  fi

  if ! validate_current_head_github_checks; then
    echo "AI review PASS, but current-head GitHub checks are not passing." >&2
    if [[ "$current_head_github_checks_result" == "fail" ]]; then
      exit_with_status 1 "failure" "Strict BMAD review passed locally, but current-head GitHub checks failed or required checks are missing."
    fi

    exit_with_status 2 "pending" "Strict BMAD review passed locally; current-head GitHub checks are not complete."
  fi
}

append_fail_log() {
  local agent="$1"
  local review_log="$2"
  local ts="$3"

  fail_log="${fail_log:-$log_dir/review-fail-iter${iter}-${ts}.md}"
  { echo "=== Agent: $agent ==="; cat "$review_log"; echo; } >> "$fail_log"
}

append_pending_log() {
  local agent="$1"
  local review_log="$2"
  local ts="$3"

  pending_log="${pending_log:-$log_dir/review-pending-iter${iter}-${ts}.md}"
  { echo "=== Agent: $agent ==="; cat "$review_log"; echo; } >> "$pending_log"
}

mark_review_status() {
  local agent="$1"
  local review_log="$2"
  local ts="$3"
  local status="$4"
  local marker_state

  case "$status" in
    PASS)
      marker_state="$(strict_marker_state "$review_log")"
      case "$marker_state" in
        PASS)
          if ! report_has_required_content "$review_log"; then
            echo "Warning: Agent $agent produced STATUS: PASS but required review sections are missing; treating as FAIL." >&2
            all_pass=false
            any_fail=true
            append_fail_log "$agent" "$review_log" "$ts"
          elif report_has_actionable_findings "$review_log"; then
            echo "Warning: Agent $agent produced STATUS: PASS with actionable local findings; treating as FAIL." >&2
            all_pass=false
            any_fail=true
            append_fail_log "$agent" "$review_log" "$ts"
          fi
          ;;
        PENDING)
          echo "Warning: Agent $agent produced STATUS: PASS with remote-CI pending markers; prompt requires STATUS: FAIL for pending remote CI." >&2
          all_pass=false
          any_fail=true
          protocol_failure=true
          append_fail_log "$agent" "$review_log" "$ts"
          ;;
        *)
          echo "Warning: Agent $agent produced STATUS: PASS but strict BMAD markers are not passing; treating as FAIL." >&2
          all_pass=false
          any_fail=true
          append_fail_log "$agent" "$review_log" "$ts"
          ;;
      esac
      ;;
    FAIL)
      all_pass=false
      if strict_failure_is_remote_pending "$review_log"; then
        if ! report_has_required_content "$review_log"; then
          echo "Warning: Agent $agent produced remote-CI pending markers but required review sections are missing; treating as FAIL." >&2
          any_fail=true
          append_fail_log "$agent" "$review_log" "$ts"
        else
          any_pending=true
          append_pending_log "$agent" "$review_log" "$ts"
        fi
      else
        any_fail=true
        append_fail_log "$agent" "$review_log" "$ts"
      fi
      ;;
  esac
}

run_fix_if_needed() {
  local fix_ts
  local fix_log

  fix_ts=$(date +%Y%m%d_%H%M%S)
  fix_log="$log_dir/fix-${fix_agent}-iter${iter}-${fix_ts}.md"
  run_fix "$fix_agent" "$fix_log" "$fail_log" "$ci_log"
  cp "$fix_log" "$log_dir/fix-latest.md"

  ci_log="$log_dir/ci-iter${iter}-${fix_ts}.log"
  if ! bash -c "$verify_cmd" >"$ci_log" 2>&1; then
    echo "Warning: Verification failed (see $ci_log)." >&2
    last_verify_ok=false
  else
    last_verify_ok=true
  fi
  verification_ran=true
}

run_verification_for_pass() {
  local verify_ts

  if [[ "$verification_ran" == true && "$last_verify_ok" == true ]]; then
    return
  fi

  verify_ts=$(date +%Y%m%d_%H%M%S)
  ci_log="$log_dir/ci-pass-${verify_ts}.log"
  if ! bash -c "$verify_cmd" >"$ci_log" 2>&1; then
    echo "Warning: Verification failed (see $ci_log)." >&2
    last_verify_ok=false
  else
    last_verify_ok=true
  fi
  verification_ran=true
}

handle_review_result() {
  if [[ "$all_pass" == true ]]; then
    run_verification_for_pass
    if [[ "$last_verify_ok" != true ]]; then
      echo "AI review PASS, but last verification failed. Fix verification failures first." >&2
      exit_with_status 1 "failure" "Strict BMAD review passed, but local verification failed."
    fi
    validate_success_status_target
    echo "AI review PASS." >&2
    exit_with_status 0 "success" "Strict BMAD FR/NFR review and local verification passed."
  fi

  if [[ "$protocol_failure" == true ]]; then
    echo "AI review report violates strict BMAD protocol; remote-pending markers require STATUS: FAIL." >&2
    exit_with_status 1 "failure" "Strict BMAD review report violated pending-CI status protocol."
  fi

  if [[ "$any_fail" != true && "$any_pending" == true ]]; then
    echo "AI review pending remote CI; no local fixes to apply." >&2
    cp "$pending_log" "$log_dir/review-latest.md" 2>/dev/null || true
    exit_with_status 2 "pending" "Strict BMAD review passed locally; expected remote CI is still pending."
  fi

  post_github_status "pending" "Strict BMAD review found findings; fix loop running."
}

fail_max_iterations() {
  echo "Reached AI_REVIEW_MAX_ITER=$max_iter without PASS." >&2
  exit_with_status 1 "failure" "Strict BMAD review still has findings after max fix iterations."
}

# --- Agent runners --------------------------------------------------------

run_review() {
  local agent="$1"
  local output_file="$2"
  local prompt

  prompt="$(build_review_prompt)"

  case "$agent" in
    codex)
      printf "%s" "$prompt" \
        | "$codex_cmd" exec \
            ${codex_flags[@]+"${codex_flags[@]}"} \
            --sandbox "$review_sandbox" \
            --output-last-message "$output_file" - \
          >"${output_file}.log" 2>&1
      ;;
    claude)
      "$claude_cmd" -p "$prompt" \
        ${claude_flags[@]+"${claude_flags[@]}"} \
        --append-system-prompt "After completing the review, your FIRST line of output MUST be exactly STATUS: PASS or STATUS: FAIL and include every strict BMAD marker from the prompt." \
        --output-format text \
        >"$output_file" 2>"${output_file}.log"
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
        | "$codex_cmd" exec \
            ${codex_flags[@]+"${codex_flags[@]}"} \
            --sandbox "$fix_sandbox" \
            --output-last-message "$output_file" - \
          >"${output_file}.log" 2>&1
      ;;
    claude)
      "$claude_cmd" -p "$prompt" \
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
verification_ran=false

while :; do
  if [[ "$max_iter" -ne 0 && "$iter" -gt "$max_iter" ]]; then
    fail_max_iterations
  fi

  all_pass=true
  any_fail=false
  any_pending=false
  protocol_failure=false
  fail_log=""
  pending_log=""

  for agent in "${agents[@]}"; do
    ts=$(date +%Y%m%d_%H%M%S)
    review_log="$log_dir/review-${agent}-iter${iter}-${ts}.md"
    run_review "$agent" "$review_log"
    cp "$review_log" "$log_dir/review-latest-${agent}.md"

    status=$(parse_status_line "$review_log")
    if [[ "$status" == "UNKNOWN" ]]; then
      echo "Warning: Agent $agent did not produce STATUS line; treating as FAIL." >&2
      status="FAIL"
    fi

    mark_review_status "$agent" "$review_log" "$ts" "$status"
  done

  cp "${fail_log:-${pending_log:-$log_dir/review-latest-${agents[-1]}.md}}" \
    "$log_dir/review-latest.md" 2>/dev/null || true

  handle_review_result
  run_fix_if_needed

  iter=$((iter + 1))
done
