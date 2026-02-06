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

  cat > "$bin_dir/codex" <<'EOF'
#!/usr/bin/env bash
set -euo pipefail

args="$*"

if [[ "$args" == *"exec --help"* ]]; then
  echo "--output-last-message"
  exit 0
fi

if [[ "$args" == *"review --help"* ]]; then
  echo "codex review help"
  exit 0
fi

if [[ "$args" == *"review"* ]]; then
  echo "STATUS: PASS"
  echo "0 issues."
  exit 0
fi

echo "unexpected codex invocation: $args" >&2
exit 2
EOF
  chmod +x "$bin_dir/codex"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/ai-review-loop.sh 2>&1"
  assert_success
  assert_output --partial "AI review PASS."
}
