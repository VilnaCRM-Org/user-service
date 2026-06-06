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

  cat > "$output_file" <<STATUS
STATUS: PASS
0 issues.
Reviewed SHA and PR URL: local test fixture.
FR/NFR counts: FRs: 1; NFRs: 1; generated cases: 8; covered cases: 8; uncovered cases: 0; findings fixed: 0.
Findings table:
| Severity | File/Line | Requirement/NFR | Evidence | Fix | Verification |
${AI_REVIEW_STUB_FINDING_ROW:-| None | N/A | N/A | No findings | N/A | N/A |}
Test evidence table:
| Suite/Command | Evidence | Result |
| true | Stub verification | PASS |
Current-head GitHub check summary:
| Check | State | Evidence |
| local | PASS | Stub current-head check |
Flaky-test risk review: no flaky risk found.
System quality attribute scorecard:
| Attribute | Score | Evidence | Improvement/Fix | Blocker |
${AI_REVIEW_STUB_CORRECTNESS_ROW:-| correctness | 5 | Stub evidence. | None. | No |}
  ${AI_REVIEW_STUB_AUDITABILITY_ROW:-| auditability | 5 | Stub evidence. | None. | No |}
  ${AI_REVIEW_STUB_CONFIDENTIALITY_ROW:-| confidentiality | 5 | Stub evidence. | None. | No |}
| integrity | 5 | Stub evidence. | None. | No |
| availability | 5 | Stub evidence. | None. | No |
| reliability | 5 | Stub evidence. | None. | No |
${AI_REVIEW_STUB_MAINTAINABILITY_ROW:-| maintainability | 5 | Stub evidence. | None. | No |}
| observability | 5 | Stub evidence. | None. | No |
| operability | 5 | Stub evidence. | None. | No |
| testability | 5 | Stub evidence. | None. | No |
| traceability | 5 | Stub evidence. | None. | No |
| vulnerability | 5 | Stub evidence. | None. | No |
| accountability | 5 | Stub evidence. | None. | No |
| accuracy | 5 | Stub evidence. | None. | No |
| compatibility | 5 | Stub evidence. | None. | No |
| deployability | 5 | Stub evidence. | None. | No |
| efficiency | 5 | Stub evidence. | None. | No |
| interoperability | 5 | Stub evidence. | None. | No |
| resilience | 5 | Stub evidence. | None. | No |
| responsiveness | 5 | Stub evidence. | None. | No |
| performance | 5 | Stub evidence. | None. | No |
${AI_REVIEW_STUB_SAFETY_ROW:-| safety | 5 | Stub evidence. | None. | No |}
| scalability | 5 | Stub evidence. | None. | No |
| securability | 5 | Stub evidence. | None. | No |
| standards compliance | 5 | Stub evidence. | None. | No |
| survivability | 5 | Stub evidence. | None. | No |
${AI_REVIEW_STUB_REMAINING_ROW-| all remaining listed attributes | N/A | No changed behavior for these attributes. | None. | No |}
Graph/code-search whole-codebase impact evidence: current graph and code-search context reviewed.
FR_NFR_SCORECARD: PASS
TEST_CASE_MATRIX: PASS
AUTO_TEST_COVERAGE: PASS
CI_COVERAGE: ${AI_REVIEW_STUB_CI_COVERAGE_MARKER:-PASS}
FLAKY_TEST_RISK: PASS
SYSTEM_QUALITY_ATTRIBUTES_SCORECARD: PASS
WHOLE_CODEBASE_IMPACT: PASS
GRAPH_IMPACT_CONTEXT: PASS
GITHUB_COMPLETION_GATE: ${AI_REVIEW_STUB_GITHUB_COMPLETION_GATE_MARKER:-PASS}
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

