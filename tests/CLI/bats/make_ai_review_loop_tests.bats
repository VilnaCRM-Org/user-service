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

write_successful_bmad_gh_stub() {
  local bin_dir="$1"

  cat > "$bin_dir/gh" <<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail

if [[ "${1:-}" == "pr" && "${2:-}" == "view" ]]; then
  if [[ "$*" != *"headRefOid"* || "$*" == *"statusCheckRollup"* ]]; then
    echo "PR state query must include headRefOid and must not rely on full statusCheckRollup" >&2
    exit 2
  fi
  printf '287\tfalse\tAPPROVED\thttps://github.example.com/VilnaCRM-Org/user-service/pull/287\t%s\n' "$(git rev-parse HEAD)"
  exit 0
fi

if [[ "${1:-}" == "pr" && "${2:-}" == "checks" ]]; then
  if [[ "$*" != *"--required"* ]]; then
    echo "required check query must use gh pr checks --required" >&2
    exit 2
  fi
  printf '3\t\n'
  exit 0
fi

if [[ "${1:-}" == "api" && "${2:-}" == "graphql" ]]; then
  printf '0\tfalse\t\n'
  exit 0
fi

echo "unexpected gh invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/gh"
}

write_failing_check_bmad_gh_stub() {
  local bin_dir="$1"

  cat > "$bin_dir/gh" <<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail

if [[ "${1:-}" == "pr" && "${2:-}" == "view" ]]; then
  if [[ "$*" != *"headRefOid"* || "$*" == *"statusCheckRollup"* ]]; then
    echo "PR state query must include headRefOid and must not rely on full statusCheckRollup" >&2
    exit 2
  fi
  printf '287\tfalse\tAPPROVED\thttps://github.example.com/VilnaCRM-Org/user-service/pull/287\t%s\n' "$(git rev-parse HEAD)"
  exit 0
fi

if [[ "${1:-}" == "pr" && "${2:-}" == "checks" ]]; then
  if [[ "$*" != *"--required"* ]]; then
    echo "required check query must use gh pr checks --required" >&2
    exit 2
  fi
  printf '3\tRun Bats Core Tests\n'
  exit 0
fi

if [[ "${1:-}" == "api" && "${2:-}" == "graphql" ]]; then
  printf '0\tfalse\t\n'
  exit 0
fi

echo "unexpected gh invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/gh"
}

write_empty_required_passing_visible_bmad_gh_stub() {
  local bin_dir="$1"

  cat > "$bin_dir/gh" <<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail

if [[ "${1:-}" == "pr" && "${2:-}" == "view" ]]; then
  if [[ "$*" != *"headRefOid"* || "$*" == *"statusCheckRollup"* ]]; then
    echo "PR state query must include headRefOid and must not rely on full statusCheckRollup" >&2
    exit 2
  fi
  printf '287\tfalse\tAPPROVED\thttps://github.example.com/VilnaCRM-Org/user-service/pull/287\t%s\n' "$(git rev-parse HEAD)"
  exit 0
fi

if [[ "${1:-}" == "pr" && "${2:-}" == "checks" ]]; then
  if [[ "$*" == *"--required"* ]]; then
    printf '0\t\n'
    exit 0
  fi
  printf '3\t\n'
  exit 0
fi

if [[ "${1:-}" == "api" && "${2:-}" == "graphql" ]]; then
  printf '0\tfalse\t\n'
  exit 0
fi

echo "unexpected gh invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/gh"
}

write_empty_required_empty_visible_bmad_gh_stub() {
  local bin_dir="$1"

  cat > "$bin_dir/gh" <<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail

if [[ "${1:-}" == "pr" && "${2:-}" == "view" ]]; then
  if [[ "$*" != *"headRefOid"* || "$*" == *"statusCheckRollup"* ]]; then
    echo "PR state query must include headRefOid and must not rely on full statusCheckRollup" >&2
    exit 2
  fi
  printf '287\tfalse\tAPPROVED\thttps://github.example.com/VilnaCRM-Org/user-service/pull/287\t%s\n' "$(git rev-parse HEAD)"
  exit 0
fi

if [[ "${1:-}" == "pr" && "${2:-}" == "checks" ]]; then
  printf '0\t\n'
  exit 0
fi

if [[ "${1:-}" == "api" && "${2:-}" == "graphql" ]]; then
  printf '0\tfalse\t\n'
  exit 0
fi

echo "unexpected gh invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/gh"
}

write_empty_required_failing_visible_bmad_gh_stub() {
  local bin_dir="$1"

  cat > "$bin_dir/gh" <<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail

if [[ "${1:-}" == "pr" && "${2:-}" == "view" ]]; then
  if [[ "$*" != *"headRefOid"* || "$*" == *"statusCheckRollup"* ]]; then
    echo "PR state query must include headRefOid and must not rely on full statusCheckRollup" >&2
    exit 2
  fi
  printf '287\tfalse\tAPPROVED\thttps://github.example.com/VilnaCRM-Org/user-service/pull/287\t%s\n' "$(git rev-parse HEAD)"
  exit 0
fi

if [[ "${1:-}" == "pr" && "${2:-}" == "checks" ]]; then
  if [[ "$*" == *"--required"* ]]; then
    printf '0\t\n'
    exit 0
  fi
  printf '3\tRun Bats Core Tests\n'
  exit 0
fi

if [[ "${1:-}" == "api" && "${2:-}" == "graphql" ]]; then
  printf '0\tfalse\t\n'
  exit 0
fi

echo "unexpected gh invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/gh"
}

write_skipped_check_bmad_gh_stub() {
  local bin_dir="$1"

  cat > "$bin_dir/gh" <<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail

if [[ "${1:-}" == "pr" && "${2:-}" == "view" ]]; then
  if [[ "$*" != *"headRefOid"* || "$*" == *"statusCheckRollup"* ]]; then
    echo "PR state query must include headRefOid and must not rely on full statusCheckRollup" >&2
    exit 2
  fi
  printf '287\tfalse\tAPPROVED\thttps://github.example.com/VilnaCRM-Org/user-service/pull/287\t%s\n' "$(git rev-parse HEAD)"
  exit 0
fi

if [[ "${1:-}" == "pr" && "${2:-}" == "checks" ]]; then
  if [[ "$*" != *"--required"* ]]; then
    echo "required check query must use gh pr checks --required" >&2
    exit 2
  fi
  printf '3\tOptional Docs\n'
  exit 0
fi

if [[ "${1:-}" == "api" && "${2:-}" == "graphql" ]]; then
  printf '0\tfalse\t\n'
  exit 0
fi

echo "unexpected gh invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/gh"
}

write_stale_head_bmad_gh_stub() {
  local bin_dir="$1"

  cat > "$bin_dir/gh" <<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail

if [[ "${1:-}" == "pr" && "${2:-}" == "view" ]]; then
  printf '287\tfalse\tAPPROVED\thttps://github.example.com/VilnaCRM-Org/user-service/pull/287\t0000000000000000000000000000000000000000\n'
  exit 0
fi

if [[ "${1:-}" == "pr" && "${2:-}" == "checks" ]]; then
  printf '3\t\n'
  exit 0
fi

if [[ "${1:-}" == "api" && "${2:-}" == "graphql" ]]; then
  printf '0\tfalse\t\n'
  exit 0
fi

echo "unexpected gh invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/gh"
}

write_bmad_pass_codex_stub() {
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
  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
FR_NFR_SCORECARD: PASS
NFR_CATALOG_SCORECARD: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: APPROVED
CI_CHECK_ROLLUP: PASSING

Requirement Scorecard:
- FR-01 evidence: 5/5 PASS

NFR Catalog Scorecard:
- Performance: 5/5 PASS
- Usability: 5/5 PASS
- Maintainability: 5/5 PASS
- Availability: 5/5 PASS
- Interoperability: 5/5 PASS
- Security: 5/5 PASS
- Manageability: 5/5 PASS
- Automatability: 5/5 PASS
- Dependability: 5/5 PASS
Performance: 5/5 PASS

Manual Test Evidence:
- Manual evidence reviewed: 5/5 PASS
- Not applicable for shell validation fixture.

QA Verification:
- QA verification completed: 5/5 PASS
- make ci: PASS

GitHub Completion Gate:
- GitHub completion verified: 5/5 PASS
- reviewDecision APPROVED, unresolved threads 0.

CI Gate:
- Required CI checks verified: 5/5 PASS
- statusCheckRollup PASSING.
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"
}

