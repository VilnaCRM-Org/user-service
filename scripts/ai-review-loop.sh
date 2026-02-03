#!/usr/bin/env bash
set -euo pipefail

log_dir="${AI_REVIEW_LOG_DIR:-var/ai-review}"
review_prompt_file="${AI_REVIEW_REVIEW_PROMPT:-scripts/ai-review-prompts/review.md}"
fix_prompt_file="${AI_REVIEW_FIX_PROMPT:-scripts/ai-review-prompts/fix.md}"
verify_cmd="${AI_REVIEW_VERIFY_CMD:-make ci}"
max_iter_raw="${AI_REVIEW_MAX_ITER:-3}"

mkdir -p "$log_dir"

if [[ ! -f "$review_prompt_file" ]]; then
  echo "Review prompt file not found: $review_prompt_file" >&2
  exit 1
fi

if [[ ! -f "$fix_prompt_file" ]]; then
  echo "Fix prompt file not found: $fix_prompt_file" >&2
  exit 1
fi

if [[ ! "$max_iter_raw" =~ ^[0-9]+$ ]]; then
  echo "AI_REVIEW_MAX_ITER must be a non-negative integer (got: $max_iter_raw)" >&2
  exit 1
fi

agents_raw="${AI_REVIEW_AGENTS:-${AI_REVIEW_AGENT:-codex}}"
IFS=',' read -r -a agents <<< "$agents_raw"

sanitize_agent() {
  echo "$1" | tr -d '[:space:]'
}

agents_sanitized=()
for agent in "${agents[@]}"; do
  cleaned="$(sanitize_agent "$agent")"
  if [[ -n "$cleaned" ]]; then
    agents_sanitized+=("$cleaned")
  fi
done

if [[ ${#agents_sanitized[@]} -eq 0 ]]; then
  echo "No review agents configured. Set AI_REVIEW_AGENT or AI_REVIEW_AGENTS." >&2
  exit 1
fi

fix_agent="${AI_REVIEW_FIX_AGENT:-${agents_sanitized[0]}}"

codex_cmd="${AI_REVIEW_CODEX_CMD:-codex}"
claude_cmd="${AI_REVIEW_CLAUDE_CMD:-claude}"

read -r -a codex_flags <<< "${AI_REVIEW_CODEX_FLAGS:-}"
read -r -a claude_flags <<< "${AI_REVIEW_CLAUDE_FLAGS:-}"

require_command() {
  local cmd="$1"
  local label="$2"
  if ! command -v "$cmd" >/dev/null 2>&1; then
    echo "$label is required but not installed: $cmd" >&2
    return 1
  fi
}

require_agent_command() {
  local agent="$1"
  case "$agent" in
    codex)
      require_command "$codex_cmd" "Codex CLI (codex)" || exit 1
      ;;
    claude)
      require_command "$claude_cmd" "Claude CLI (claude)" || exit 1
      ;;
    *)
      echo "Unknown agent: $agent" >&2
      exit 1
      ;;
  esac
}

for agent in "${agents_sanitized[@]}"; do
  require_agent_command "$agent"
done
require_agent_command "$fix_agent"

base_branch="${AI_REVIEW_BASE:-}"
if [[ -z "$base_branch" ]]; then
  if command -v gh >/dev/null 2>&1; then
    base_branch=$(gh pr view --json baseRefName -q .baseRefName 2>/dev/null || true)
  fi
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