if [[ "${1:-}" == "pr" && "${2:-}" == "checks" ]]; then
  printf '%b\n' "${GH_CHECK_ROWS:-PHPUnit\tUnit and Integration testing\tSUCCESS\tpass}"
  exit "${GH_CHECK_EXIT:-0}"
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
    AI_REVIEW_VERIFY_CMD=true \
    bash -c "./scripts/ai-review-loop.sh 2>&1"
  assert_success
  assert_output --partial "AI review PASS."
}

@test "ai-review-loop runs verification when first review passes" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local verify_log="${BATS_TEST_TMPDIR}/verify-ran"
  mkdir -p "$bin_dir"

  create_pass_codex_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_GITHUB_STATUS=false \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    AI_REVIEW_VERIFY_CMD="printf verified > ${verify_log}" \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_success
  run cat "$verify_log"
  assert_success
  assert_output "verified"
}

@test "ai-review-loop rejects PASS report with actionable findings" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  mkdir -p "$bin_dir"

  create_pass_codex_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_GITHUB_STATUS=false \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    AI_REVIEW_STUB_FINDING_ROW="| High | scripts/ai-review-loop.sh:1 | FR-1 | Local actionable defect remains. | Fix local code. | Re-run tests. |" \
    AI_REVIEW_VERIFY_CMD=true \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "actionable local findings"
  refute_output --partial "AI review PASS."
}

@test "ai-review-loop rejects PASS report with indented actionable findings table row" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  mkdir -p "$bin_dir"

  create_pass_codex_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_GITHUB_STATUS=false \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    AI_REVIEW_STUB_FINDING_ROW="  | High | scripts/ai-review-loop.sh:1 | FR-1 | Local actionable defect remains. | Fix local code. | Re-run tests. |" \
    AI_REVIEW_VERIFY_CMD=true \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "actionable local findings"
  refute_output --partial "AI review PASS."
}

@test "ai-review-loop rejects actionable findings without file location" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  mkdir -p "$bin_dir"

  create_pass_codex_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_GITHUB_STATUS=false \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    AI_REVIEW_STUB_FINDING_ROW="| High | N/A | FR-1 | Local actionable defect remains. | Fix local code. | Re-run tests. |" \
    AI_REVIEW_VERIFY_CMD=true \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "actionable finding is missing File/Line evidence"
  refute_output --partial "AI review PASS."
}

@test "ai-review-loop rejects incomplete quality scorecard rows" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  mkdir -p "$bin_dir"

  create_pass_codex_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_GITHUB_STATUS=false \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    AI_REVIEW_STUB_CORRECTNESS_ROW="| correctness | 5 | | None. | No |" \
    AI_REVIEW_VERIFY_CMD=true \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "Strict review report quality attribute row has an empty required field: correctness"
  refute_output --partial "AI review PASS."
}

@test "ai-review-loop rejects PASS report without grouped remaining quality attributes row" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  mkdir -p "$bin_dir"

  create_pass_codex_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_GITHUB_STATUS=false \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    AI_REVIEW_STUB_REMAINING_ROW="" \
    AI_REVIEW_VERIFY_CMD=true \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "missing grouped N/A row for remaining quality attributes"
  refute_output --partial "AI review PASS."
}

@test "ai-review-loop rejects PASS report with low quality attribute score" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  mkdir -p "$bin_dir"

  create_pass_codex_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_GITHUB_STATUS=false \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    AI_REVIEW_STUB_CORRECTNESS_ROW="| correctness | 3 | Partial evidence. | Add missing coverage. | No |" \
    AI_REVIEW_VERIFY_CMD=true \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "quality attribute score below pass threshold: correctness (3)"
  refute_output --partial "AI review PASS."
}

@test "ai-review-loop rejects PASS report with weakened security attribute score" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  mkdir -p "$bin_dir"

  create_pass_codex_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_GITHUB_STATUS=false \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    AI_REVIEW_STUB_CONFIDENTIALITY_ROW="| confidentiality | 4 | Acceptable but improvable. | Tighten evidence. | No |" \
    AI_REVIEW_VERIFY_CMD=true \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "security quality attribute below strict threshold: confidentiality (4)"
  refute_output --partial "AI review PASS."
}