@test "make help includes ai-review-loop" {
  run make help
  assert_success
  assert_output --partial "ai-review-loop"
  assert_output --partial "bmad-fr-nfr-review-gate"
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
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
EOF
  chmod +x "$bin_dir/codex"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_VERIFY_ON_PASS=false \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/ai-review-loop.sh 2>&1"
  assert_success
  assert_output --partial "AI review PASS."
}

@test "ai-review-loop accepts strict PASS without trailing newline" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  mkdir -p "$bin_dir"

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
  printf 'STATUS: PASS' > "$output_file"
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"
  write_successful_bmad_gh_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_VERIFY_ON_PASS=false \
    AI_REVIEW_REQUIRE_GATE_MARKERS=true \
    AI_REVIEW_REQUIRED_GATE_MARKERS="STATUS: PASS" \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_success
  assert_output --partial "AI review PASS."
}

@test "ai-review-loop verifies after PASS by default" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local verify_marker="${BATS_TEST_TMPDIR}/verify-ran"
  mkdir -p "$bin_dir"

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
  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"

  cat > "$bin_dir/make" <<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail
printf "%s\n" "$*" > "${VERIFY_MARKER}"
[[ "$*" == "ci" ]]
SCRIPT
  chmod +x "$bin_dir/make"

  run env \
    PATH="$bin_dir:$PATH" \
    VERIFY_MARKER="$verify_marker" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_success
  assert_output --partial "AI review PASS."

  run cat "$verify_marker"
  assert_output "ci"
}

@test "ai-review-loop sanitizes review-control env before invoking agents" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/example"
  mkdir -p "$bin_dir" "$spec_dir"
  printf "# PRD\n\nFR-01: Works.\n" > "${spec_dir}/prd.md"

  cat > "$bin_dir/codex" <<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail

if [[ "${1:-}" == "exec" && "${2:-}" == "--help" ]]; then
  echo "--output-last-message"
  exit 0
fi

if [[ "${1:-}" == "exec" ]]; then
  for name in \
    AI_REVIEW_SPEC_PATH \
    AI_REVIEW_VERIFY_CMD \
    AI_REVIEW_LOG_DIR \
    AI_REVIEW_REQUIRED_GATE_MARKERS \
    BMAD_REVIEW_SPEC_PATH \
    BMAD_REVIEW_VERIFY_CMD \
    BMAD_REVIEW_LOG_DIR; do
    if [[ -n "${!name:-}" ]]; then
      echo "unexpected inherited env: ${name}" >&2
      exit 3
    fi
  done

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
  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"
  write_successful_bmad_gh_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_SPEC_PATH="$spec_dir" \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_REQUIRED_GATE_MARKERS="SHOULD_NOT_LEAK" \
    AI_REVIEW_VERIFY_CMD="should-not-leak" \
    AI_REVIEW_VERIFY_ON_PASS=false \
    AI_REVIEW_MAX_ITER=1 \
    BMAD_REVIEW_SPEC_PATH="should-not-leak" \
    BMAD_REVIEW_VERIFY_CMD="should-not-leak" \
    BMAD_REVIEW_LOG_DIR="should-not-leak" \
    bash -c "./scripts/ai-review-loop.sh 2>&1"
  assert_success
  assert_output --partial "AI review PASS."
}

@test "ai-review-loop claude agent uses built-in /review skill" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  mkdir -p "$bin_dir"

  cat > "$bin_dir/claude" <<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail

# Capture all args to verify /review is passed
for arg in "$@"; do
  if [[ "$arg" == "/review" ]]; then
    echo "STATUS: PASS"
    echo "0 issues."
    exit 0
  fi
done

echo "ERROR: /review not found in args: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/claude"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_AGENT=claude \
    AI_REVIEW_CLAUDE_CMD=claude \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_VERIFY_CMD=true \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/ai-review-loop.sh 2>&1"
  assert_success
  assert_output --partial "AI review PASS."
}

@test "ai-review-loop claude agent skips built-in review when gate markers are required" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  mkdir -p "$bin_dir"

  cat > "$bin_dir/claude" <<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail

