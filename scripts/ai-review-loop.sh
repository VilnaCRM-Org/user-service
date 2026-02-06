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

mkdir -p "$log_dir"

# --- Validate inputs ------------------------------------------------------

for f in "$review_prompt_file" "$fix_prompt_file"; do
  if [[ ! -f "$f" ]]; then
    echo "Prompt file not found: $f" >&2
    exit 1
  fi
done

if [[ ! "$max_iter" =~ ^[0-9]+$ ]]; then
  echo "AI_REVIEW_MAX_ITER must be a non-negative integer (got: $max_iter)" >&2
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

# --- Agent validation -----------------------------------------------------

ensure_codex_output_last_message() {
  if ! "$codex_cmd" exec --help 2>/dev/null | grep -q -- '--output-last-message'; then
    echo "Codex CLI is missing --output-last-message; update Codex CLI." >&2
    exit 1
  fi
}

ensure_codex_review_command() {
  if ! "$codex_cmd" review --help >/dev/null 2>&1; then
    echo "Codex CLI is missing review command; update Codex CLI." >&2
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
      ensure_codex_review_command
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
  base_branch=$(gh pr view --json baseRefName -q .baseRefName 2>/dev/null || true)
fi

if [[ -z "$base_branch" ]]; then
  base_branch="main"
  echo "Warning: Unable to detect PR base branch. Falling back to origin/$base_branch." >&2
fi

review_base="$base_branch"
if [[ "$base_branch" != */* ]]; then
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

# --- Agent runners --------------------------------------------------------

run_review() {
  local agent="$1"
  local output_file="$2"
  local prompt
  prompt="$(build_review_prompt)"

  case "$agent" in
    codex)
      printf "%s" "$prompt" \
        | "$codex_cmd" \
            ${codex_flags[@]+"${codex_flags[@]}"} \
            --sandbox "$review_sandbox" \
            review \
            --base "$review_base" \
            --uncommitted \
            - \
          >"$output_file" 2>"${output_file}.log"
      ;;
    claude)
      "$claude_cmd" -p "$prompt" \
        ${claude_flags[@]+"${claude_flags[@]}"} \
        --output-format text \
        >"$output_file" 2>&1
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
        >"$output_file" 2>&1
      ;;
  esac
}

# --- Main loop ------------------------------------------------------------

iter=1
ci_log=""
last_verify_ok=true

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
    run_review "$agent" "$review_log"
    cp "$review_log" "$log_dir/review-latest-${agent}.md"

    status=$(parse_status_line "$review_log")
    if [[ "$status" == "UNKNOWN" ]]; then
      echo "Warning: Agent $agent did not produce STATUS line; treating as FAIL." >&2
      status="FAIL"
    fi

    if [[ "$status" == "FAIL" ]]; then
      all_pass=false
      fail_log="${fail_log:-$log_dir/review-fail-iter${iter}-${ts}.md}"
      { echo "=== Agent: $agent ==="; cat "$review_log"; echo; } >> "$fail_log"
    fi
  done

  cp "${fail_log:-$log_dir/review-latest-${agents[-1]}.md}" \
    "$log_dir/review-latest.md" 2>/dev/null || true

  if [[ "$all_pass" == true ]]; then
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
  if ! bash -c "$verify_cmd" >"$ci_log" 2>&1; then
    echo "Warning: Verification failed (see $ci_log)." >&2
    last_verify_ok=false
  else
    last_verify_ok=true
  fi

  iter=$((iter + 1))
done
