#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

setup() {
  if project_root="$(git rev-parse --show-toplevel 2>/dev/null)"; then
    cd "$project_root"
    return
  fi

  cd "$BATS_TEST_DIRNAME/../../.."
}

teardown() {
  rm -f .bats-ai-review-loop-dirty-*
}

create_pass_codex_stub() {
  local bin_dir="$1"

  cat > "$bin_dir/codex" <<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail

if [[ "${1:-}" == "exec" && "${2:-}" == "--help" ]]; then
  echo "--output-last-message"
  exit 0
fi

if [[ "${1:-}" == "exec" ]]; then
  output_file=""
  while [[ $# -gt 0 ]]; do
    if [[ "$1" == "--output-last-message" ]]; then
      output_file="${2:-}"
      shift 2
      continue
    fi
    shift
  done

  cat >/dev/null

  if [[ -z "$output_file" ]]; then
    echo "missing --output-last-message argument" >&2
    exit 2
  fi

  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
FR_NFR_SCORECARD: PASS
TEST_CASE_MATRIX: PASS
AUTO_TEST_COVERAGE: PASS
CI_COVERAGE: PASS
FLAKY_TEST_RISK: PASS
SYSTEM_QUALITY_ATTRIBUTES_SCORECARD: PASS
WHOLE_CODEBASE_IMPACT: PASS
GRAPH_IMPACT_CONTEXT: PASS
GITHUB_COMPLETION_GATE: PASS
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"
}

create_github_status_stub() {
  local bin_dir="$1"

  cat > "$bin_dir/gh" <<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail

if [[ "${1:-}" == "repo" && "${2:-}" == "view" ]]; then
  echo "VilnaCRM-Org/user-service"
  exit 0
fi

if [[ "${1:-}" == "pr" && "${2:-}" == "view" ]]; then
  joined=" $* "
  if [[ "$joined" == *" baseRefName "* ]]; then
    echo "main"
    exit 0
  fi

  if [[ "$joined" == *" headRefOid "* ]]; then
    IFS=',' read -r -a head_values <<< "${GH_HEAD_SHA_SEQUENCE:?}"
    counter_file="${GH_HEAD_COUNTER_FILE:?}"
    counter="$(cat "$counter_file" 2>/dev/null || echo 0)"
    counter=$((counter + 1))
    echo "$counter" > "$counter_file"
    index=$((counter - 1))
    if [[ "$index" -ge "${#head_values[@]}" ]]; then
      index=$((${#head_values[@]} - 1))
    fi
    echo "${head_values[$index]}"
    exit 0
  fi

  if [[ "$joined" == *" url "* ]]; then
    echo "https://github.com/VilnaCRM-Org/user-service/pull/286"
    exit 0
  fi
fi

if [[ "${1:-}" == "api" ]]; then
  printf '%s\n' "$*" >> "${GH_STATUS_LOG:?}"
  exit 0
fi

echo "unexpected gh invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/gh"
}

@test "make help includes ai-review-loop" {
  run make help
  assert_success
  assert_output --partial "ai-review-loop"
}

@test "ai-review-loop fails with helpful message when Codex command is missing" {
  AI_REVIEW_CODEX_CMD=codex-missing AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" run ./scripts/ai-review-loop.sh
  assert_failure
  assert_output --partial "Codex CLI (codex) is required"
}

@test "ai-review-loop passes when Codex review reports PASS" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  mkdir -p "$bin_dir"

  create_pass_codex_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_GITHUB_STATUS=false \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/ai-review-loop.sh 2>&1"
  assert_success
  assert_output --partial "AI review PASS."
}

@test "ai-review-loop claude agent receives strict review prompt" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  mkdir -p "$bin_dir"

  cat > "$bin_dir/claude" <<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail

# Capture all args to verify the strict prompt is passed via -p.
joined=" $* "
if [[ "$joined" != *" -p "* || "$joined" != *"strict BMAD FR/NFR code reviewer"* ]]; then
  echo "ERROR: strict review prompt not found in args: $*" >&2
  exit 2
fi

echo "STATUS: PASS"
echo "0 issues."
echo "FR_NFR_SCORECARD: PASS"
echo "TEST_CASE_MATRIX: PASS"
echo "AUTO_TEST_COVERAGE: PASS"
echo "CI_COVERAGE: PASS"
echo "FLAKY_TEST_RISK: PASS"
echo "SYSTEM_QUALITY_ATTRIBUTES_SCORECARD: PASS"
echo "WHOLE_CODEBASE_IMPACT: PASS"
echo "GRAPH_IMPACT_CONTEXT: PASS"
echo "GITHUB_COMPLETION_GATE: PASS"
SCRIPT
  chmod +x "$bin_dir/claude"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_AGENT=claude \
    AI_REVIEW_CLAUDE_CMD=claude \
    AI_REVIEW_GITHUB_STATUS=false \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/ai-review-loop.sh 2>&1"
  assert_success
  assert_output --partial "AI review PASS."
}

@test "ai-review-loop claude agent separates stderr from stdout" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  mkdir -p "$bin_dir"

  cat > "$bin_dir/claude" <<'SCRIPT'
#!/usr/bin/env bash
echo "some warning" >&2
echo "STATUS: PASS"
echo "0 issues."
echo "FR_NFR_SCORECARD: PASS"
echo "TEST_CASE_MATRIX: PASS"
echo "AUTO_TEST_COVERAGE: PASS"
echo "CI_COVERAGE: PASS"
echo "FLAKY_TEST_RISK: PASS"
echo "SYSTEM_QUALITY_ATTRIBUTES_SCORECARD: PASS"
echo "WHOLE_CODEBASE_IMPACT: PASS"
echo "GRAPH_IMPACT_CONTEXT: PASS"
echo "GITHUB_COMPLETION_GATE: PASS"
SCRIPT
  chmod +x "$bin_dir/claude"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_AGENT=claude \
    AI_REVIEW_CLAUDE_CMD=claude \
    AI_REVIEW_GITHUB_STATUS=false \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/ai-review-loop.sh 2>&1"
  assert_success
  assert_output --partial "AI review PASS."

  # Verify stderr went to .log file, not to the review output
  local review_file
  review_file="${BATS_TEST_TMPDIR}/ai-review/review-latest-claude.md"
  run cat "$review_file"
  refute_output --partial "some warning"
}

@test "ai-review-loop refuses success when passing review leaves dirty worktree" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local head_sha
  local status_log="${BATS_TEST_TMPDIR}/statuses.log"
  mkdir -p "$bin_dir"
  create_pass_codex_stub "$bin_dir"
  create_github_status_stub "$bin_dir"
  head_sha="$(git rev-parse HEAD)"
  touch ".bats-ai-review-loop-dirty-${BATS_TEST_NUMBER}"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_GITHUB_PR=286 \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    AI_REVIEW_BASE=HEAD \
    GH_HEAD_COUNTER_FILE="${BATS_TEST_TMPDIR}/head-counter" \
    GH_HEAD_SHA_SEQUENCE="${head_sha},${head_sha}" \
    GH_STATUS_LOG="$status_log" \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "working tree has uncommitted changes"
  run grep -q "state=success" "$status_log"
  assert_failure
  run grep -q "state=failure" "$status_log"
  assert_success
}

@test "ai-review-loop refuses success when local head is not the PR head" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local remote_sha="0000000000000000000000000000000000000001"
  local status_log="${BATS_TEST_TMPDIR}/statuses.log"
  mkdir -p "$bin_dir"
  create_pass_codex_stub "$bin_dir"
  create_github_status_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_GITHUB_PR=286 \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    AI_REVIEW_BASE=HEAD \
    GH_HEAD_COUNTER_FILE="${BATS_TEST_TMPDIR}/head-counter" \
    GH_HEAD_SHA_SEQUENCE="${remote_sha},${remote_sha}" \
    GH_STATUS_LOG="$status_log" \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "does not match current PR head"
  run grep -q "state=success" "$status_log"
  assert_failure
  run grep -q "state=pending" "$status_log"
  assert_success
}

@test "ai-review-loop refuses success when PR head drifts before completion" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local head_sha
  local drift_sha="0000000000000000000000000000000000000002"
  local status_log="${BATS_TEST_TMPDIR}/statuses.log"
  mkdir -p "$bin_dir"
  create_pass_codex_stub "$bin_dir"
  create_github_status_stub "$bin_dir"
  head_sha="$(git rev-parse HEAD)"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_GITHUB_PR=286 \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    AI_REVIEW_BASE=HEAD \
    GH_HEAD_COUNTER_FILE="${BATS_TEST_TMPDIR}/head-counter" \
    GH_HEAD_SHA_SEQUENCE="${head_sha},${drift_sha}" \
    GH_STATUS_LOG="$status_log" \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "does not match current PR head"
  run grep -q "state=success" "$status_log"
  assert_failure
  run grep -q "statuses/${drift_sha}" "$status_log"
  assert_success
}