prompt=""
while [[ $# -gt 0 ]]; do
  if [[ "$1" == "-p" ]]; then
    prompt="${2:-}"
    shift 2
    continue
  fi
  shift
done

if [[ "$prompt" == "/review" ]]; then
  echo "ERROR: built-in /review should not run when gate markers are required" >&2
  exit 2
fi

cat <<'STATUS'
STATUS: PASS
0 issues.
FR_NFR_SCORECARD: PASS
NFR_CATALOG_SCORECARD: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: APPROVED
CI_CHECK_ROLLUP: PASSING

Requirement Scorecard:
- FR-01 evidence: 5/5 PASS

NFR Catalog Scorecard:
- Performance: 5/5 PASS
- Usability: 5/5 PASS
- Maintainability: 5/5 PASS
- Availability: 5/5 PASS
- Interoperability: 5/5 PASS
- Security: 5/5 PASS
- Manageability: 5/5 PASS
- Automatability: 5/5 PASS
- Dependability: 5/5 PASS
- Maintainability evidence: 5/5 PASS

Manual Test Evidence:
- Manual evidence reviewed: 5/5 PASS
- Not applicable for shell validation fixture.

QA Verification:
- QA verification completed: 5/5 PASS
- make ci: PASS

GitHub Completion Gate:
- GitHub completion verified: 5/5 PASS
- reviewDecision APPROVED, unresolved threads 0.

CI Gate:
- Required CI checks verified: 5/5 PASS
- statusCheckRollup PASSING.
STATUS
SCRIPT
  chmod +x "$bin_dir/claude"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_AGENT=claude \
    AI_REVIEW_CLAUDE_CMD=claude \
    AI_REVIEW_BASE=HEAD \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_REQUIRE_GATE_MARKERS=true \
    AI_REVIEW_VERIFY_CMD=true \
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
SCRIPT
  chmod +x "$bin_dir/claude"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_AGENT=claude \
    AI_REVIEW_CLAUDE_CMD=claude \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_VERIFY_CMD=true \
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

@test "ai-review-loop resolves local base branch before same-named tag" {
  local repo_dir="${BATS_TEST_TMPDIR}/repo"
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local prompt_capture="${BATS_TEST_TMPDIR}/prompt.txt"
  local base_name="ai-review-shadow-base"
  local project_root
  project_root="$(pwd)"

  mkdir -p "$repo_dir/scripts/ai-review-prompts" "$bin_dir"
  cp "$project_root/scripts/ai-review-loop.sh" "$repo_dir/scripts/ai-review-loop.sh"
  cp "$project_root/scripts/ai-review-prompts/review.md" "$repo_dir/scripts/ai-review-prompts/review.md"
  cp "$project_root/scripts/ai-review-prompts/fix.md" "$repo_dir/scripts/ai-review-prompts/fix.md"
  chmod +x "$repo_dir/scripts/ai-review-loop.sh"

  git -C "$repo_dir" init -q
  git -C "$repo_dir" config user.email "ci@example.test"
  git -C "$repo_dir" config user.name "CI"
  printf "base\n" > "$repo_dir/file.txt"
  git -C "$repo_dir" add file.txt
  git -C "$repo_dir" commit -qm "base"
  git -C "$repo_dir" branch "$base_name"
  git -C "$repo_dir" tag "$base_name"

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

  prompt="$(cat)"
  printf "%s" "$prompt" > "${PROMPT_CAPTURE}"

  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"
  write_successful_bmad_gh_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    PROMPT_CAPTURE="$prompt_capture" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_BASE="$base_name" \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_VERIFY_CMD=true \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "cd '$repo_dir' && ./scripts/ai-review-loop.sh 2>&1"

  assert_success
  assert_output --partial "AI review PASS."

  run grep -F "refs/heads/${base_name}" "$prompt_capture"
  assert_success
}

@test "ai-review-loop accepts explicit HEAD ancestry base refs" {
  local repo_dir="${BATS_TEST_TMPDIR}/repo"
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local prompt_capture="${BATS_TEST_TMPDIR}/prompt.txt"
  local project_root
  project_root="$(pwd)"

  mkdir -p "$repo_dir/scripts/ai-review-prompts" "$bin_dir"
  cp "$project_root/scripts/ai-review-loop.sh" "$repo_dir/scripts/ai-review-loop.sh"
  cp "$project_root/scripts/ai-review-prompts/review.md" "$repo_dir/scripts/ai-review-prompts/review.md"
  cp "$project_root/scripts/ai-review-prompts/fix.md" "$repo_dir/scripts/ai-review-prompts/fix.md"
  chmod +x "$repo_dir/scripts/ai-review-loop.sh"

  git -C "$repo_dir" init -q
  git -C "$repo_dir" config user.email "ci@example.test"
  git -C "$repo_dir" config user.name "CI"
  printf "base\n" > "$repo_dir/file.txt"
  git -C "$repo_dir" add file.txt
  git -C "$repo_dir" commit -qm "base"
  printf "work\n" >> "$repo_dir/file.txt"
  git -C "$repo_dir" commit -am "work" -q

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

  prompt="$(cat)"
  printf "%s" "$prompt" > "${PROMPT_CAPTURE}"

  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"
  write_successful_bmad_gh_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    PROMPT_CAPTURE="$prompt_capture" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_BASE="HEAD~" \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_VERIFY_CMD=true \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "cd '$repo_dir' && ./scripts/ai-review-loop.sh 2>&1"

  assert_success
  assert_output --partial "AI review PASS."

  run grep -F "HEAD~" "$prompt_capture"
  assert_success
}

@test "ai-review-loop fetches hex-like branch names before treating them as commits" {
  local remote_dir="${BATS_TEST_TMPDIR}/remote.git"
  local seed_dir="${BATS_TEST_TMPDIR}/seed"
  local repo_dir="${BATS_TEST_TMPDIR}/repo"
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local prompt_capture="${BATS_TEST_TMPDIR}/prompt.txt"
  local branch_name="deadbeef"
  local project_root
  project_root="$(pwd)"

  mkdir -p "$seed_dir" "$repo_dir/scripts/ai-review-prompts" "$bin_dir"
  git init --bare -q "$remote_dir"
  git -C "$seed_dir" init -q
  git -C "$seed_dir" config user.email "ci@example.test"
  git -C "$seed_dir" config user.name "CI"
  printf "base\n" > "$seed_dir/file.txt"
  git -C "$seed_dir" add file.txt
  git -C "$seed_dir" commit -qm "base"
  git -C "$seed_dir" branch "$branch_name"
  git -C "$seed_dir" remote add origin "$remote_dir"
  git -C "$seed_dir" push -q origin "$branch_name"

  cp "$project_root/scripts/ai-review-loop.sh" "$repo_dir/scripts/ai-review-loop.sh"
  cp "$project_root/scripts/ai-review-prompts/review.md" "$repo_dir/scripts/ai-review-prompts/review.md"
  cp "$project_root/scripts/ai-review-prompts/fix.md" "$repo_dir/scripts/ai-review-prompts/fix.md"
  chmod +x "$repo_dir/scripts/ai-review-loop.sh"

  git -C "$repo_dir" init -q
  git -C "$repo_dir" config user.email "ci@example.test"
  git -C "$repo_dir" config user.name "CI"
  printf "work\n" > "$repo_dir/file.txt"
  git -C "$repo_dir" add file.txt
  git -C "$repo_dir" commit -qm "work"
  git -C "$repo_dir" remote add origin "$remote_dir"

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

  prompt="$(cat)"
  printf "%s" "$prompt" > "${PROMPT_CAPTURE}"

  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"
  write_successful_bmad_gh_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    PROMPT_CAPTURE="$prompt_capture" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_BASE="$branch_name" \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_VERIFY_CMD=true \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "cd '$repo_dir' && ./scripts/ai-review-loop.sh 2>&1"

  assert_success
  assert_output --partial "AI review PASS."

  run grep -F "refs/remotes/origin/${branch_name}" "$prompt_capture"
  assert_success
}

@test "bmad-fr-nfr-review-gate requires a spec path" {
  run ./scripts/bmad-fr-nfr-review-gate.sh
  assert_failure
  assert_output --partial "Error: --spec or BMAD_REVIEW_SPEC_PATH is required."
}

@test "bmad-fr-nfr-review-gate requires option values" {
  run ./scripts/bmad-fr-nfr-review-gate.sh --spec
  assert_failure
  assert_output --partial "Error: --spec requires a value."

  run ./scripts/bmad-fr-nfr-review-gate.sh --spec --base HEAD
  assert_failure
  assert_output --partial "Error: --spec requires a value."
}

@test "bmad-fr-nfr-review-gate fails before agent invocation when manual evidence path is missing" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/example"
  local missing_evidence_file="${BATS_TEST_TMPDIR}/missing/manual-evidence.md"
  local invocation_marker="${BATS_TEST_TMPDIR}/codex-invoked"

  mkdir -p "$bin_dir" "$spec_dir"
  printf "# PRD\n\nFR-01: Works.\n" > "${spec_dir}/prd.md"

  cat > "$bin_dir/codex" <<'SCRIPT'
#!/usr/bin/env bash
touch "${CODEX_INVOCATION_MARKER}"
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"

  run env \
    PATH="$bin_dir:$PATH" \
    CODEX_INVOCATION_MARKER="$invocation_marker" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/bmad-fr-nfr-review-gate.sh --spec '${spec_dir}' --manual-evidence '${missing_evidence_file}' 2>&1"

  assert_failure
  assert_output --partial "Error: Manual evidence path not found: ${missing_evidence_file}"

  if [ -e "$invocation_marker" ]; then
    fail "Expected missing manual evidence validation to stop before Codex invocation."
  fi
}

@test "ai-review-loop substitutes BMAD review gate placeholders" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/path & example"
  local evidence_file="${BATS_TEST_TMPDIR}/manual & evidence.md"
  local prompt_capture="${BATS_TEST_TMPDIR}/prompt.txt"

  mkdir -p "$bin_dir" "$spec_dir"
  printf "# PRD\n\nFR-01: Works.\n" > "${spec_dir}/prd.md"
  printf "# Manual Evidence\n\nTester: QA\n" > "$evidence_file"

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

  prompt="$(cat)"
  printf "%s" "$prompt" > "${PROMPT_CAPTURE}"

  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
FR_NFR_SCORECARD: PASS
NFR_CATALOG_SCORECARD: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: APPROVED
CI_CHECK_ROLLUP: PASSING

Requirement Scorecard:
- FR-01 evidence: 5/5 PASS

NFR Catalog Scorecard:
- Performance: 5/5 PASS
- Usability: 5/5 PASS
- Maintainability: 5/5 PASS
- Availability: 5/5 PASS
- Interoperability: 5/5 PASS
- Security: 5/5 PASS
- Manageability: 5/5 PASS
- Automatability: 5/5 PASS
- Dependability: 5/5 PASS
- Maintainability evidence: 5/5 PASS

Manual Test Evidence:
- Manual evidence reviewed: 5/5 PASS
- Not applicable for shell validation fixture.

QA Verification:
- QA verification completed: 5/5 PASS
- make ci: PASS

GitHub Completion Gate:
- GitHub completion verified: 5/5 PASS
- reviewDecision APPROVED, unresolved threads 0.

CI Gate:
- Required CI checks verified: 5/5 PASS
- statusCheckRollup PASSING.
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"
  write_successful_bmad_gh_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    PROMPT_CAPTURE="$prompt_capture" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_REVIEW_PROMPT=scripts/ai-review-prompts/bmad-fr-nfr-review.md \
    AI_REVIEW_FIX_PROMPT=scripts/ai-review-prompts/bmad-fr-nfr-fix.md \
    AI_REVIEW_SPEC_PATH="$spec_dir" \
    AI_REVIEW_MANUAL_EVIDENCE="$evidence_file" \
    AI_REVIEW_PR_NUMBER=123 \
    AI_REVIEW_BASE=HEAD \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_REQUIRE_GATE_MARKERS=true \
    AI_REVIEW_VERIFY_ON_PASS=true \
    AI_REVIEW_VERIFY_CMD=true \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_success
  assert_output --partial "AI review PASS."

  run grep -F "BMAD spec source at \`${spec_dir}\`" "$prompt_capture"
  assert_success
  run grep -F "Manual test evidence is at \`${evidence_file}\`" "$prompt_capture"
  assert_success
  run grep -F "Use PR \`123\`" "$prompt_capture"
  assert_success
  run grep -F "Performance, Usability, Maintainability, Availability, Interoperability, Security, Manageability, Automatability, Dependability" "$prompt_capture"
  assert_success
}