@test "ai-review-loop rejects lowercase security N/A without tracked exception" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  mkdir -p "$bin_dir"

  create_pass_codex_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_GITHUB_STATUS=false \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    AI_REVIEW_STUB_SAFETY_ROW="| safety | n/a | No direct safety change. | None. | No |" \
    AI_REVIEW_VERIFY_CMD=true \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "security quality attribute cannot be N/A in a passing report: safety"
  refute_output --partial "AI review PASS."
}

@test "ai-review-loop allows tracked out-of-scope security exception" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  mkdir -p "$bin_dir"

  create_pass_codex_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_GITHUB_STATUS=false \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    AI_REVIEW_STUB_CONFIDENTIALITY_ROW="| confidentiality | 4 | Existing upstream control is outside PR scope. | Outside PR scope; tracked in SEC-123 with owner Security and risk accepted. | Tracked risk owner Security. |" \
    AI_REVIEW_VERIFY_CMD=true \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_success
  assert_output --partial "AI review PASS."
}

@test "ai-review-loop rejects score four quality row without improvement statement" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  mkdir -p "$bin_dir"

  create_pass_codex_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_GITHUB_STATUS=false \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    AI_REVIEW_STUB_MAINTAINABILITY_ROW="| maintainability | 4 | Acceptable evidence. | None. | No |" \
    AI_REVIEW_VERIFY_CMD=true \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "quality attribute score 4 lacks a concrete improvement statement: maintainability"
  refute_output --partial "AI review PASS."
}

@test "ai-review-loop rejects STATUS PASS with remote pending markers" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  mkdir -p "$bin_dir"

  create_pass_codex_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_GITHUB_STATUS=false \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    AI_REVIEW_STUB_CI_COVERAGE_MARKER=PENDING_REMOTE \
    AI_REVIEW_STUB_GITHUB_COMPLETION_GATE_MARKER=PENDING_REMOTE_CI \
    AI_REVIEW_VERIFY_CMD=true \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "STATUS: PASS with remote-CI pending markers"
  assert_output --partial "violates strict BMAD protocol"
  refute_output --partial "AI review pending remote CI"
  refute_output --partial "fix loop running"
}

@test "ai-review-loop fails when first-pass verification fails" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  mkdir -p "$bin_dir"

  create_pass_codex_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_GITHUB_STATUS=false \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    AI_REVIEW_VERIFY_CMD="exit 42" \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "last verification failed"
}

@test "ai-review-loop claude agent receives strict review prompt" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  mkdir -p "$bin_dir"

  cat > "$bin_dir/claude" <<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail

# Capture all args to verify the strict prompt is passed via -p.
joined=" $* "
if [[ "$joined" != *" -p "* || "$joined" != *"strict BMAD FR/NFR code reviewer"* || "$joined" != *"0-5"* || "$joined" != *"GRAPH_IMPACT_CONTEXT: PASS|MISSING|FAIL"* || "$joined" != *"manual evidence"* ]]; then
  echo "ERROR: strict review prompt not found in args: $*" >&2
  exit 2
fi

