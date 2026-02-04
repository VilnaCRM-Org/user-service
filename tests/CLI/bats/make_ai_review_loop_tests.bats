#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

@test "make help includes ai-review-loop" {
  run make help
  assert_success
  assert_output --partial "ai-review-loop"
}

@test "ai-review-loop fails with helpful message when Codex command is missing" {
  AI_REVIEW_CODEX_CMD=codex-missing run ./scripts/ai-review-loop.sh
  assert_failure
  assert_output --partial "Codex CLI (codex) is required"
}