@test "ai-review-loop accepts CRLF gate markers" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/example"

  mkdir -p "$bin_dir" "$spec_dir"
  printf "# PRD\n\nFR-01: Works.\n" > "${spec_dir}/prd.md"

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
  {
    printf 'STATUS: PASS\r\n'
    printf '0 issues.\r\n'
    printf 'FR_NFR_SCORECARD: PASS\r\n'
    printf 'NFR_CATALOG_SCORECARD: PASS\r\n'
    printf 'MANUAL_TEST_EVIDENCE: PASS\r\n'
    printf 'QA_BEST_PRACTICES: PASS\r\n'
    printf 'GITHUB_COMPLETION_GATE: PASS\r\n'
    printf 'CI_GATE: PASS\r\n'
  } > "$output_file"
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_SPEC_PATH="$spec_dir" \
    AI_REVIEW_BASE=HEAD \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_REQUIRE_GATE_MARKERS=true \
    AI_REVIEW_VERIFY_ON_PASS=true \
    AI_REVIEW_VERIFY_CMD=true \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_success
  assert_output --partial "AI review PASS."
}

@test "ai-review-loop accepts required gate markers that begin with a dash" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"

  mkdir -p "$bin_dir"

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
  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
-CUSTOM_MARKER: PASS
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_BASE=HEAD \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_REQUIRE_GATE_MARKERS=true \
    AI_REVIEW_REQUIRED_GATE_MARKERS="-CUSTOM_MARKER: PASS" \
    AI_REVIEW_VERIFY_CMD=true \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_success
  assert_output --partial "AI review PASS."
}

@test "ai-review-loop rejects gate PASS without required markers" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/example"

  mkdir -p "$bin_dir" "$spec_dir"
  printf "# PRD\n\nFR-01: Works.\n" > "${spec_dir}/prd.md"

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
  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_SPEC_PATH="$spec_dir" \
    AI_REVIEW_BASE=HEAD \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_REQUIRE_GATE_MARKERS=true \
    AI_REVIEW_VERIFY_CMD=true \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "Warning: PASS output is missing required gate marker: FR_NFR_SCORECARD: PASS"
  assert_output --partial "Reached AI_REVIEW_MAX_ITER=1 without PASS."
}

@test "make bmad-fr-nfr-review-gate accepts AI_REVIEW_SPEC_PATH fallback" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/example"

  mkdir -p "$bin_dir" "$spec_dir"
  printf "# PRD\n\nFR-01: Works.\n" > "${spec_dir}/prd.md"

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
  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
FR_NFR_SCORECARD: PASS
NFR_CATALOG_SCORECARD: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: APPROVED
CI_CHECK_ROLLUP: PASSING

Requirement Scorecard:
- FR-01 evidence: 5/5 PASS

NFR Catalog Scorecard:
- Performance: 5/5 PASS
- Usability: 5/5 PASS
- Maintainability: 5/5 PASS
- Availability: 5/5 PASS
- Interoperability: 5/5 PASS
- Security: 5/5 PASS
- Manageability: 5/5 PASS
- Automatability: 5/5 PASS
- Dependability: 5/5 PASS
- Maintainability evidence: 5/5 PASS

Manual Test Evidence:
- Manual evidence reviewed: 5/5 PASS
- Not applicable for shell validation fixture.

QA Verification:
- QA verification completed: 5/5 PASS
- make ci: PASS

GitHub Completion Gate:
- GitHub completion verified: 5/5 PASS
- reviewDecision APPROVED, unresolved threads 0.

CI Gate:
- Required CI checks verified: 5/5 PASS
- statusCheckRollup PASSING.
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"
  write_successful_bmad_gh_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_SPEC_PATH="$spec_dir" \
    BMAD_REVIEW_BASE=HEAD \
    BMAD_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    BMAD_REVIEW_VERIFY_CMD=true \
    BMAD_REVIEW_MAX_ITER=1 \
    bash -c "make bmad-fr-nfr-review-gate 2>&1"

  assert_success
  assert_output --partial "AI review PASS."
}

@test "bmad-fr-nfr-review-gate ignores ambient AI_REVIEW target vars" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/example"
  local verify_marker="${BATS_TEST_TMPDIR}/verify-ran"
  local prompt_capture="${BATS_TEST_TMPDIR}/prompt.txt"
  local stale_evidence_file="${BATS_TEST_TMPDIR}/missing/manual-evidence.md"

  mkdir -p "$bin_dir" "$spec_dir"
  printf "# PRD\n\nFR-01: Works.\n" > "${spec_dir}/prd.md"

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

  prompt="$(cat)"
  printf "%s" "$prompt" > "${PROMPT_CAPTURE}"

  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
FR_NFR_SCORECARD: PASS
NFR_CATALOG_SCORECARD: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: APPROVED
CI_CHECK_ROLLUP: PASSING

Requirement Scorecard:
- FR-01 evidence: 5/5 PASS

NFR Catalog Scorecard:
- Performance: 5/5 PASS
- Usability: 5/5 PASS
- Maintainability: 5/5 PASS
- Availability: 5/5 PASS
- Interoperability: 5/5 PASS
- Security: 5/5 PASS
- Manageability: 5/5 PASS
- Automatability: 5/5 PASS
- Dependability: 5/5 PASS
- Maintainability evidence: 5/5 PASS

Manual Test Evidence:
- Manual evidence reviewed: 5/5 PASS
- Not applicable for shell validation fixture.

QA Verification:
- QA verification completed: 5/5 PASS
- make ci: PASS

GitHub Completion Gate:
- GitHub completion verified: 5/5 PASS
- reviewDecision APPROVED, unresolved threads 0.

CI Gate:
- Required CI checks verified: 5/5 PASS
- statusCheckRollup PASSING.
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"
  write_successful_bmad_gh_stub "$bin_dir"

  cat > "$bin_dir/make" <<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail
printf "%s\n" "$*" > "${VERIFY_MARKER}"
[[ "$*" == "ci" ]]
SCRIPT
  chmod +x "$bin_dir/make"

  run env \
    PATH="$bin_dir:$PATH" \
    PROMPT_CAPTURE="$prompt_capture" \
    VERIFY_MARKER="$verify_marker" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_MANUAL_EVIDENCE="$stale_evidence_file" \
    AI_REVIEW_PR_NUMBER=999 \
    AI_REVIEW_BASE=missing-base \
    AI_REVIEW_VERIFY_CMD=false \
    BMAD_REVIEW_SPEC_PATH="$spec_dir" \
    BMAD_REVIEW_BASE=HEAD \
    BMAD_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    BMAD_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/bmad-fr-nfr-review-gate.sh 2>&1"

  assert_success
  assert_output --partial "AI review PASS."

  run cat "$verify_marker"
  assert_output "ci"

  run grep -F 'Use PR `AUTO_DETECT`' "$prompt_capture"
  assert_success
  run grep -F 'Manual test evidence is at `NOT_PROVIDED`' "$prompt_capture"
  assert_success
  run grep -F "$stale_evidence_file" "$prompt_capture"
  assert_failure
}

@test "bmad-fr-nfr-review-gate ignores ambient AI_REVIEW agent vars" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/example"

  mkdir -p "$bin_dir" "$spec_dir"
  printf "# PRD\n\nFR-01: Works.\n" > "${spec_dir}/prd.md"

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
  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