cat <<'STATUS'
STATUS: PASS
0 issues.
Reviewed SHA and PR URL: local test fixture.
FR/NFR counts: FRs: 1; NFRs: 1; generated cases: 8; covered cases: 8; uncovered cases: 0; findings fixed: 0.
Findings table:
| Severity | File/Line | Requirement/NFR | Evidence | Fix | Verification |
| None | N/A | N/A | No findings | N/A | N/A |
Test evidence table:
| Suite/Command | Evidence | Result |
| true | Stub verification | PASS |
Current-head GitHub check summary:
| Check | State | Evidence |
| local | PASS | Stub current-head check |
Flaky-test risk review: no flaky risk found.
System quality attribute scorecard:
| Attribute | Score | Evidence | Improvement/Fix | Blocker |
| correctness | 5 | Stub evidence. | None. | No |
| auditability | 5 | Stub evidence. | None. | No |
| confidentiality | 5 | Stub evidence. | None. | No |
| integrity | 5 | Stub evidence. | None. | No |
| availability | 5 | Stub evidence. | None. | No |
| reliability | 5 | Stub evidence. | None. | No |
| maintainability | 5 | Stub evidence. | None. | No |
| observability | 5 | Stub evidence. | None. | No |
| operability | 5 | Stub evidence. | None. | No |
| testability | 5 | Stub evidence. | None. | No |
| traceability | 5 | Stub evidence. | None. | No |
| vulnerability | 5 | Stub evidence. | None. | No |
| accountability | 5 | Stub evidence. | None. | No |
| accuracy | 5 | Stub evidence. | None. | No |
| compatibility | 5 | Stub evidence. | None. | No |
| deployability | 5 | Stub evidence. | None. | No |
| efficiency | 5 | Stub evidence. | None. | No |
| interoperability | 5 | Stub evidence. | None. | No |
| resilience | 5 | Stub evidence. | None. | No |
| responsiveness | 5 | Stub evidence. | None. | No |
| performance | 5 | Stub evidence. | None. | No |
| safety | 5 | Stub evidence. | None. | No |
| scalability | 5 | Stub evidence. | None. | No |
| securability | 5 | Stub evidence. | None. | No |
| standards compliance | 5 | Stub evidence. | None. | No |
| survivability | 5 | Stub evidence. | None. | No |
| all remaining listed attributes | N/A | No changed behavior for these attributes. | None. | No |
Graph/code-search whole-codebase impact evidence: reviewed.
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
SCRIPT
  chmod +x "$bin_dir/claude"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_AGENT=claude \
    AI_REVIEW_CLAUDE_CMD=claude \
    AI_REVIEW_GITHUB_STATUS=false \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    AI_REVIEW_VERIFY_CMD=true \
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
cat <<'STATUS'
STATUS: PASS
0 issues.
Reviewed SHA and PR URL: local test fixture.
FR/NFR counts: FRs: 1; NFRs: 1; generated cases: 8; covered cases: 8; uncovered cases: 0; findings fixed: 0.
Findings table:
| Severity | File/Line | Requirement/NFR | Evidence | Fix | Verification |
| None | N/A | N/A | No findings | N/A | N/A |
Test evidence table:
| Suite/Command | Evidence | Result |
| true | Stub verification | PASS |
Current-head GitHub check summary:
| Check | State | Evidence |
| local | PASS | Stub current-head check |
Flaky-test risk review: no flaky risk found.
System quality attribute scorecard:
| Attribute | Score | Evidence | Improvement/Fix | Blocker |
| correctness | 5 | Stub evidence. | None. | No |
| auditability | 5 | Stub evidence. | None. | No |
| confidentiality | 5 | Stub evidence. | None. | No |
| integrity | 5 | Stub evidence. | None. | No |
| availability | 5 | Stub evidence. | None. | No |
| reliability | 5 | Stub evidence. | None. | No |
| maintainability | 5 | Stub evidence. | None. | No |
| observability | 5 | Stub evidence. | None. | No |
| operability | 5 | Stub evidence. | None. | No |
| testability | 5 | Stub evidence. | None. | No |
| traceability | 5 | Stub evidence. | None. | No |
| vulnerability | 5 | Stub evidence. | None. | No |
| accountability | 5 | Stub evidence. | None. | No |
| accuracy | 5 | Stub evidence. | None. | No |
| compatibility | 5 | Stub evidence. | None. | No |
| deployability | 5 | Stub evidence. | None. | No |
| efficiency | 5 | Stub evidence. | None. | No |
| interoperability | 5 | Stub evidence. | None. | No |
| resilience | 5 | Stub evidence. | None. | No |
| responsiveness | 5 | Stub evidence. | None. | No |
| performance | 5 | Stub evidence. | None. | No |
| safety | 5 | Stub evidence. | None. | No |
| scalability | 5 | Stub evidence. | None. | No |
| securability | 5 | Stub evidence. | None. | No |
| standards compliance | 5 | Stub evidence. | None. | No |
| survivability | 5 | Stub evidence. | None. | No |
| all remaining listed attributes | N/A | No changed behavior for these attributes. | None. | No |
Graph/code-search whole-codebase impact evidence: reviewed.
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
SCRIPT
  chmod +x "$bin_dir/claude"

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_AGENT=claude \
    AI_REVIEW_CLAUDE_CMD=claude \
    AI_REVIEW_GITHUB_STATUS=false \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    AI_REVIEW_VERIFY_CMD=true \
    bash -c "./scripts/ai-review-loop.sh 2>&1"
  assert_success
  assert_output --partial "AI review PASS."

  # Verify stderr went to .log file, not to the review output
  local review_file
  review_file="${BATS_TEST_TMPDIR}/ai-review/review-latest-claude.md"
  run cat "$review_file"
  refute_output --partial "some warning"
}