ensure_base_available() {
  local base="$1"
  if git rev-parse --verify "$base" >/dev/null 2>&1; then
    return 0
  fi

  local remote="origin"
  local branch="$base"
  if [[ "$base" == */* ]]; then
    remote="${base%%/*}"
    branch="${base#*/}"
  fi

  echo "Fetching base branch $remote/$branch..." >&2
  git fetch "$remote" "$branch" >/dev/null 2>&1 || true

  if ! git rev-parse --verify "$base" >/dev/null 2>&1; then
    echo "Error: Base branch $base is not available locally." >&2
    exit 1
  fi
}

ensure_base_available "$review_base"

build_review_prompt() {
  printf "%s\n%s\n" "$(cat "$review_prompt_file")" "$review_base"
}

build_fix_prompt() {
  local review_log="$1"
  local ci_log="$2"
  local prompt_content
  prompt_content="$(cat "$fix_prompt_file")"
  printf "%s\n\nLATEST_REVIEW_OUTPUT:\n%s\n" "$prompt_content" "$(cat "$review_log")"
  if [[ -n "$ci_log" && -f "$ci_log" ]]; then
    printf "\nLATEST_CI_OUTPUT:\n%s\n" "$(cat "$ci_log")"
  fi
}

parse_status_line() {
  local output_file="$1"
  local status_line
  status_line=$(head -n 1 "$output_file" | tr -d '\r')
  case "$status_line" in
    "STATUS: PASS")
      echo "PASS"
      ;;
    "STATUS: FAIL")
      echo "FAIL"
      ;;
    *)
      echo "UNKNOWN"
      ;;
  esac
}

run_review() {
  local agent="$1"
  local output_file="$2"
  local prompt
  prompt=$(build_review_prompt)

  case "$agent" in
    codex)
      printf "%s" "$prompt" | "$codex_cmd" review --base "$review_base" "${codex_flags[@]}" - >"$output_file" 2>&1
      ;;
    claude)
      printf "%s" "$prompt" | "$claude_cmd" "${claude_flags[@]}" >"$output_file" 2>&1
      ;;
    *)
      echo "Unknown agent: $agent" >&2
      exit 1
      ;;
  esac
}

run_fix() {
  local agent="$1"
  local output_file="$2"
  local prompt
  local review_log="$3"
  local ci_log="$4"

  prompt=$(build_fix_prompt "$review_log" "$ci_log")

  case "$agent" in
    codex)
      printf "%s" "$prompt" | "$codex_cmd" exec "${codex_flags[@]}" - >"$output_file" 2>&1
      ;;
    claude)
      printf "%s" "$prompt" | "$claude_cmd" "${claude_flags[@]}" >"$output_file" 2>&1
      ;;
    *)
      echo "Unknown agent: $agent" >&2
      exit 1
      ;;
  esac
}

iter=1
ci_log=""

while :; do
  if [[ "$max_iter_raw" -ne 0 && "$iter" -gt "$max_iter_raw" ]]; then
    echo "Reached AI_REVIEW_MAX_ITER=$max_iter_raw without a PASS." >&2
    exit 1
  fi

  all_pass=true
  last_review_log=""

  for agent in "${agents_sanitized[@]}"; do
    timestamp=$(date +%Y%m%d_%H%M%S)
    review_log="$log_dir/review-${agent}-iter${iter}-$timestamp.md"
    run_review "$agent" "$review_log"

    cp "$review_log" "$log_dir/review-latest-${agent}.md"
    if [[ "$agent" == "${agents_sanitized[-1]}" ]]; then
      cp "$review_log" "$log_dir/review-latest.md"
    fi

    status=$(parse_status_line "$review_log")
    if [[ "$status" != "PASS" && "$status" != "FAIL" ]]; then
      echo "Error: Review output missing STATUS line for agent $agent." >&2
      exit 1
    fi

    last_review_log="$review_log"
    if [[ "$status" == "FAIL" ]]; then
      all_pass=false
    fi
  done

  if [[ "$all_pass" == true ]]; then
    echo "AI review PASS." >&2
    exit 0
  fi

  fix_timestamp=$(date +%Y%m%d_%H%M%S)
  fix_log="$log_dir/fix-${fix_agent}-iter${iter}-$fix_timestamp.md"
  run_fix "$fix_agent" "$fix_log" "$last_review_log" "$ci_log"
  cp "$fix_log" "$log_dir/fix-latest.md"

  ci_log="$log_dir/ci-iter${iter}-$fix_timestamp.log"
  if ! bash -c "$verify_cmd" >"$ci_log" 2>&1; then
    echo "Warning: Verification command failed (see $ci_log)." >&2
  fi

  iter=$((iter + 1))
done