FR_NFR_SCORECARD: PASS
NFR_CATALOG_SCORECARD: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: APPROVED
CI_CHECK_ROLLUP: PASSING

Requirement Scorecard:
- FR-01 evidence: 5/5 PASS

NFR Catalog Scorecard:
- Performance: 5/5 PASS
- Usability: 5/5 PASS
- Maintainability: 5/5 PASS
- Availability: 5/5 PASS
- Interoperability: 5/5 PASS
- Security: 5/5 PASS
- Manageability: 5/5 PASS
- Automatability: 5/5 PASS
- Dependability: 5/5 PASS
- Maintainability evidence: 5/5 PASS

Manual Test Evidence:
- Manual evidence reviewed: 5/5 PASS
- Not applicable for shell validation fixture.

QA Verification:
- QA verification completed: 5/5 PASS
- make ci: PASS

GitHub Completion Gate:
- GitHub completion verified: 5/5 PASS
- reviewDecision APPROVED, unresolved threads 0.

CI Gate:
- Required CI checks verified: 5/5 PASS
- statusCheckRollup PASSING.
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"

  cat > "$bin_dir/claude" <<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail
echo "ambient AI_REVIEW_AGENT should not be used" >&2
exit 42
SCRIPT
  chmod +x "$bin_dir/claude"
  write_successful_bmad_gh_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_CLAUDE_CMD=claude \
    AI_REVIEW_AGENT=claude \
    AI_REVIEW_FIX_AGENT=claude \
    BMAD_REVIEW_SPEC_PATH="$spec_dir" \
    BMAD_REVIEW_BASE=HEAD \
    BMAD_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    BMAD_REVIEW_VERIFY_CMD=true \
    BMAD_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/bmad-fr-nfr-review-gate.sh 2>&1"

  assert_success
  assert_output --partial "AI review PASS."
}

@test "bmad-fr-nfr-review-gate resolves paths outside the repo cwd" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/example"
  local repo_root
  repo_root="$(pwd)"

  mkdir -p "$bin_dir" "$spec_dir"
  printf "# PRD\n\nFR-01: Works.\n" > "${spec_dir}/prd.md"

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
  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
FR_NFR_SCORECARD: PASS
NFR_CATALOG_SCORECARD: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: APPROVED
CI_CHECK_ROLLUP: PASSING

Requirement Scorecard:
- FR-01 evidence: 5/5 PASS

NFR Catalog Scorecard:
- Performance: 5/5 PASS
- Usability: 5/5 PASS
- Maintainability: 5/5 PASS
- Availability: 5/5 PASS
- Interoperability: 5/5 PASS
- Security: 5/5 PASS
- Manageability: 5/5 PASS
- Automatability: 5/5 PASS
- Dependability: 5/5 PASS
- Maintainability evidence: 5/5 PASS

Manual Test Evidence:
- Manual evidence reviewed: 5/5 PASS
- Not applicable for shell validation fixture.

QA Verification:
- QA verification completed: 5/5 PASS
- make ci: PASS

GitHub Completion Gate:
- GitHub completion verified: 5/5 PASS
- reviewDecision APPROVED, unresolved threads 0.

CI Gate:
- Required CI checks verified: 5/5 PASS
- statusCheckRollup PASSING.
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"
  write_successful_bmad_gh_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    BMAD_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    BMAD_REVIEW_VERIFY_CMD=true \
    BMAD_REVIEW_MAX_ITER=1 \
    bash -c "cd '${BATS_TEST_TMPDIR}' && '${repo_root}/scripts/bmad-fr-nfr-review-gate.sh' --spec specs/example --base HEAD 2>&1"

  assert_success
  assert_output --partial "AI review PASS."
}

@test "bmad-fr-nfr-review-gate make target forces pinned BMAD constants" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/example"
  local prompt_capture="${BATS_TEST_TMPDIR}/prompt.txt"

  mkdir -p "$bin_dir" "$spec_dir"
  printf "# PRD\n\nFR-01: Works.\n" > "${spec_dir}/prd.md"

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

  prompt="$(cat)"
  printf "%s" "$prompt" > "${PROMPT_CAPTURE}"

  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
FR_NFR_SCORECARD: PASS
NFR_CATALOG_SCORECARD: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: APPROVED
CI_CHECK_ROLLUP: PASSING

Requirement Scorecard:
- FR-01 evidence: 5/5 PASS

NFR Catalog Scorecard:
- Performance: 5/5 PASS
- Usability: 5/5 PASS
- Maintainability: 5/5 PASS
- Availability: 5/5 PASS
- Interoperability: 5/5 PASS
- Security: 5/5 PASS
- Manageability: 5/5 PASS
- Automatability: 5/5 PASS
- Dependability: 5/5 PASS
- Maintainability evidence: 5/5 PASS

Manual Test Evidence:
- Manual evidence reviewed: 5/5 PASS
- Not applicable for shell validation fixture.

QA Verification:
- QA verification completed: 5/5 PASS
- make ci: PASS

GitHub Completion Gate:
- GitHub completion verified: 5/5 PASS
- reviewDecision APPROVED, unresolved threads 0.

CI Gate:
- Required CI checks verified: 5/5 PASS
- statusCheckRollup PASSING.
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"
  write_successful_bmad_gh_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    PROMPT_CAPTURE="$prompt_capture" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_SCORE_THRESHOLD=4 \
    AI_REVIEW_NFR_CATEGORIES="Security" \
    AI_REVIEW_REQUIRED_GATE_MARKERS="FR_NFR_SCORECARD: PASS" \
    BMAD_REVIEW_SPEC_PATH="$spec_dir" \
    BMAD_REVIEW_PR=123 \
    BMAD_REVIEW_BASE=HEAD \
    BMAD_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    BMAD_REVIEW_VERIFY_CMD=true \
    BMAD_REVIEW_MAX_ITER=1 \
    bash -c "make bmad-fr-nfr-review-gate 2>&1"

  assert_success
  assert_output --partial "AI review PASS."

  run grep -F "Use PR \`123\`" "$prompt_capture"
  assert_success
  run grep -F "Passing threshold: every applicable FR, NFR, catalog category, QA checkpoint," "$prompt_capture"
  assert_success
  run grep -F "\`5/5\`" "$prompt_capture"
  assert_success
  run grep -F "Performance, Usability, Maintainability, Availability, Interoperability, Security, Manageability, Automatability, Dependability" "$prompt_capture"
  assert_success
}

@test "bmad-fr-nfr-review-gate rejects shortened required marker override" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/example"

  mkdir -p "$bin_dir" "$spec_dir"
  printf "# PRD\n\nFR-01: Works.\n" > "${spec_dir}/prd.md"

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
  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
FR_NFR_SCORECARD: PASS
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"
  write_successful_bmad_gh_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_REQUIRED_GATE_MARKERS="FR_NFR_SCORECARD: PASS" \
    BMAD_REVIEW_SPEC_PATH="$spec_dir" \
    BMAD_REVIEW_BASE=HEAD \
    BMAD_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    BMAD_REVIEW_VERIFY_CMD=true \
    BMAD_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/bmad-fr-nfr-review-gate.sh 2>&1"

  assert_failure
  assert_output --partial "Warning: PASS output is missing required gate marker: NFR_CATALOG_SCORECARD: PASS"
  assert_output --partial "Reached AI_REVIEW_MAX_ITER=1 without PASS."
}

@test "bmad-fr-nfr-review-gate rejects marker-only PASS without scorecard evidence" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/example"

  mkdir -p "$bin_dir" "$spec_dir"
  printf "# PRD\n\nFR-01: Works.\n" > "${spec_dir}/prd.md"

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
  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
FR_NFR_SCORECARD: PASS
NFR_CATALOG_SCORECARD: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"
  write_successful_bmad_gh_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    BMAD_REVIEW_SPEC_PATH="$spec_dir" \
    BMAD_REVIEW_BASE=HEAD \
    BMAD_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    BMAD_REVIEW_VERIFY_CMD=true \
    BMAD_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/bmad-fr-nfr-review-gate.sh 2>&1"

  assert_failure
  assert_output --partial "Warning: BMAD PASS output is missing required evidence marker: FR_NFR_MIN_SCORE: 5/5"
  assert_output --partial "Reached AI_REVIEW_MAX_ITER=1 without PASS."
}