@test "ai-review-loop rejects marker-only PASS report" {
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

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_GITHUB_STATUS=false \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    AI_REVIEW_VERIFY_CMD=true \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "required review sections are missing"
}

@test "ai-review-loop rejects keyword-only PASS report" {
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
Reviewed SHA, FR/NFR, positive, negative, edge, automated CI, flaky, quality attribute, graph, code-search, whole-codebase.
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

  run env \
    PATH="$bin_dir:$PATH" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_GITHUB_STATUS=false \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    AI_REVIEW_VERIFY_CMD=true \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "required review sections are missing"
}

@test "ai-review-loop rejects skeletal remote pending report" {
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
STATUS: FAIL
Issues:
1. Remote CI is pending.
FR_NFR_SCORECARD: PASS
TEST_CASE_MATRIX: PASS
AUTO_TEST_COVERAGE: PASS
CI_COVERAGE: PENDING_REMOTE
FLAKY_TEST_RISK: PASS
SYSTEM_QUALITY_ATTRIBUTES_SCORECARD: PASS
WHOLE_CODEBASE_IMPACT: PASS
GRAPH_IMPACT_CONTEXT: PASS
GITHUB_COMPLETION_GATE: PENDING_REMOTE_CI
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
    AI_REVIEW_GITHUB_STATUS=false \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    AI_REVIEW_VERIFY_CMD=true \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "required review sections are missing"
  refute_output --partial "AI review pending remote CI"
}