@test "bmad-fr-nfr-review-gate rejects PASS scorecards below 5" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/example"

  mkdir -p "$bin_dir" "$spec_dir"
  printf "# PRD\n\nFR-01: Works.\n" > "${spec_dir}/prd.md"

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
  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
FR_NFR_SCORECARD: PASS
NFR_CATALOG_SCORECARD: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: APPROVED
CI_CHECK_ROLLUP: PASSING

Requirement Scorecard:
- FR-01 evidence: 0/5 PASS

NFR Catalog Scorecard:
- Performance: 5/5 PASS
- Usability: 5/5 PASS
- Maintainability: 5/5 PASS
- Availability: 5/5 PASS
- Interoperability: 5/5 PASS
- Security: 5/5 PASS
- Manageability: 5/5 PASS
- Automatability: 5/5 PASS
- Dependability: 5/5 PASS
Performance: 5/5 PASS

Manual Test Evidence:
- Manual evidence reviewed: 5/5 PASS
- Not applicable for shell validation fixture.

QA Verification:
- QA verification completed: 5/5 PASS
- make ci: PASS

GitHub Completion Gate:
- GitHub completion verified: 5/5 PASS
- reviewDecision APPROVED, unresolved threads 0.

CI Gate:
- Required CI checks verified: 5/5 PASS
- statusCheckRollup PASSING.
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"
  write_successful_bmad_gh_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    BMAD_REVIEW_SPEC_PATH="$spec_dir" \
    BMAD_REVIEW_BASE=HEAD \
    BMAD_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    BMAD_REVIEW_VERIFY_CMD=true \
    BMAD_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/bmad-fr-nfr-review-gate.sh 2>&1"

  assert_failure
  assert_output --partial "Warning: BMAD PASS output contains a score below 5/5 in Requirement Scorecard."
  assert_output --partial "Reached AI_REVIEW_MAX_ITER=1 without PASS."
}

@test "ai-review-loop scorecard validation honors configured threshold" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  mkdir -p "$bin_dir"

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
  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
FR_NFR_SCORECARD: PASS
NFR_CATALOG_SCORECARD: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 4/5
NFR_CATALOG_MIN_SCORE: 4/5
GITHUB_COMPLETION_STATE: APPROVED
CI_CHECK_ROLLUP: PASSING

Requirement Scorecard:
- FR-01 evidence: 5/5 PASS

NFR Catalog Scorecard:
- Performance: 5/5 PASS
- Usability: 5/5 PASS
- Maintainability: 5/5 PASS
- Availability: 5/5 PASS
- Interoperability: 5/5 PASS
- Security: 5/5 PASS
- Manageability: 5/5 PASS
- Automatability: 5/5 PASS
- Dependability: 5/5 PASS
- Maintainability evidence: 5/5 PASS

Manual Test Evidence:
- Manual evidence reviewed: 5/5 PASS
- Not applicable for shell validation fixture.

QA Verification:
- QA verification completed: 5/5 PASS
- make ci: PASS

GitHub Completion Gate:
- GitHub completion verified: 5/5 PASS
- Not required for this generic loop fixture.

CI Gate:
- Required CI checks verified: 5/5 PASS
- Not required for this generic loop fixture.
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_BASE=HEAD \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_VERIFY_CMD=true \
    AI_REVIEW_REQUIRE_GATE_MARKERS=true \
    AI_REVIEW_REQUIRE_SCORECARD_VALIDATION=true \
    AI_REVIEW_SCORE_THRESHOLD=4 \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_success
  assert_output --partial "AI review PASS."
}

@test "ai-review-loop rejects PASS without scored gate sections" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  mkdir -p "$bin_dir"

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
  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
FR_NFR_SCORECARD: PASS
NFR_CATALOG_SCORECARD: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: APPROVED
CI_CHECK_ROLLUP: PASSING

Requirement Scorecard:
- FR-01 evidence: 5/5 PASS

NFR Catalog Scorecard:
- Performance: 5/5 PASS
- Usability: 5/5 PASS
- Maintainability: 5/5 PASS
- Availability: 5/5 PASS
- Interoperability: 5/5 PASS
- Security: 5/5 PASS
- Manageability: 5/5 PASS
- Automatability: 5/5 PASS
- Dependability: 5/5 PASS

Manual Test Evidence:
- Evidence exists but is not scored.

QA Verification:
- QA verification completed: 5/5 PASS

GitHub Completion Gate:
- GitHub completion verified: 5/5 PASS

CI Gate:
- Required CI checks verified: 5/5 PASS
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_BASE=HEAD \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_VERIFY_CMD=true \
    AI_REVIEW_REQUIRE_GATE_MARKERS=true \
    AI_REVIEW_REQUIRE_SCORECARD_VALIDATION=true \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "Warning: BMAD PASS output lacks 5/5 evidence in Manual Test Evidence."
}

@test "ai-review-loop rejects PASS without pinned NFR category coverage" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  mkdir -p "$bin_dir"

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
  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
FR_NFR_SCORECARD: PASS
NFR_CATALOG_SCORECARD: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: APPROVED
CI_CHECK_ROLLUP: PASSING

Requirement Scorecard:
- FR-01 evidence: 5/5 PASS

NFR Catalog Scorecard:
- Performance: 5/5 PASS

Manual Test Evidence:
- Manual evidence reviewed: 5/5 PASS

QA Verification:
- QA verification completed: 5/5 PASS

GitHub Completion Gate:
- GitHub completion verified: 5/5 PASS

CI Gate:
- Required CI checks verified: 5/5 PASS
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_BASE=HEAD \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_VERIFY_CMD=true \
    AI_REVIEW_REQUIRE_GATE_MARKERS=true \
    AI_REVIEW_REQUIRE_SCORECARD_VALIDATION=true \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "Warning: BMAD PASS output lacks 5/5 evidence for NFR category: Usability."
}

@test "ai-review-loop accepts pinned NFR category coverage in markdown table rows" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  mkdir -p "$bin_dir"

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
  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
FR_NFR_SCORECARD: PASS
NFR_CATALOG_SCORECARD: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: APPROVED
CI_CHECK_ROLLUP: PASSING

Requirement Scorecard:
| Source | Evidence | Score | Status |
| --- | --- | --- | --- |
| FR-01 | Verified by tests. | 5/5 | PASS |

NFR Catalog Scorecard:
| Category | Evidence | Score | Status |
| --- | --- | --- | --- |
| Performance | Lightweight wrapper and bounded validation. | 5/5 | PASS |
| Usability | Make target and docs. | 5/5 | PASS |
| Maintainability | Focused shell functions and Bats coverage. | 5/5 | PASS |
| Availability | Fails closed on unavailable gates. | 5/5 | PASS |
| Interoperability | Codex, Claude, GitHub CLI, and BMAD specs. | 5/5 | PASS |
| Security | Review control env is sanitized. | 5/5 | PASS |
| Manageability | Inputs are configurable through BMAD env. | 5/5 | PASS |
| Automatability | Non-interactive Make target. | 5/5 | PASS |
| Dependability | Invalid markers and scores fail closed. | 5/5 | PASS |

Manual Test Evidence:
- Manual evidence reviewed: 5/5 PASS

QA Verification:
- QA verification completed: 5/5 PASS

GitHub Completion Gate:
- GitHub completion verified: 5/5 PASS

CI Gate:
- Required CI checks verified: 5/5 PASS
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_BASE=HEAD \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_VERIFY_CMD=true \
    AI_REVIEW_REQUIRE_GATE_MARKERS=true \
    AI_REVIEW_REQUIRE_SCORECARD_VALIDATION=true \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_success
  assert_output --partial "AI review PASS."
}

@test "bmad-fr-nfr-review-gate ignores scorecard title mentions outside scorecard sections" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/example"

  mkdir -p "$bin_dir" "$spec_dir"
  printf "# PRD\n\nFR-01: Works.\n" > "${spec_dir}/prd.md"

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
  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
FR_NFR_SCORECARD: PASS
NFR_CATALOG_SCORECARD: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: APPROVED
CI_CHECK_ROLLUP: PASSING

Review Notes:
- A draft mention of Requirement Scorecard: 0/5 should not be parsed as the scorecard section.

Requirement Scorecard:
- FR-01 evidence: 5/5 PASS

NFR Catalog Scorecard:
- Performance: 5/5 PASS
- Usability: 5/5 PASS
- Maintainability: 5/5 PASS
- Availability: 5/5 PASS
- Interoperability: 5/5 PASS
- Security: 5/5 PASS
- Manageability: 5/5 PASS
- Automatability: 5/5 PASS
- Dependability: 5/5 PASS
- Maintainability evidence: 5/5 PASS

Manual Test Evidence:
- Manual evidence reviewed: 5/5 PASS
- Not applicable for shell validation fixture.

QA Verification:
- QA verification completed: 5/5 PASS
- make ci: PASS

GitHub Completion Gate:
- GitHub completion verified: 5/5 PASS
- reviewDecision APPROVED, unresolved threads 0.

CI Gate:
- Required CI checks verified: 5/5 PASS
- statusCheckRollup PASSING.
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"
  write_successful_bmad_gh_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    BMAD_REVIEW_SPEC_PATH="$spec_dir" \
    BMAD_REVIEW_BASE=HEAD \
    BMAD_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    BMAD_REVIEW_VERIFY_CMD=true \
    BMAD_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/bmad-fr-nfr-review-gate.sh 2>&1"

  assert_success
  assert_output --partial "AI review PASS."
}

@test "bmad-fr-nfr-review-gate ignores non-score dates when validating scorecards" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/example"

  mkdir -p "$bin_dir" "$spec_dir"
  printf "# PRD\n\nFR-01: Works.\n" > "${spec_dir}/prd.md"

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
  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
FR_NFR_SCORECARD: PASS
NFR_CATALOG_SCORECARD: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: APPROVED
CI_CHECK_ROLLUP: PASSING

Requirement Scorecard:
- FR-01 evidence: 5/5 PASS

NFR Catalog Scorecard:
- Performance: 5/5 PASS
- Usability: 5/5 PASS
- Maintainability: 5/5 PASS
- Availability: 5/5 PASS
- Interoperability: 5/5 PASS
- Security: 5/5 PASS
- Manageability: 5/5 PASS
- Automatability: 5/5 PASS
- Dependability: 5/5 PASS
Performance: 5/5 PASS

Manual Test Evidence:
- Manual evidence reviewed: 5/5 PASS
- Manual session date 1/5/2026; not a score.

QA Verification:
- QA verification completed: 5/5 PASS
- make ci: PASS

GitHub Completion Gate:
- GitHub completion verified: 5/5 PASS
- reviewDecision APPROVED, unresolved threads 0.

CI Gate:
- Required CI checks verified: 5/5 PASS
- statusCheckRollup PASSING.
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"
  write_successful_bmad_gh_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    BMAD_REVIEW_SPEC_PATH="$spec_dir" \
    BMAD_REVIEW_BASE=HEAD \
    BMAD_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    BMAD_REVIEW_VERIFY_CMD=true \
    BMAD_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/bmad-fr-nfr-review-gate.sh 2>&1"

  assert_success
  assert_output --partial "AI review PASS."
}

@test "bmad-fr-nfr-review-gate rejects PASS when GitHub checks are not passing" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/example"

  mkdir -p "$bin_dir" "$spec_dir"
  printf "# PRD\n\nFR-01: Works.\n" > "${spec_dir}/prd.md"

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
  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
FR_NFR_SCORECARD: PASS
NFR_CATALOG_SCORECARD: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: APPROVED
CI_CHECK_ROLLUP: PASSING

Requirement Scorecard:
- FR-01 evidence: 5/5 PASS

NFR Catalog Scorecard:
- Performance: 5/5 PASS
- Usability: 5/5 PASS
- Maintainability: 5/5 PASS
- Availability: 5/5 PASS
- Interoperability: 5/5 PASS
- Security: 5/5 PASS
- Manageability: 5/5 PASS
- Automatability: 5/5 PASS
- Dependability: 5/5 PASS
- Maintainability evidence: 5/5 PASS

Manual Test Evidence:
- Manual evidence reviewed: 5/5 PASS
- Not applicable for shell validation fixture.

QA Verification:
- QA verification completed: 5/5 PASS
- make ci: PASS

GitHub Completion Gate:
- GitHub completion verified: 5/5 PASS
- reviewDecision APPROVED, unresolved threads 0.

CI Gate:
- Required CI checks verified: 5/5 PASS
- statusCheckRollup PASSING.
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"
  write_failing_check_bmad_gh_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    BMAD_REVIEW_SPEC_PATH="$spec_dir" \
    BMAD_REVIEW_BASE=HEAD \
    BMAD_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    BMAD_REVIEW_VERIFY_CMD=true \
    BMAD_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/bmad-fr-nfr-review-gate.sh 2>&1"

  assert_failure
  assert_output --partial "Warning: GitHub required PR checks are not fully passing: Run Bats Core Tests"
  assert_output --partial "GitHub corroboration failed before AI review."
  refute_output --partial "Reached AI_REVIEW_MAX_ITER=1 without PASS."
}

@test "bmad-fr-nfr-review-gate falls back to visible checks when required check rollup is empty" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/example"

  mkdir -p "$bin_dir" "$spec_dir"
  printf "# PRD\n\nFR-01: Works.\n" > "${spec_dir}/prd.md"
  write_bmad_pass_codex_stub "$bin_dir"
  write_empty_required_passing_visible_bmad_gh_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    BMAD_REVIEW_SPEC_PATH="$spec_dir" \
    BMAD_REVIEW_BASE=HEAD \
    BMAD_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    BMAD_REVIEW_VERIFY_CMD=true \
    BMAD_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/bmad-fr-nfr-review-gate.sh 2>&1"

  assert_success
  assert_output --partial "AI review PASS."
}

@test "bmad-fr-nfr-review-gate rejects PASS when visible GitHub check rollup is empty" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/example"

  mkdir -p "$bin_dir" "$spec_dir"
  printf "# PRD\n\nFR-01: Works.\n" > "${spec_dir}/prd.md"
  write_bmad_pass_codex_stub "$bin_dir"
  write_empty_required_empty_visible_bmad_gh_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    BMAD_REVIEW_SPEC_PATH="$spec_dir" \
    BMAD_REVIEW_BASE=HEAD \
    BMAD_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    BMAD_REVIEW_VERIFY_CMD=true \
    BMAD_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/bmad-fr-nfr-review-gate.sh 2>&1"

  assert_failure
  assert_output --partial "Warning: GitHub PR check rollup is empty."
  assert_output --partial "GitHub corroboration failed before AI review."
  refute_output --partial "Reached AI_REVIEW_MAX_ITER=1 without PASS."
}

@test "bmad-fr-nfr-review-gate rejects PASS when fallback visible GitHub checks are not passing" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/example"

  mkdir -p "$bin_dir" "$spec_dir"
  printf "# PRD\n\nFR-01: Works.\n" > "${spec_dir}/prd.md"
  write_bmad_pass_codex_stub "$bin_dir"
  write_empty_required_failing_visible_bmad_gh_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    BMAD_REVIEW_SPEC_PATH="$spec_dir" \
    BMAD_REVIEW_BASE=HEAD \
    BMAD_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    BMAD_REVIEW_VERIFY_CMD=true \
    BMAD_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/bmad-fr-nfr-review-gate.sh 2>&1"

  assert_failure
  assert_output --partial "Warning: GitHub PR checks are not fully passing: Run Bats Core Tests"
  assert_output --partial "GitHub corroboration failed before AI review."
  refute_output --partial "Reached AI_REVIEW_MAX_ITER=1 without PASS."
}