@test "ai-review-loop rejects remote pending report with actionable findings" {
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
STATUS: FAIL
Reviewed SHA and PR URL: local test fixture.
FR/NFR counts: FRs: 1; NFRs: 1; generated cases: 8; covered cases: 8; uncovered cases: 0; findings fixed: 0.
Findings table:
| Severity | File/Line | Requirement/NFR | Evidence | Fix | Verification |
| High | scripts/ai-review-loop.sh:1 | FR-1 | Local actionable defect remains. | Fix local code. | Re-run tests. |
Test evidence table:
| Suite/Command | Evidence | Result |
| true | Stub verification | PASS |
Current-head GitHub check summary:
| Check | State | Evidence |
| remote | N/A | PENDING_REMOTE_CI | Remote CI still running. | Wait. | Remote checks complete. |
Flaky-test risk review: no flaky risk found.
System quality attribute scorecard:
| Attribute | Score | Evidence | Improvement/Fix | Blocker |
| correctness | 5 | Stub evidence. | None. | No |
| auditability | 5 | Stub evidence. | None. | No |
| confidentiality | 5 | Stub evidence. | None. | No |
| integrity | 5 | Stub evidence. | None. | No |
| availability | 5 | Stub evidence. | None. | No |
| reliability | 5 | Stub evidence. | None. | No |
| maintainability | 5 | Stub evidence. | None. | No |
| observability | 5 | Stub evidence. | None. | No |
| operability | 5 | Stub evidence. | None. | No |
| testability | 5 | Stub evidence. | None. | No |
| traceability | 5 | Stub evidence. | None. | No |
| vulnerability | 5 | Stub evidence. | None. | No |
| accountability | 5 | Stub evidence. | None. | No |
| accuracy | 5 | Stub evidence. | None. | No |
| compatibility | 5 | Stub evidence. | None. | No |
| deployability | 5 | Stub evidence. | None. | No |
| efficiency | 5 | Stub evidence. | None. | No |
| interoperability | 5 | Stub evidence. | None. | No |
| resilience | 5 | Stub evidence. | None. | No |
| responsiveness | 5 | Stub evidence. | None. | No |
| performance | 5 | Stub evidence. | None. | No |
| safety | 5 | Stub evidence. | None. | No |
| scalability | 5 | Stub evidence. | None. | No |
| securability | 5 | Stub evidence. | None. | No |
| standards compliance | 5 | Stub evidence. | None. | No |
| survivability | 5 | Stub evidence. | None. | No |
| all remaining listed attributes | N/A | No changed behavior for these attributes. | None. | No |
Graph/code-search whole-codebase impact evidence: reviewed.
FR_NFR_SCORECARD: PASS
TEST_CASE_MATRIX: PASS
AUTO_TEST_COVERAGE: PASS
CI_COVERAGE: PENDING_REMOTE
FLAKY_TEST_RISK: PASS
SYSTEM_QUALITY_ATTRIBUTES_SCORECARD: PASS
WHOLE_CODEBASE_IMPACT: PASS
GRAPH_IMPACT_CONTEXT: PASS
GITHUB_COMPLETION_GATE: PENDING_REMOTE_CI
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
    AI_REVIEW_GITHUB_STATUS=false \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_MAX_ITER=1 \
    AI_REVIEW_VERIFY_CMD=true \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "actionable local findings"
  refute_output --partial "AI review pending remote CI"
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
    AI_REVIEW_VERIFY_CMD=true \
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
    AI_REVIEW_VERIFY_CMD=true \
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
    AI_REVIEW_VERIFY_CMD=true \
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

@test "ai-review-loop refuses success when current-head GitHub checks fail" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local head_sha
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
    AI_REVIEW_VERIFY_CMD=true \
    GH_HEAD_COUNTER_FILE="${BATS_TEST_TMPDIR}/head-counter" \
    GH_HEAD_SHA_SEQUENCE="${head_sha},${head_sha}" \
    GH_CHECK_ROWS=$'PHPUnit\tUnit and Integration testing\tFAIL\tfail' \
    GH_STATUS_LOG="$status_log" \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "current-head GitHub checks are not passing"
  run grep -q "state=success" "$status_log"
  assert_failure
  run grep -q "state=failure" "$status_log"
  assert_success
}

@test "ai-review-loop keeps BMAD status pending when current-head GitHub checks are pending" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local head_sha
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
    AI_REVIEW_REQUIRED_CHECKS=PHPUnit \
    AI_REVIEW_VERIFY_CMD=true \
    GH_HEAD_COUNTER_FILE="${BATS_TEST_TMPDIR}/head-counter" \
    GH_HEAD_SHA_SEQUENCE="${head_sha},${head_sha}" \
    GH_CHECK_ROWS=$'PHPUnit\tUnit and Integration testing\tPENDING\tpending' \
    GH_STATUS_LOG="$status_log" \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "current-head GitHub checks are not passing"
  run grep -q "state=success" "$status_log"
  assert_failure
  run grep -q "state=pending" "$status_log"
  assert_success
}