@test "bmad-fr-nfr-review-gate rejects PASS when PR head differs from local HEAD" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/example"

  mkdir -p "$bin_dir" "$spec_dir"
  printf "# PRD\n\nFR-01: Works.\n" > "${spec_dir}/prd.md"
  write_bmad_pass_codex_stub "$bin_dir"
  write_stale_head_bmad_gh_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    BMAD_REVIEW_SPEC_PATH="$spec_dir" \
    BMAD_REVIEW_BASE=HEAD \
    BMAD_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    BMAD_REVIEW_VERIFY_CMD=true \
    BMAD_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/bmad-fr-nfr-review-gate.sh 2>&1"

  assert_failure
  assert_output --partial "does not match local HEAD"
  assert_output --partial "GitHub corroboration failed before AI review."
  refute_output --partial "Reached AI_REVIEW_MAX_ITER=1 without PASS."
}

@test "bmad-fr-nfr-review-gate rejects PASS when GitHub checks are skipped" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/example"

  mkdir -p "$bin_dir" "$spec_dir"
  printf "# PRD\n\nFR-01: Works.\n" > "${spec_dir}/prd.md"
  write_bmad_pass_codex_stub "$bin_dir"
  write_skipped_check_bmad_gh_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    BMAD_REVIEW_SPEC_PATH="$spec_dir" \
    BMAD_REVIEW_BASE=HEAD \
    BMAD_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    BMAD_REVIEW_VERIFY_CMD=true \
    BMAD_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/bmad-fr-nfr-review-gate.sh 2>&1"

  assert_failure
  assert_output --partial "Warning: GitHub required PR checks are not fully passing: Optional Docs"
  assert_output --partial "GitHub corroboration failed before AI review."
  refute_output --partial "Reached AI_REVIEW_MAX_ITER=1 without PASS."
}

@test "bmad-fr-nfr-review-gate checks paginated unresolved review threads" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/example"

  mkdir -p "$bin_dir" "$spec_dir"
  printf "# PRD\n\nFR-01: Works.\n" > "${spec_dir}/prd.md"

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
  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
FR_NFR_SCORECARD: PASS
NFR_CATALOG_SCORECARD: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: APPROVED
CI_CHECK_ROLLUP: PASSING

Requirement Scorecard:
- FR-01 evidence: 5/5 PASS

NFR Catalog Scorecard:
- Performance: 5/5 PASS
- Usability: 5/5 PASS
- Maintainability: 5/5 PASS
- Availability: 5/5 PASS
- Interoperability: 5/5 PASS
- Security: 5/5 PASS
- Manageability: 5/5 PASS
- Automatability: 5/5 PASS
- Dependability: 5/5 PASS
- Maintainability evidence: 5/5 PASS

Manual Test Evidence:
- Manual evidence reviewed: 5/5 PASS
- Not applicable for shell validation fixture.

QA Verification:
- QA verification completed: 5/5 PASS
- make ci: PASS

GitHub Completion Gate:
- GitHub completion verified: 5/5 PASS
- reviewDecision APPROVED, unresolved threads 0.

CI Gate:
- Required CI checks verified: 5/5 PASS
- statusCheckRollup PASSING.
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"

  cat > "$bin_dir/gh" <<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail

if [[ "${1:-}" == "pr" && "${2:-}" == "view" ]]; then
  printf '287\tfalse\tAPPROVED\thttps://github.example.com/VilnaCRM-Org/user-service/pull/287\t%s\n' "$(git rev-parse HEAD)"
  exit 0
fi

if [[ "${1:-}" == "pr" && "${2:-}" == "checks" ]]; then
  printf '3\t\n'
  exit 0
fi

if [[ "${1:-}" == "api" && "${2:-}" == "graphql" ]]; then
  if printf '%s\n' "$*" | grep -Fq -- "-f cursor=PAGE2"; then
    printf '1\tfalse\t\n'
  else
    printf '0\ttrue\tPAGE2\n'
  fi
  exit 0
fi

echo "unexpected gh invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/gh"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    BMAD_REVIEW_SPEC_PATH="$spec_dir" \
    BMAD_REVIEW_BASE=HEAD \
    BMAD_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    BMAD_REVIEW_VERIFY_CMD=true \
    BMAD_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/bmad-fr-nfr-review-gate.sh 2>&1"

  assert_failure
  assert_output --partial "Warning: GitHub PR has unresolved review threads: 1"
  assert_output --partial "GitHub corroboration failed before AI review."
  refute_output --partial "Reached AI_REVIEW_MAX_ITER=1 without PASS."
}

@test "bmad-fr-nfr-review-gate requires STATUS on the first line" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/example"

  mkdir -p "$bin_dir" "$spec_dir"
  printf "# PRD\n\nFR-01: Works.\n" > "${spec_dir}/prd.md"

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
  cat > "$output_file" <<'STATUS'
Here is the review.
STATUS: PASS
0 issues.
FR_NFR_SCORECARD: PASS
NFR_CATALOG_SCORECARD: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: APPROVED
CI_CHECK_ROLLUP: PASSING

Requirement Scorecard:
- FR-01 evidence: 5/5 PASS

NFR Catalog Scorecard:
- Performance: 5/5 PASS
- Usability: 5/5 PASS
- Maintainability: 5/5 PASS
- Availability: 5/5 PASS
- Interoperability: 5/5 PASS
- Security: 5/5 PASS
- Manageability: 5/5 PASS
- Automatability: 5/5 PASS
- Dependability: 5/5 PASS
- Maintainability evidence: 5/5 PASS

Manual Test Evidence:
- Manual evidence reviewed: 5/5 PASS
- Not applicable for shell validation fixture.

QA Verification:
- QA verification completed: 5/5 PASS
- make ci: PASS

GitHub Completion Gate:
- GitHub completion verified: 5/5 PASS
- reviewDecision APPROVED, unresolved threads 0.

CI Gate:
- Required CI checks verified: 5/5 PASS
- statusCheckRollup PASSING.
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"
  write_successful_bmad_gh_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    BMAD_REVIEW_SPEC_PATH="$spec_dir" \
    BMAD_REVIEW_BASE=HEAD \
    BMAD_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    BMAD_REVIEW_VERIFY_CMD=true \
    BMAD_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/bmad-fr-nfr-review-gate.sh 2>&1"

  assert_failure
  assert_output --partial "Warning: Agent codex did not produce STATUS line; treating as FAIL."
  assert_output --partial "Reached AI_REVIEW_MAX_ITER=1 without PASS."
}

@test "bmad-fr-nfr-review-gate fails when verification fails after PASS" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/example"

  mkdir -p "$bin_dir" "$spec_dir"
  printf "# PRD\n\nFR-01: Works.\n" > "${spec_dir}/prd.md"

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
  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
FR_NFR_SCORECARD: PASS
NFR_CATALOG_SCORECARD: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: APPROVED
CI_CHECK_ROLLUP: PASSING

Requirement Scorecard:
- FR-01 evidence: 5/5 PASS

NFR Catalog Scorecard:
- Performance: 5/5 PASS
- Usability: 5/5 PASS
- Maintainability: 5/5 PASS
- Availability: 5/5 PASS
- Interoperability: 5/5 PASS
- Security: 5/5 PASS
- Manageability: 5/5 PASS
- Automatability: 5/5 PASS
- Dependability: 5/5 PASS
- Maintainability evidence: 5/5 PASS

Manual Test Evidence:
- Manual evidence reviewed: 5/5 PASS
- Not applicable for shell validation fixture.

QA Verification:
- QA verification completed: 5/5 PASS
- make ci: PASS

GitHub Completion Gate:
- GitHub completion verified: 5/5 PASS
- reviewDecision APPROVED, unresolved threads 0.

CI Gate:
- Required CI checks verified: 5/5 PASS
- statusCheckRollup PASSING.
STATUS
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"
  write_successful_bmad_gh_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    BMAD_REVIEW_SPEC_PATH="$spec_dir" \
    BMAD_REVIEW_BASE=HEAD \
    BMAD_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    BMAD_REVIEW_VERIFY_CMD=false \
    BMAD_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/bmad-fr-nfr-review-gate.sh 2>&1"

  assert_failure
  assert_output --partial "Verification failed after AI review PASS"
}