@test "ai-review-loop ignores its own BMAD status when validating current-head checks" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local head_sha
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
    AI_REVIEW_REQUIRED_CHECKS=PHPUnit,Psalm \
    AI_REVIEW_VERIFY_CMD=true \
    GH_HEAD_COUNTER_FILE="${BATS_TEST_TMPDIR}/head-counter" \
    GH_HEAD_SHA_SEQUENCE="${head_sha},${head_sha}" \
    GH_CHECK_ROWS=$'BMAD FR/NFR Review Gate\t-\tPENDING\tpending\nPHPUnit\tUnit and Integration testing\tSUCCESS\tpass\nPsalm\tcode quality\tSUCCESS\tpass' \
    GH_STATUS_LOG="$status_log" \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_success
  assert_output --partial "AI review PASS."
  run grep -q "state=success" "$status_log"
  assert_success
}

@test "ai-review-loop refuses success when a default expected GitHub check is missing" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local head_sha
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
    AI_REVIEW_VERIFY_CMD=true \
    GH_HEAD_COUNTER_FILE="${BATS_TEST_TMPDIR}/head-counter" \
    GH_HEAD_SHA_SEQUENCE="${head_sha},${head_sha}" \
    GH_CHECK_ROWS=$'PHPUnit\tUnit and Integration testing\tSUCCESS\tpass' \
    GH_STATUS_LOG="$status_log" \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "Current-head GitHub check is missing: Behat."
  run grep -q "state=success" "$status_log"
  assert_failure
  run grep -q "state=failure" "$status_log"
  assert_success
}

@test "ai-review-loop matches workflow-qualified required GitHub checks" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local head_sha
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
    AI_REVIEW_REQUIRED_CHECKS="OPENAPI-DIFF@GraphQL spec backward comparability,openapi-diff@openapi-diff" \
    AI_REVIEW_VERIFY_CMD=true \
    GH_HEAD_COUNTER_FILE="${BATS_TEST_TMPDIR}/head-counter" \
    GH_HEAD_SHA_SEQUENCE="${head_sha},${head_sha}" \
    GH_CHECK_ROWS=$'Openapi-diff\tGraphQL spec backward comparability\tSUCCESS\tpass\nopenapi-diff\topenapi-diff\tSUCCESS\tpass' \
    GH_STATUS_LOG="$status_log" \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_success
  assert_output --partial "AI review PASS."
  run grep -q "state=success" "$status_log"
  assert_success
}

@test "ai-review-loop keeps workflow-qualified GitHub checks distinct" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local head_sha
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
    AI_REVIEW_REQUIRED_CHECKS="Openapi-diff@GraphQL spec backward comparability,openapi-diff@openapi-diff" \
    AI_REVIEW_VERIFY_CMD=true \
    GH_HEAD_COUNTER_FILE="${BATS_TEST_TMPDIR}/head-counter" \
    GH_HEAD_SHA_SEQUENCE="${head_sha},${head_sha}" \
    GH_CHECK_ROWS=$'openapi-diff\topenapi-diff\tSUCCESS\tpass' \
    GH_STATUS_LOG="$status_log" \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "Current-head GitHub check is missing: Openapi-diff@GraphQL spec backward comparability."
  run grep -q "state=failure" "$status_log"
  assert_success
}

@test "ai-review-loop passes when configured current-head GitHub checks pass" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local head_sha
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
    AI_REVIEW_REQUIRED_CHECKS=PHPUnit,Psalm \
    AI_REVIEW_VERIFY_CMD=true \
    GH_HEAD_COUNTER_FILE="${BATS_TEST_TMPDIR}/head-counter" \
    GH_HEAD_SHA_SEQUENCE="${head_sha},${head_sha}" \
    GH_CHECK_ROWS=$'PHPUnit\tUnit and Integration testing\tSUCCESS\tpass\nPsalm\tcode quality\tSUCCESS\tpass' \
    GH_STATUS_LOG="$status_log" \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_success
  assert_output --partial "AI review PASS."
  run grep -q "state=success" "$status_log"
  assert_success
}
