#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

write_strict_bmad_pass_report_fixture() {
  local output_file="$1"

  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
FR_NFR_SCORECARD: PASS
NFR_CATALOG_SCORECARD: PASS
EXPANDED_QUALITY_SCORECARD: PASS
SYSTEM_QUALITY_ATTRIBUTES_SCORECARD: PASS
WHOLE_CODEBASE_IMPACT: PASS
GRAPH_IMPACT_CONTEXT: PASS
TEST_CASE_MATRIX: PASS
AUTO_TEST_COVERAGE: PASS
FLAKY_TEST_RISK: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
EXPANDED_QUALITY_MIN_SCORE: 5/5
SYSTEM_QUALITY_ATTRIBUTES_MIN_SCORE: 5/5
IMPACT_ANALYSIS_MIN_SCORE: 5/5
TEST_CASE_COVERAGE_MIN_SCORE: 5/5
AUTO_TEST_COVERAGE_MIN_SCORE: 5/5
FLAKY_TEST_RISK_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: PASSING
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

Expanded Quality Scorecard:
- Functional Suitability: 5/5 PASS
- Performance Resource Sustainability: 5/5 PASS
- Compatibility Coexistence: 5/5 PASS
- Interaction Capability Accessibility: 5/5 PASS
- Reliability Resilience: 5/5 PASS
- Security Privacy Accountability: 5/5 PASS
- Maintainability Testability: 5/5 PASS
- Flexibility Portability: 5/5 PASS
- Safety Harm Prevention: 5/5 PASS
- Data Quality Integrity: 5/5 PASS
- Operational Excellence Releaseability: 5/5 PASS
- Observability Diagnosability: 5/5 PASS
- Supply-Chain Integrity: 5/5 PASS
- Compliance Governance: 5/5 PASS
- Sustainability Resource Impact: 5/5 PASS
- AI Automation Governance: 5/5 PASS

System Quality Attributes Scorecard:
- Accessibility: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Accountability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Accuracy: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Adaptability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Administrability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Affordability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Agility: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Analyzability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Auditability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Autonomy: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Availability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Compatibility: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Composability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Confidentiality: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Configurability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Convenience: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Correctness: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Credibility: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Customizability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Debuggability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Degradability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Determinability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Demonstrability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Dependability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Deployability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Discoverability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Distributability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Durability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Effectiveness: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Efficiency: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Elasticity: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Evolvability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Extensibility: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Failure Transparency: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Familiarity: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Fault-Tolerance: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Fidelity: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Flexibility: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Inspectability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Installability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Integrity: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Interactivity: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Interchangeability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Interoperability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Intuitiveness: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Learnability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Localizability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Maintainability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Manageability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Mobility: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Modifiability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Modularity: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Observability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Operability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Orthogonality: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Portability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Precision: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Predictability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Process Capabilities: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Producibility: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Provability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Recoverability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Redundancy: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Relevance: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Reliability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Repairability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Repeatability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Reproducibility: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Resilience: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Responsiveness: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Reusability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Robustness: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Safety: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Scalability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Seamlessness: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Self-Sustainability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Serviceability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Securability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Simplicity: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Stability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Standards Compliance: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Survivability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Sustainability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Tailorability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Testability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Timeliness: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Traceability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Transparency: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Ubiquity: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Understandability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Upgradability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Usability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS
- Vulnerability: checked PR scope, graph impact, tests, and CI evidence; source Wikipedia/BMAD; Improvement: none. Score: 5/5 PASS

Whole-Codebase Impact Analysis:
- Runtime paths: wrapper-generated local relationship graph edge evidence inspected in codebase-graph-impact-context.md: 5/5 PASS
- Architecture and layer boundaries: 5/5 PASS
- Domain model: 5/5 PASS
- Persistence and database: 5/5 PASS
- Public API and schema: 5/5 PASS
- Async events and queues: 5/5 PASS
- Configuration and environment: 5/5 PASS
- Dependencies and lockfiles: 5/5 PASS
- CI and workflows: 5/5 PASS
- Tests and fixtures: 5/5 PASS
- Documentation: 5/5 PASS
- Operations and observability: 5/5 PASS
- Security and privacy: 5/5 PASS
- Backward compatibility: 5/5 PASS

Graph Impact Context:
- Provider/artifact path: wrapper-generated local relationship graph at codebase-graph-impact-context.md; changed-file relationship edges inspected and source files validated: 5/5 PASS

Test Case Matrix:
- Every FR/NFR/quality requirement has positive success, negative invalid/error, and edge boundary/race/timeout/concurrency cases mapped to automated PHPUnit/Behat/CI evidence and manual evidence where required; missing gaps: none. Score: 5/5 PASS

Automated Test And CI Coverage:
- Every repeatable FR/NFR/quality acceptance case is covered by automated unit, integration, e2e, Behat, PHPUnit, Schemathesis, K6, Infection, and GitHub workflow check rollup evidence; missing required fix gaps: none. Score: 5/5 PASS

Flaky Test Risk:
- Changed and impacted existing tests inspected for flaky nondeterministic risk sources: sleeps, clock, random, parallel order, external dependency, timeout, retry, race, and fixture environment issues; mitigations evidenced by deterministic CI command/check rollup. Score: 5/5 PASS

Manual Test Evidence:
- Manual evidence reviewed: 5/5 PASS
- Not applicable for shell validation fixture.

QA Verification:
- QA verification completed: 5/5 PASS
- make ci: PASS

GitHub Completion Gate:
- GitHub completion verified: 5/5 PASS
- reviewDecision REVIEW_REQUIRED, unresolved threads 0.

CI Gate:
- Required CI checks verified: 5/5 PASS
- statusCheckRollup PASSING.

Required Fixes:
- None.
STATUS
}

setup() {
  export BMAD_PASS_REPORT="${BATS_TEST_TMPDIR}/bmad-pass-report.md"
  write_strict_bmad_pass_report_fixture "$BMAD_PASS_REPORT"

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

if [[ "${1:-}" == "pr" && "${2:-}" == "comment" ]]; then
  printf 'pr comment %s\n' "$*" >> "${GH_PR_COMMENT_LOG:-/dev/null}"
  exit 0
fi

if [[ "${1:-}" == "api" && "${2:-}" == repos/*/statuses/* ]]; then
  printf 'status %s\n' "$*" >> "${GH_STATUS_LOG:-/dev/null}"
  exit 0
fi

if [[ "${1:-}" == "pr" && "${2:-}" == "view" ]]; then
  if [[ "$*" != *"headRefOid"* || "$*" == *"statusCheckRollup"* ]]; then
    echo "PR state query must include headRefOid and must not rely on full statusCheckRollup" >&2
    exit 2
  fi
  printf '287\tfalse\tREVIEW_REQUIRED\thttps://github.example.com/VilnaCRM-Org/user-service/pull/287\t%s\n' "$(git rev-parse HEAD)"
  exit 0
fi

if [[ "${1:-}" == "pr" && "${2:-}" == "checks" ]]; then
  printf 'pr checks %s\n' "$*" >> "${GH_PR_CHECKS_ARGS_LOG:-/dev/null}"
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

if [[ "${1:-}" == "pr" && "${2:-}" == "comment" ]]; then
  printf 'pr comment %s\n' "$*" >> "${GH_PR_COMMENT_LOG:-/dev/null}"
  exit 0
fi

if [[ "${1:-}" == "api" && "${2:-}" == repos/*/statuses/* ]]; then
  printf 'status %s\n' "$*" >> "${GH_STATUS_LOG:-/dev/null}"
  exit 0
fi

if [[ "${1:-}" == "pr" && "${2:-}" == "view" ]]; then
  if [[ "$*" != *"headRefOid"* || "$*" == *"statusCheckRollup"* ]]; then
    echo "PR state query must include headRefOid and must not rely on full statusCheckRollup" >&2
    exit 2
  fi
  printf '287\tfalse\tREVIEW_REQUIRED\thttps://github.example.com/VilnaCRM-Org/user-service/pull/287\t%s\n' "$(git rev-parse HEAD)"
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

if [[ "${1:-}" == "pr" && "${2:-}" == "comment" ]]; then
  printf 'pr comment %s\n' "$*" >> "${GH_PR_COMMENT_LOG:-/dev/null}"
  exit 0
fi

if [[ "${1:-}" == "api" && "${2:-}" == repos/*/statuses/* ]]; then
  printf 'status %s\n' "$*" >> "${GH_STATUS_LOG:-/dev/null}"
  exit 0
fi

if [[ "${1:-}" == "pr" && "${2:-}" == "view" ]]; then
  if [[ "$*" != *"headRefOid"* || "$*" == *"statusCheckRollup"* ]]; then
    echo "PR state query must include headRefOid and must not rely on full statusCheckRollup" >&2
    exit 2
  fi
  printf '287\tfalse\tREVIEW_REQUIRED\thttps://github.example.com/VilnaCRM-Org/user-service/pull/287\t%s\n' "$(git rev-parse HEAD)"
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

if [[ "${1:-}" == "pr" && "${2:-}" == "comment" ]]; then
  printf 'pr comment %s\n' "$*" >> "${GH_PR_COMMENT_LOG:-/dev/null}"
  exit 0
fi

if [[ "${1:-}" == "api" && "${2:-}" == repos/*/statuses/* ]]; then
  printf 'status %s\n' "$*" >> "${GH_STATUS_LOG:-/dev/null}"
  exit 0
fi

if [[ "${1:-}" == "pr" && "${2:-}" == "view" ]]; then
  if [[ "$*" != *"headRefOid"* || "$*" == *"statusCheckRollup"* ]]; then
    echo "PR state query must include headRefOid and must not rely on full statusCheckRollup" >&2
    exit 2
  fi
  printf '287\tfalse\tREVIEW_REQUIRED\thttps://github.example.com/VilnaCRM-Org/user-service/pull/287\t%s\n' "$(git rev-parse HEAD)"
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

if [[ "${1:-}" == "pr" && "${2:-}" == "comment" ]]; then
  printf 'pr comment %s\n' "$*" >> "${GH_PR_COMMENT_LOG:-/dev/null}"
  exit 0
fi

if [[ "${1:-}" == "api" && "${2:-}" == repos/*/statuses/* ]]; then
  printf 'status %s\n' "$*" >> "${GH_STATUS_LOG:-/dev/null}"
  exit 0
fi

if [[ "${1:-}" == "pr" && "${2:-}" == "view" ]]; then
  if [[ "$*" != *"headRefOid"* || "$*" == *"statusCheckRollup"* ]]; then
    echo "PR state query must include headRefOid and must not rely on full statusCheckRollup" >&2
    exit 2
  fi
  printf '287\tfalse\tREVIEW_REQUIRED\thttps://github.example.com/VilnaCRM-Org/user-service/pull/287\t%s\n' "$(git rev-parse HEAD)"
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

if [[ "${1:-}" == "pr" && "${2:-}" == "comment" ]]; then
  printf 'pr comment %s\n' "$*" >> "${GH_PR_COMMENT_LOG:-/dev/null}"
  exit 0
fi

if [[ "${1:-}" == "api" && "${2:-}" == repos/*/statuses/* ]]; then
  printf 'status %s\n' "$*" >> "${GH_STATUS_LOG:-/dev/null}"
  exit 0
fi

if [[ "${1:-}" == "pr" && "${2:-}" == "view" ]]; then
  if [[ "$*" != *"headRefOid"* || "$*" == *"statusCheckRollup"* ]]; then
    echo "PR state query must include headRefOid and must not rely on full statusCheckRollup" >&2
    exit 2
  fi
  printf '287\tfalse\tREVIEW_REQUIRED\thttps://github.example.com/VilnaCRM-Org/user-service/pull/287\t%s\n' "$(git rev-parse HEAD)"
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

if [[ "${1:-}" == "pr" && "${2:-}" == "comment" ]]; then
  printf 'pr comment %s\n' "$*" >> "${GH_PR_COMMENT_LOG:-/dev/null}"
  exit 0
fi

if [[ "${1:-}" == "api" && "${2:-}" == repos/*/statuses/* ]]; then
  printf 'status %s\n' "$*" >> "${GH_STATUS_LOG:-/dev/null}"
  exit 0
fi

if [[ "${1:-}" == "pr" && "${2:-}" == "view" ]]; then
  printf '287\tfalse\tREVIEW_REQUIRED\thttps://github.example.com/VilnaCRM-Org/user-service/pull/287\t0000000000000000000000000000000000000000\n'
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
  cp "${BMAD_PASS_REPORT}" "$output_file"
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"
}

write_mutating_bmad_pass_codex_stub() {
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
  cp "${BMAD_PASS_REPORT}" "$output_file"

  case "${BMAD_REPORT_MUTATION:-}" in
    missing_system_attribute)
      sed -i '/^- Repairability:/d' "$output_file"
      ;;
    weak_test_matrix)
      perl -0pi -e 's/Test Case Matrix:\n.*?\n\nAutomated Test And CI Coverage:/Test Case Matrix:\n- Every FR\/NFR quality requirement has positive success cases mapped to automated PHPUnit CI evidence; missing gaps: none. Score: 5\/5 PASS\n\nAutomated Test And CI Coverage:/s' "$output_file"
      ;;
    weak_auto_coverage)
      perl -0pi -e 's/Automated Test And CI Coverage:\n.*?\n\nFlaky Test Risk:/Automated Test And CI Coverage:\n- Every FR\/NFR quality case has automated unit coverage; missing gaps: none. Score: 5\/5 PASS\n\nFlaky Test Risk:/s' "$output_file"
      ;;
    weak_flaky_risk)
      perl -0pi -e 's/Flaky Test Risk:\n.*?\n\nManual Test Evidence:/Flaky Test Risk:\n- Flaky risk reviewed. Score: 5\/5 PASS\n\nManual Test Evidence:/s' "$output_file"
      ;;
  esac
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
EXPANDED_QUALITY_SCORECARD: PASS
SYSTEM_QUALITY_ATTRIBUTES_SCORECARD: PASS
WHOLE_CODEBASE_IMPACT: PASS
GRAPH_IMPACT_CONTEXT: PASS
TEST_CASE_MATRIX: PASS
AUTO_TEST_COVERAGE: PASS
FLAKY_TEST_RISK: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
EXPANDED_QUALITY_MIN_SCORE: 5/5
IMPACT_ANALYSIS_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: PASSING
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

Expanded Quality Scorecard:
- Functional Suitability: 5/5 PASS
- Performance Resource Sustainability: 5/5 PASS
- Compatibility Coexistence: 5/5 PASS
- Interaction Capability Accessibility: 5/5 PASS
- Reliability Resilience: 5/5 PASS
- Security Privacy Accountability: 5/5 PASS
- Maintainability Testability: 5/5 PASS
- Flexibility Portability: 5/5 PASS
- Safety Harm Prevention: 5/5 PASS
- Data Quality Integrity: 5/5 PASS
- Operational Excellence Releaseability: 5/5 PASS
- Observability Diagnosability: 5/5 PASS
- Supply-Chain Integrity: 5/5 PASS
- Compliance Governance: 5/5 PASS
- Sustainability Resource Impact: 5/5 PASS
- AI Automation Governance: 5/5 PASS

Whole-Codebase Impact Analysis:
- Runtime paths: wrapper-generated local relationship graph edge evidence inspected in codebase-graph-impact-context.md: 5/5 PASS
- Architecture and layer boundaries: 5/5 PASS
- Domain model: 5/5 PASS
- Persistence and database: 5/5 PASS
- Public API and schema: 5/5 PASS
- Async events and queues: 5/5 PASS
- Configuration and environment: 5/5 PASS
- Dependencies and lockfiles: 5/5 PASS
- CI and workflows: 5/5 PASS
- Tests and fixtures: 5/5 PASS
- Documentation: 5/5 PASS
- Operations and observability: 5/5 PASS
- Security and privacy: 5/5 PASS
- Backward compatibility: 5/5 PASS

Graph Impact Context:
- Provider/artifact path: wrapper-generated local relationship graph at codebase-graph-impact-context.md; changed-file relationship edges inspected and source files validated: 5/5 PASS

Manual Test Evidence:
- Manual evidence reviewed: 5/5 PASS
- Not applicable for shell validation fixture.

QA Verification:
- QA verification completed: 5/5 PASS
- make ci: PASS

GitHub Completion Gate:
- GitHub completion verified: 5/5 PASS
- reviewDecision REVIEW_REQUIRED, unresolved threads 0.

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

  cp "${BMAD_PASS_REPORT}" "$output_file"
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
    AI_REVIEW_GITHUB_STATUS_CONTEXT="Custom & Status" \
    AI_REVIEW_GITHUB_STATUS_EXCLUDED_CONTEXT="Custom & Excluded" \
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
  run grep -F "publishes its own GitHub status as \`Custom & Status\`" "$prompt_capture"
  assert_success
  run grep -F "excludes check context \`Custom & Excluded\`" "$prompt_capture"
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
  cp "${BMAD_PASS_REPORT}" "$output_file"
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

  cp "${BMAD_PASS_REPORT}" "$output_file"
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
  cp "${BMAD_PASS_REPORT}" "$output_file"
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
  cp "${BMAD_PASS_REPORT}" "$output_file"
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

  cp "${BMAD_PASS_REPORT}" "$output_file"
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
    BMAD_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    BMAD_REVIEW_VERIFY_CMD=true \
    BMAD_REVIEW_MAX_ITER=1 \
    bash -c "make bmad-fr-nfr-review-gate 2>&1"

  assert_success
  assert_output --partial "AI review PASS."

  run grep -F "Use PR \`123\`" "$prompt_capture"
  assert_success
  run grep -F "against base ref \`refs/remotes/origin/main\`" "$prompt_capture"
  assert_success
  run grep -F "Base ref: origin/main" "${BATS_TEST_TMPDIR}/ai-review/codebase-graph-impact-context.md"
  assert_success
  run grep -F "Passing threshold: every applicable FR, NFR, catalog category, expanded quality" "$prompt_capture"
  assert_success
  run grep -F "\`5/5\`" "$prompt_capture"
  assert_success
  run grep -F "Performance, Usability, Maintainability, Availability, Interoperability, Security, Manageability, Automatability, Dependability" "$prompt_capture"
  assert_success
  run grep -F "Functional Suitability, Performance Resource Sustainability" "$prompt_capture"
  assert_success
  run grep -F "Runtime paths, Architecture and layer boundaries" "$prompt_capture"
  assert_success
  run grep -F "Required graph-backed whole-codebase impact context is at" "$prompt_capture"
  assert_success
  run grep -F "wrapper-generated GitHub/CI corroboration" "$prompt_capture"
  assert_success
  run grep -F "GRAPH_IMPACT_CONTEXT: PASS" "$prompt_capture"
  assert_success
  run test -f "${BATS_TEST_TMPDIR}/ai-review/codebase-graph-impact-context.md"
  assert_success
  run test -f "${BATS_TEST_TMPDIR}/ai-review/bmad-required-impact-and-github-context.md"
  assert_success
  run grep -F "BMAD Required Graph Impact Context" "${BATS_TEST_TMPDIR}/ai-review/codebase-graph-impact-context.md"
  assert_success
  run grep -F "Required Local Relationship Graph" "${BATS_TEST_TMPDIR}/ai-review/codebase-graph-impact-context.md"
  assert_success
  run grep -F "BMAD GitHub/CI Corroboration Context" "${BATS_TEST_TMPDIR}/ai-review/bmad-required-impact-and-github-context.md"
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
EXPANDED_QUALITY_SCORECARD: PASS
SYSTEM_QUALITY_ATTRIBUTES_SCORECARD: PASS
WHOLE_CODEBASE_IMPACT: PASS
GRAPH_IMPACT_CONTEXT: PASS
TEST_CASE_MATRIX: PASS
AUTO_TEST_COVERAGE: PASS
FLAKY_TEST_RISK: PASS
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
  cp "${BMAD_PASS_REPORT}" "$output_file"
  sed -i '0,/FR-01 evidence: 5\/5 PASS/s//FR-01 evidence: 0\/5 PASS/' "$output_file"
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
  local prompt_capture="${BATS_TEST_TMPDIR}/prompt.txt"
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

  cat > "${PROMPT_CAPTURE:-/dev/null}"
  cat > "$output_file" <<'STATUS'
STATUS: PASS
0 issues.
FR_NFR_SCORECARD: PASS
NFR_CATALOG_SCORECARD: PASS
EXPANDED_QUALITY_SCORECARD: PASS
WHOLE_CODEBASE_IMPACT: PASS
GRAPH_IMPACT_CONTEXT: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 4/5
NFR_CATALOG_MIN_SCORE: 4/5
GITHUB_COMPLETION_STATE: PASSING
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

Expanded Quality Scorecard:
- Functional Suitability: 5/5 PASS
- Performance Resource Sustainability: 5/5 PASS
- Compatibility Coexistence: 5/5 PASS
- Interaction Capability Accessibility: 5/5 PASS
- Reliability Resilience: 5/5 PASS
- Security Privacy Accountability: 5/5 PASS
- Maintainability Testability: 5/5 PASS
- Flexibility Portability: 5/5 PASS
- Safety Harm Prevention: 5/5 PASS
- Data Quality Integrity: 5/5 PASS
- Operational Excellence Releaseability: 5/5 PASS
- Observability Diagnosability: 5/5 PASS
- Supply-Chain Integrity: 5/5 PASS
- Compliance Governance: 5/5 PASS
- Sustainability Resource Impact: 5/5 PASS
- AI Automation Governance: 5/5 PASS

Whole-Codebase Impact Analysis:
- Runtime paths: wrapper-generated local relationship graph edge evidence inspected in codebase-graph-impact-context.md: 5/5 PASS
- Architecture and layer boundaries: 5/5 PASS
- Domain model: 5/5 PASS
- Persistence and database: 5/5 PASS
- Public API and schema: 5/5 PASS
- Async events and queues: 5/5 PASS
- Configuration and environment: 5/5 PASS
- Dependencies and lockfiles: 5/5 PASS
- CI and workflows: 5/5 PASS
- Tests and fixtures: 5/5 PASS
- Documentation: 5/5 PASS
- Operations and observability: 5/5 PASS
- Security and privacy: 5/5 PASS
- Backward compatibility: 5/5 PASS

Graph Impact Context:
- Provider/artifact path: wrapper-generated local relationship graph at codebase-graph-impact-context.md; changed-file relationship edges inspected and source files validated: 5/5 PASS

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
    PROMPT_CAPTURE="$prompt_capture" \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_REVIEW_PROMPT=scripts/ai-review-prompts/bmad-fr-nfr-review.md \
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

  run grep -F "FR_NFR_MIN_SCORE: 4/5" "$prompt_capture"
  assert_success
  run grep -F "NFR_CATALOG_MIN_SCORE: 4/5" "$prompt_capture"
  assert_success
  run grep -F 'evidence at `4/5` or higher' "$prompt_capture"
  assert_success
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
EXPANDED_QUALITY_SCORECARD: PASS
WHOLE_CODEBASE_IMPACT: PASS
GRAPH_IMPACT_CONTEXT: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
EXPANDED_QUALITY_MIN_SCORE: 5/5
IMPACT_ANALYSIS_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: PASSING
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

Expanded Quality Scorecard:
- Functional Suitability: 5/5 PASS
- Performance Resource Sustainability: 5/5 PASS
- Compatibility Coexistence: 5/5 PASS
- Interaction Capability Accessibility: 5/5 PASS
- Reliability Resilience: 5/5 PASS
- Security Privacy Accountability: 5/5 PASS
- Maintainability Testability: 5/5 PASS
- Flexibility Portability: 5/5 PASS
- Safety Harm Prevention: 5/5 PASS
- Data Quality Integrity: 5/5 PASS
- Operational Excellence Releaseability: 5/5 PASS
- Observability Diagnosability: 5/5 PASS
- Supply-Chain Integrity: 5/5 PASS
- Compliance Governance: 5/5 PASS
- Sustainability Resource Impact: 5/5 PASS
- AI Automation Governance: 5/5 PASS

Whole-Codebase Impact Analysis:
- Runtime paths: wrapper-generated local relationship graph edge evidence inspected in codebase-graph-impact-context.md: 5/5 PASS
- Architecture and layer boundaries: 5/5 PASS
- Domain model: 5/5 PASS
- Persistence and database: 5/5 PASS
- Public API and schema: 5/5 PASS
- Async events and queues: 5/5 PASS
- Configuration and environment: 5/5 PASS
- Dependencies and lockfiles: 5/5 PASS
- CI and workflows: 5/5 PASS
- Tests and fixtures: 5/5 PASS
- Documentation: 5/5 PASS
- Operations and observability: 5/5 PASS
- Security and privacy: 5/5 PASS
- Backward compatibility: 5/5 PASS

Graph Impact Context:
- Provider/artifact path: wrapper-generated local relationship graph at codebase-graph-impact-context.md; changed-file relationship edges inspected and source files validated: 5/5 PASS

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
EXPANDED_QUALITY_SCORECARD: PASS
WHOLE_CODEBASE_IMPACT: PASS
GRAPH_IMPACT_CONTEXT: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
EXPANDED_QUALITY_MIN_SCORE: 5/5
IMPACT_ANALYSIS_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: PASSING
CI_CHECK_ROLLUP: PASSING

Requirement Scorecard:
- FR-01 evidence: 5/5 PASS

NFR Catalog Scorecard:
- Performance: 5/5 PASS

Expanded Quality Scorecard:
- Functional Suitability: 5/5 PASS
- Performance Resource Sustainability: 5/5 PASS
- Compatibility Coexistence: 5/5 PASS
- Interaction Capability Accessibility: 5/5 PASS
- Reliability Resilience: 5/5 PASS
- Security Privacy Accountability: 5/5 PASS
- Maintainability Testability: 5/5 PASS
- Flexibility Portability: 5/5 PASS
- Safety Harm Prevention: 5/5 PASS
- Data Quality Integrity: 5/5 PASS
- Operational Excellence Releaseability: 5/5 PASS
- Observability Diagnosability: 5/5 PASS
- Supply-Chain Integrity: 5/5 PASS
- Compliance Governance: 5/5 PASS
- Sustainability Resource Impact: 5/5 PASS
- AI Automation Governance: 5/5 PASS

Whole-Codebase Impact Analysis:
- Runtime paths: wrapper-generated local relationship graph edge evidence inspected in codebase-graph-impact-context.md: 5/5 PASS
- Architecture and layer boundaries: 5/5 PASS
- Domain model: 5/5 PASS
- Persistence and database: 5/5 PASS
- Public API and schema: 5/5 PASS
- Async events and queues: 5/5 PASS
- Configuration and environment: 5/5 PASS
- Dependencies and lockfiles: 5/5 PASS
- CI and workflows: 5/5 PASS
- Tests and fixtures: 5/5 PASS
- Documentation: 5/5 PASS
- Operations and observability: 5/5 PASS
- Security and privacy: 5/5 PASS
- Backward compatibility: 5/5 PASS

Graph Impact Context:
- Provider/artifact path: wrapper-generated local relationship graph at codebase-graph-impact-context.md; changed-file relationship edges inspected and source files validated: 5/5 PASS

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

@test "ai-review-loop rejects PASS without expanded quality dimension coverage" {
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
EXPANDED_QUALITY_SCORECARD: PASS
WHOLE_CODEBASE_IMPACT: PASS
GRAPH_IMPACT_CONTEXT: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
EXPANDED_QUALITY_MIN_SCORE: 5/5
IMPACT_ANALYSIS_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: PASSING
CI_CHECK_ROLLUP: PASSING

Requirement Scorecard:
- FR-01 evidence: 5/5 PASS

NFR Catalog Scorecard:
- Security: 5/5 PASS

Expanded Quality Scorecard:
- Functional Suitability: 5/5 PASS

Whole-Codebase Impact Analysis:
- Runtime paths: wrapper-generated local relationship graph edge evidence inspected in codebase-graph-impact-context.md: 5/5 PASS

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
    AI_REVIEW_NFR_CATEGORIES="Security" \
    AI_REVIEW_QUALITY_DIMENSIONS="Functional Suitability, Safety Harm Prevention" \
    AI_REVIEW_IMPACT_SURFACES="Runtime paths" \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "Warning: BMAD PASS output lacks 5/5 evidence for expanded quality dimension: Safety Harm Prevention."
}

@test "ai-review-loop rejects PASS without whole-codebase impact surface coverage" {
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
EXPANDED_QUALITY_SCORECARD: PASS
WHOLE_CODEBASE_IMPACT: PASS
GRAPH_IMPACT_CONTEXT: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
EXPANDED_QUALITY_MIN_SCORE: 5/5
IMPACT_ANALYSIS_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: PASSING
CI_CHECK_ROLLUP: PASSING

Requirement Scorecard:
- FR-01 evidence: 5/5 PASS

NFR Catalog Scorecard:
- Security: 5/5 PASS

Expanded Quality Scorecard:
- Functional Suitability: 5/5 PASS

Whole-Codebase Impact Analysis:
- Runtime paths: wrapper-generated local relationship graph edge evidence inspected in codebase-graph-impact-context.md: 5/5 PASS

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
    AI_REVIEW_NFR_CATEGORIES="Security" \
    AI_REVIEW_QUALITY_DIMENSIONS="Functional Suitability" \
    AI_REVIEW_IMPACT_SURFACES="Runtime paths, Documentation" \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "Warning: BMAD PASS output lacks 5/5 evidence for impact surface: Documentation."
}

@test "ai-review-loop rejects PASS without graph impact context evidence" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local impact_context="${BATS_TEST_TMPDIR}/graph-context.md"
  mkdir -p "$bin_dir"
  printf '%s\n' "wrapper-generated local relationship graph" > "$impact_context"

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
EXPANDED_QUALITY_SCORECARD: PASS
WHOLE_CODEBASE_IMPACT: PASS
GRAPH_IMPACT_CONTEXT: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
EXPANDED_QUALITY_MIN_SCORE: 5/5
IMPACT_ANALYSIS_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: PASSING
CI_CHECK_ROLLUP: PASSING

Requirement Scorecard:
- FR-01 evidence: 5/5 PASS

NFR Catalog Scorecard:
- Security: 5/5 PASS

Expanded Quality Scorecard:
- Functional Suitability: 5/5 PASS

Whole-Codebase Impact Analysis:
- Runtime paths: wrapper-generated local relationship graph edge evidence inspected in codebase-graph-impact-context.md: 5/5 PASS

Graph Impact Context:
- Required graph impact context reviewed: 5/5 PASS

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
    AI_REVIEW_REQUIRED_GATE_MARKERS="FR_NFR_SCORECARD: PASS,NFR_CATALOG_SCORECARD: PASS,EXPANDED_QUALITY_SCORECARD: PASS,WHOLE_CODEBASE_IMPACT: PASS,GRAPH_IMPACT_CONTEXT: PASS,MANUAL_TEST_EVIDENCE: PASS,QA_BEST_PRACTICES: PASS,GITHUB_COMPLETION_GATE: PASS,CI_GATE: PASS" \
    AI_REVIEW_NFR_CATEGORIES="Security" \
    AI_REVIEW_QUALITY_DIMENSIONS="Functional Suitability" \
    AI_REVIEW_IMPACT_SURFACES="Runtime paths" \
    AI_REVIEW_IMPACT_CONTEXT="$impact_context" \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "Warning: BMAD PASS output lacks graph provider or graph artifact evidence."
}

@test "ai-review-loop requires impact context when graph marker is required" {
  run env \
    AI_REVIEW_BASE=HEAD \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_VERIFY_CMD=true \
    AI_REVIEW_REQUIRED_GATE_MARKERS="GRAPH_IMPACT_CONTEXT: PASS" \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "AI_REVIEW_IMPACT_CONTEXT is required when GRAPH_IMPACT_CONTEXT: PASS is required."
}

@test "ai-review-loop requires readable impact context when graph marker is required" {
  run env \
    AI_REVIEW_BASE=HEAD \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_VERIFY_CMD=true \
    AI_REVIEW_REQUIRED_GATE_MARKERS="GRAPH_IMPACT_CONTEXT: PASS" \
    AI_REVIEW_IMPACT_CONTEXT="${BATS_TEST_TMPDIR}/missing-graph-context.md" \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "AI_REVIEW_IMPACT_CONTEXT is not readable: ${BATS_TEST_TMPDIR}/missing-graph-context.md"
}

@test "ai-review-loop rejects PASS when whole-codebase impact omits graph evidence" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local impact_context="${BATS_TEST_TMPDIR}/graph-context.md"
  mkdir -p "$bin_dir"
  printf '%s\n' "wrapper-generated local relationship graph" > "$impact_context"

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
EXPANDED_QUALITY_SCORECARD: PASS
WHOLE_CODEBASE_IMPACT: PASS
GRAPH_IMPACT_CONTEXT: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
EXPANDED_QUALITY_MIN_SCORE: 5/5
IMPACT_ANALYSIS_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: PASSING
CI_CHECK_ROLLUP: PASSING

Requirement Scorecard:
- FR-01 evidence: 5/5 PASS

NFR Catalog Scorecard:
- Security: 5/5 PASS

Expanded Quality Scorecard:
- Functional Suitability: 5/5 PASS

Whole-Codebase Impact Analysis:
- Runtime paths: changed files inspected: 5/5 PASS

Graph Impact Context:
- Provider/artifact path: wrapper-generated local relationship graph at codebase-graph-impact-context.md; changed-file relationship edges inspected and source files validated: 5/5 PASS

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
    AI_REVIEW_REQUIRED_GATE_MARKERS="FR_NFR_SCORECARD: PASS,NFR_CATALOG_SCORECARD: PASS,EXPANDED_QUALITY_SCORECARD: PASS,WHOLE_CODEBASE_IMPACT: PASS,GRAPH_IMPACT_CONTEXT: PASS,MANUAL_TEST_EVIDENCE: PASS,QA_BEST_PRACTICES: PASS,GITHUB_COMPLETION_GATE: PASS,CI_GATE: PASS" \
    AI_REVIEW_NFR_CATEGORIES="Security" \
    AI_REVIEW_QUALITY_DIMENSIONS="Functional Suitability" \
    AI_REVIEW_IMPACT_SURFACES="Runtime paths" \
    AI_REVIEW_IMPACT_CONTEXT="$impact_context" \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "Warning: BMAD PASS output does not use graph or relationship evidence in Whole-Codebase Impact Analysis."
}

@test "ai-review-loop rejects PASS without pinned system quality attribute coverage" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  mkdir -p "$bin_dir"
  write_mutating_bmad_pass_codex_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    BMAD_REPORT_MUTATION=missing_system_attribute \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_BASE=HEAD \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_VERIFY_CMD=true \
    AI_REVIEW_REQUIRE_GATE_MARKERS=true \
    AI_REVIEW_REQUIRE_SCORECARD_VALIDATION=true \
    AI_REVIEW_REQUIRED_GATE_MARKERS="FR_NFR_SCORECARD: PASS,NFR_CATALOG_SCORECARD: PASS,SYSTEM_QUALITY_ATTRIBUTES_SCORECARD: PASS" \
    AI_REVIEW_SYSTEM_QUALITY_ATTRIBUTES="Accessibility, Repairability" \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "Warning: BMAD PASS output lacks 5/5 evidence for system quality attribute: Repairability."
}

@test "ai-review-loop rejects PASS without positive negative and edge test matrix evidence" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  mkdir -p "$bin_dir"
  write_mutating_bmad_pass_codex_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    BMAD_REPORT_MUTATION=weak_test_matrix \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_BASE=HEAD \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_VERIFY_CMD=true \
    AI_REVIEW_REQUIRE_GATE_MARKERS=true \
    AI_REVIEW_REQUIRE_SCORECARD_VALIDATION=true \
    AI_REVIEW_REQUIRED_GATE_MARKERS="FR_NFR_SCORECARD: PASS,NFR_CATALOG_SCORECARD: PASS,TEST_CASE_MATRIX: PASS" \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "Warning: BMAD PASS output lacks negative test-case evidence in Test Case Matrix."
}

@test "ai-review-loop rejects PASS without automated coverage to CI mapping" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  mkdir -p "$bin_dir"
  write_mutating_bmad_pass_codex_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    BMAD_REPORT_MUTATION=weak_auto_coverage \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_BASE=HEAD \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_VERIFY_CMD=true \
    AI_REVIEW_REQUIRE_GATE_MARKERS=true \
    AI_REVIEW_REQUIRE_SCORECARD_VALIDATION=true \
    AI_REVIEW_REQUIRED_GATE_MARKERS="FR_NFR_SCORECARD: PASS,NFR_CATALOG_SCORECARD: PASS,AUTO_TEST_COVERAGE: PASS" \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "Warning: BMAD PASS output lacks CI check mapping for automated coverage."
}

@test "ai-review-loop rejects PASS without flaky-test risk source evidence" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  mkdir -p "$bin_dir"
  write_mutating_bmad_pass_codex_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    BMAD_REPORT_MUTATION=weak_flaky_risk \
    AI_REVIEW_CODEX_CMD=codex \
    AI_REVIEW_BASE=HEAD \
    AI_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    AI_REVIEW_VERIFY_CMD=true \
    AI_REVIEW_REQUIRE_GATE_MARKERS=true \
    AI_REVIEW_REQUIRE_SCORECARD_VALIDATION=true \
    AI_REVIEW_REQUIRED_GATE_MARKERS="FR_NFR_SCORECARD: PASS,NFR_CATALOG_SCORECARD: PASS,FLAKY_TEST_RISK: PASS" \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_failure
  assert_output --partial "Warning: BMAD PASS output lacks concrete flaky-test risk sources inspected."
}

@test "ai-review-loop accepts bold markdown scorecard headings" {
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
GITHUB_COMPLETION_STATE: PASSING
CI_CHECK_ROLLUP: PASSING

**Requirement Scorecard:**
- FR-01 evidence: 5/5 PASS

**NFR Catalog Scorecard:**
- Security: 5/5 PASS

**Manual Test Evidence:**
- Manual evidence reviewed: 5/5 PASS

**QA Verification:**
- QA verification completed: 5/5 PASS

**GitHub Completion Gate:**
- GitHub completion verified: 5/5 PASS

**CI Gate:**
- Required CI checks verified: 5/5 PASS

**Required Fixes:**
None.
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
    AI_REVIEW_NFR_CATEGORIES="Security" \
    AI_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/ai-review-loop.sh 2>&1"

  assert_success
  assert_output --partial "AI review PASS."
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
EXPANDED_QUALITY_SCORECARD: PASS
WHOLE_CODEBASE_IMPACT: PASS
GRAPH_IMPACT_CONTEXT: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
EXPANDED_QUALITY_MIN_SCORE: 5/5
IMPACT_ANALYSIS_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: PASSING
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

Expanded Quality Scorecard:
- Functional Suitability: 5/5 PASS
- Performance Resource Sustainability: 5/5 PASS
- Compatibility Coexistence: 5/5 PASS
- Interaction Capability Accessibility: 5/5 PASS
- Reliability Resilience: 5/5 PASS
- Security Privacy Accountability: 5/5 PASS
- Maintainability Testability: 5/5 PASS
- Flexibility Portability: 5/5 PASS
- Safety Harm Prevention: 5/5 PASS
- Data Quality Integrity: 5/5 PASS
- Operational Excellence Releaseability: 5/5 PASS
- Observability Diagnosability: 5/5 PASS
- Supply-Chain Integrity: 5/5 PASS
- Compliance Governance: 5/5 PASS
- Sustainability Resource Impact: 5/5 PASS
- AI Automation Governance: 5/5 PASS

Whole-Codebase Impact Analysis:
- Runtime paths: wrapper-generated local relationship graph edge evidence inspected in codebase-graph-impact-context.md: 5/5 PASS
- Architecture and layer boundaries: 5/5 PASS
- Domain model: 5/5 PASS
- Persistence and database: 5/5 PASS
- Public API and schema: 5/5 PASS
- Async events and queues: 5/5 PASS
- Configuration and environment: 5/5 PASS
- Dependencies and lockfiles: 5/5 PASS
- CI and workflows: 5/5 PASS
- Tests and fixtures: 5/5 PASS
- Documentation: 5/5 PASS
- Operations and observability: 5/5 PASS
- Security and privacy: 5/5 PASS
- Backward compatibility: 5/5 PASS

Graph Impact Context:
- Provider/artifact path: wrapper-generated local relationship graph at codebase-graph-impact-context.md; changed-file relationship edges inspected and source files validated: 5/5 PASS

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

@test "ai-review-loop accepts GFM NFR table rows without outer pipes or with escaped pipes" {
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
EXPANDED_QUALITY_SCORECARD: PASS
WHOLE_CODEBASE_IMPACT: PASS
GRAPH_IMPACT_CONTEXT: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
EXPANDED_QUALITY_MIN_SCORE: 5/5
IMPACT_ANALYSIS_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: PASSING
CI_CHECK_ROLLUP: PASSING

Requirement Scorecard:
Source | Evidence | Score | Status
--- | --- | --- | ---
FR-01 | Verified by tests. | 5/5 | PASS

NFR Catalog Scorecard:
Category | Evidence | Score | Status
--- | --- | --- | ---
Performance | Lightweight wrapper and bounded validation. | 5/5 | PASS
Usability | Make target and docs. | 5/5 | PASS
Maintainability | Focused shell functions and Bats coverage. | 5/5 | PASS
Availability | Fails closed on unavailable gates. | 5/5 | PASS
Interoperability | Codex \| Claude adapters, GitHub CLI, and BMAD specs. | 5/5 | PASS
Security | Review control env is sanitized. | 5/5 | PASS
Manageability | Inputs are configurable through BMAD env. | 5/5 | PASS
Automatability | Non-interactive Make target. | 5/5 | PASS
Dependability | Invalid markers and scores fail closed. | 5/5 | PASS

Expanded Quality Scorecard:
- Functional Suitability: 5/5 PASS
- Performance Resource Sustainability: 5/5 PASS
- Compatibility Coexistence: 5/5 PASS
- Interaction Capability Accessibility: 5/5 PASS
- Reliability Resilience: 5/5 PASS
- Security Privacy Accountability: 5/5 PASS
- Maintainability Testability: 5/5 PASS
- Flexibility Portability: 5/5 PASS
- Safety Harm Prevention: 5/5 PASS
- Data Quality Integrity: 5/5 PASS
- Operational Excellence Releaseability: 5/5 PASS
- Observability Diagnosability: 5/5 PASS
- Supply-Chain Integrity: 5/5 PASS
- Compliance Governance: 5/5 PASS
- Sustainability Resource Impact: 5/5 PASS
- AI Automation Governance: 5/5 PASS

Whole-Codebase Impact Analysis:
- Runtime paths: wrapper-generated local relationship graph edge evidence inspected in codebase-graph-impact-context.md: 5/5 PASS
- Architecture and layer boundaries: 5/5 PASS
- Domain model: 5/5 PASS
- Persistence and database: 5/5 PASS
- Public API and schema: 5/5 PASS
- Async events and queues: 5/5 PASS
- Configuration and environment: 5/5 PASS
- Dependencies and lockfiles: 5/5 PASS
- CI and workflows: 5/5 PASS
- Tests and fixtures: 5/5 PASS
- Documentation: 5/5 PASS
- Operations and observability: 5/5 PASS
- Security and privacy: 5/5 PASS
- Backward compatibility: 5/5 PASS

Graph Impact Context:
- Provider/artifact path: wrapper-generated local relationship graph at codebase-graph-impact-context.md; changed-file relationship edges inspected and source files validated: 5/5 PASS

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

@test "ai-review-loop rejects markdown NFR table rows without score-column evidence" {
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
EXPANDED_QUALITY_SCORECARD: PASS
WHOLE_CODEBASE_IMPACT: PASS
GRAPH_IMPACT_CONTEXT: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
EXPANDED_QUALITY_MIN_SCORE: 5/5
IMPACT_ANALYSIS_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: PASSING
CI_CHECK_ROLLUP: PASSING

Requirement Scorecard:
| Source | Evidence | Score | Status |
| --- | --- | --- | --- |
| FR-01 | Verified by tests. | 5/5 | PASS |

NFR Catalog Scorecard:
| Category | Evidence | Score | Status |
| --- | --- | --- | --- |
| Performance | Evidence mentions a prior 5/5 but the score cell is missing a score. | PASS | PASS |
| Usability | Make target and docs. | 5/5 | PASS |
| Maintainability | Focused shell functions and Bats coverage. | 5/5 | PASS |
| Availability | Fails closed on unavailable gates. | 5/5 | PASS |
| Interoperability | Codex, Claude, GitHub CLI, and BMAD specs. | 5/5 | PASS |
| Security | Review control env is sanitized. | 5/5 | PASS |
| Manageability | Inputs are configurable through BMAD env. | 5/5 | PASS |
| Automatability | Non-interactive Make target. | 5/5 | PASS |
| Dependability | Invalid markers and scores fail closed. | 5/5 | PASS |

Expanded Quality Scorecard:
- Functional Suitability: 5/5 PASS
- Performance Resource Sustainability: 5/5 PASS
- Compatibility Coexistence: 5/5 PASS
- Interaction Capability Accessibility: 5/5 PASS
- Reliability Resilience: 5/5 PASS
- Security Privacy Accountability: 5/5 PASS
- Maintainability Testability: 5/5 PASS
- Flexibility Portability: 5/5 PASS
- Safety Harm Prevention: 5/5 PASS
- Data Quality Integrity: 5/5 PASS
- Operational Excellence Releaseability: 5/5 PASS
- Observability Diagnosability: 5/5 PASS
- Supply-Chain Integrity: 5/5 PASS
- Compliance Governance: 5/5 PASS
- Sustainability Resource Impact: 5/5 PASS
- AI Automation Governance: 5/5 PASS

Whole-Codebase Impact Analysis:
- Runtime paths: wrapper-generated local relationship graph edge evidence inspected in codebase-graph-impact-context.md: 5/5 PASS
- Architecture and layer boundaries: 5/5 PASS
- Domain model: 5/5 PASS
- Persistence and database: 5/5 PASS
- Public API and schema: 5/5 PASS
- Async events and queues: 5/5 PASS
- Configuration and environment: 5/5 PASS
- Dependencies and lockfiles: 5/5 PASS
- CI and workflows: 5/5 PASS
- Tests and fixtures: 5/5 PASS
- Documentation: 5/5 PASS
- Operations and observability: 5/5 PASS
- Security and privacy: 5/5 PASS
- Backward compatibility: 5/5 PASS

Graph Impact Context:
- Provider/artifact path: wrapper-generated local relationship graph at codebase-graph-impact-context.md; changed-file relationship edges inspected and source files validated: 5/5 PASS

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
  assert_output --partial "Warning: BMAD PASS output lacks 5/5 evidence for NFR category: Performance."
}

@test "ai-review-loop rejects short markdown NFR rows without inheriting a prior score cell" {
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
EXPANDED_QUALITY_SCORECARD: PASS
WHOLE_CODEBASE_IMPACT: PASS
GRAPH_IMPACT_CONTEXT: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
EXPANDED_QUALITY_MIN_SCORE: 5/5
IMPACT_ANALYSIS_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: PASSING
CI_CHECK_ROLLUP: PASSING

Requirement Scorecard:
| Source | Evidence | Score | Status |
| --- | --- | --- | --- |
| FR-01 | Verified by tests. | 5/5 | PASS |

NFR Catalog Scorecard:
| Category | Evidence | Score | Status |
| --- | --- | --- | --- |
| Performance | Lightweight wrapper and bounded validation. | 5/5 | PASS |
| Usability | Short malformed row without a score cell. |
| Maintainability | Focused shell functions and Bats coverage. | 5/5 | PASS |
| Availability | Fails closed on unavailable gates. | 5/5 | PASS |
| Interoperability | Codex, Claude, GitHub CLI, and BMAD specs. | 5/5 | PASS |
| Security | Review control env is sanitized. | 5/5 | PASS |
| Manageability | Inputs are configurable through BMAD env. | 5/5 | PASS |
| Automatability | Non-interactive Make target. | 5/5 | PASS |
| Dependability | Invalid markers and scores fail closed. | 5/5 | PASS |

Expanded Quality Scorecard:
- Functional Suitability: 5/5 PASS
- Performance Resource Sustainability: 5/5 PASS
- Compatibility Coexistence: 5/5 PASS
- Interaction Capability Accessibility: 5/5 PASS
- Reliability Resilience: 5/5 PASS
- Security Privacy Accountability: 5/5 PASS
- Maintainability Testability: 5/5 PASS
- Flexibility Portability: 5/5 PASS
- Safety Harm Prevention: 5/5 PASS
- Data Quality Integrity: 5/5 PASS
- Operational Excellence Releaseability: 5/5 PASS
- Observability Diagnosability: 5/5 PASS
- Supply-Chain Integrity: 5/5 PASS
- Compliance Governance: 5/5 PASS
- Sustainability Resource Impact: 5/5 PASS
- AI Automation Governance: 5/5 PASS

Whole-Codebase Impact Analysis:
- Runtime paths: wrapper-generated local relationship graph edge evidence inspected in codebase-graph-impact-context.md: 5/5 PASS
- Architecture and layer boundaries: 5/5 PASS
- Domain model: 5/5 PASS
- Persistence and database: 5/5 PASS
- Public API and schema: 5/5 PASS
- Async events and queues: 5/5 PASS
- Configuration and environment: 5/5 PASS
- Dependencies and lockfiles: 5/5 PASS
- CI and workflows: 5/5 PASS
- Tests and fixtures: 5/5 PASS
- Documentation: 5/5 PASS
- Operations and observability: 5/5 PASS
- Security and privacy: 5/5 PASS
- Backward compatibility: 5/5 PASS

Graph Impact Context:
- Provider/artifact path: wrapper-generated local relationship graph at codebase-graph-impact-context.md; changed-file relationship edges inspected and source files validated: 5/5 PASS

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
  cp "${BMAD_PASS_REPORT}" "$output_file"
  sed -i '/Requirement Scorecard:/i Review Notes:\n- A draft mention of Requirement Scorecard: 0/5 should not be parsed as the scorecard section.\n' "$output_file"
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
  cp "${BMAD_PASS_REPORT}" "$output_file"
  sed -i '/Manual evidence reviewed/a - Manual session date 1/5/2026; not a score.' "$output_file"
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
  cp "${BMAD_PASS_REPORT}" "$output_file"
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
  assert_output --partial "Reached AI_REVIEW_MAX_ITER=1 without PASS."
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

@test "bmad-fr-nfr-review-gate publishes PR comment and success GitHub status after PASS" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/example"
  local log_dir="${BATS_TEST_TMPDIR}/ai-review"
  local status_log="${BATS_TEST_TMPDIR}/status.log"
  local comment_log="${BATS_TEST_TMPDIR}/comment.log"
  local checks_args_log="${BATS_TEST_TMPDIR}/checks-args.log"

  mkdir -p "$bin_dir" "$spec_dir"
  printf "# PRD\n\nFR-01: Works.\n" > "${spec_dir}/prd.md"
  write_bmad_pass_codex_stub "$bin_dir"
  write_successful_bmad_gh_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    GH_STATUS_LOG="$status_log" \
    GH_PR_COMMENT_LOG="$comment_log" \
    GH_PR_CHECKS_ARGS_LOG="$checks_args_log" \
    AI_REVIEW_CODEX_CMD=codex \
    BMAD_REVIEW_SPEC_PATH="$spec_dir" \
    BMAD_REVIEW_BASE=HEAD \
    BMAD_REVIEW_LOG_DIR="$log_dir" \
    BMAD_REVIEW_VERIFY_CMD=true \
    BMAD_REVIEW_MAX_ITER=1 \
    bash -c "./scripts/bmad-fr-nfr-review-gate.sh 2>&1"

  assert_success
  assert_output --partial "AI review PASS."

  run grep -F "state=pending" "$status_log"
  assert_success
  run grep -F "state=success" "$status_log"
  assert_success
  run grep -F "context=BMAD FR/NFR Review Gate" "$status_log"
  assert_success
  run grep -F "pr comment 287 --body-file" "$comment_log"
  assert_success
  run grep -F 'select(.name != "BMAD FR/NFR Review Gate")' "$checks_args_log"
  assert_success
  run bash -c "grep -R \"BMAD FR/NFR Review Gate: PASS\" '$log_dir'/pr-comment-PASS.*.md"
  assert_success
}

@test "bmad-fr-nfr-review-gate excludes BMAD status check when status publishing is disabled" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/example"
  local checks_args_log="${BATS_TEST_TMPDIR}/checks-args.log"

  mkdir -p "$bin_dir" "$spec_dir"
  printf "# PRD\n\nFR-01: Works.\n" > "${spec_dir}/prd.md"
  write_bmad_pass_codex_stub "$bin_dir"
  write_successful_bmad_gh_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    GH_PR_CHECKS_ARGS_LOG="$checks_args_log" \
    AI_REVIEW_CODEX_CMD=codex \
    BMAD_REVIEW_SPEC_PATH="$spec_dir" \
    BMAD_REVIEW_BASE=HEAD \
    BMAD_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    BMAD_REVIEW_VERIFY_CMD=true \
    BMAD_REVIEW_MAX_ITER=1 \
    BMAD_REVIEW_POST_PR_COMMENT=false \
    BMAD_REVIEW_POST_GITHUB_STATUS=false \
    bash -c "./scripts/bmad-fr-nfr-review-gate.sh 2>&1"

  assert_success
  assert_output --partial "AI review PASS."

  run grep -F 'select(.name != "BMAD FR/NFR Review Gate")' "$checks_args_log"
  assert_success
}

@test "bmad-fr-nfr-review-gate excludes custom status context after argument parsing" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/example"
  local checks_args_log="${BATS_TEST_TMPDIR}/checks-args.log"

  mkdir -p "$bin_dir" "$spec_dir"
  printf "# PRD\n\nFR-01: Works.\n" > "${spec_dir}/prd.md"
  write_bmad_pass_codex_stub "$bin_dir"
  write_successful_bmad_gh_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    GH_PR_CHECKS_ARGS_LOG="$checks_args_log" \
    AI_REVIEW_CODEX_CMD=codex \
    BMAD_REVIEW_SPEC_PATH="$spec_dir" \
    BMAD_REVIEW_BASE=HEAD \
    BMAD_REVIEW_LOG_DIR="${BATS_TEST_TMPDIR}/ai-review" \
    BMAD_REVIEW_VERIFY_CMD=true \
    BMAD_REVIEW_MAX_ITER=1 \
    BMAD_REVIEW_POST_PR_COMMENT=false \
    BMAD_REVIEW_POST_GITHUB_STATUS=false \
    bash -c "./scripts/bmad-fr-nfr-review-gate.sh --status-context 'Custom Gate' 2>&1"

  assert_success
  assert_output --partial "AI review PASS."

  run grep -F 'select(.name != "Custom Gate")' "$checks_args_log"
  assert_success
}

@test "bmad-fr-nfr-review-gate publishes failure status before Codex fix and success after PASS" {
  local bin_dir="${BATS_TEST_TMPDIR}/bin"
  local spec_dir="${BATS_TEST_TMPDIR}/specs/example"
  local log_dir="${BATS_TEST_TMPDIR}/ai-review"
  local status_log="${BATS_TEST_TMPDIR}/status.log"
  local comment_log="${BATS_TEST_TMPDIR}/comment.log"
  local review_count="${BATS_TEST_TMPDIR}/review-count"

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

  if [[ "$output_file" == *"/fix-"* ]]; then
    echo "Applied BMAD fix." > "$output_file"
    exit 0
  fi

  count=0
  if [[ -f "${REVIEW_COUNT_FILE}" ]]; then
    count="$(cat "${REVIEW_COUNT_FILE}")"
  fi
  count=$((count + 1))
  printf "%s" "$count" > "${REVIEW_COUNT_FILE}"

  if [[ "$count" -eq 1 ]]; then
    cat > "$output_file" <<'STATUS'
STATUS: FAIL
Issues:
1. Missing BMAD evidence.
FR_NFR_SCORECARD: FAIL
NFR_CATALOG_SCORECARD: FAIL
MANUAL_TEST_EVIDENCE: FAIL
QA_BEST_PRACTICES: FAIL
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
STATUS
    exit 0
  fi

  cp "${BMAD_PASS_REPORT}" "$output_file"
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"
  write_successful_bmad_gh_stub "$bin_dir"

  run env \
    PATH="$bin_dir:$PATH" \
    GH_STATUS_LOG="$status_log" \
    GH_PR_COMMENT_LOG="$comment_log" \
    REVIEW_COUNT_FILE="$review_count" \
    AI_REVIEW_CODEX_CMD=codex \
    BMAD_REVIEW_SPEC_PATH="$spec_dir" \
    BMAD_REVIEW_BASE=HEAD \
    BMAD_REVIEW_LOG_DIR="$log_dir" \
    BMAD_REVIEW_VERIFY_CMD=true \
    BMAD_REVIEW_MAX_ITER=2 \
    bash -c "./scripts/bmad-fr-nfr-review-gate.sh 2>&1"

  assert_success
  assert_output --partial "AI review PASS."

  run grep -F "state=failure" "$status_log"
  assert_success
  run grep -F "state=success" "$status_log"
  assert_success
  run grep -F "Applied BMAD fix." "$log_dir/fix-latest.md"
  assert_success
  run grep -F "pr comment 287 --body-file" "$comment_log"
  assert_success

  local failure_line success_line
  failure_line="$(grep -n "state=failure" "$status_log" | head -n 1 | cut -d: -f1)"
  success_line="$(grep -n "state=success" "$status_log" | tail -n 1 | cut -d: -f1)"
  if [[ -z "$failure_line" || -z "$success_line" || "$failure_line" -ge "$success_line" ]]; then
    fail "Expected failure status to be published before final success status."
  fi
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
  assert_output --partial "Reached AI_REVIEW_MAX_ITER=1 without PASS."
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
  assert_output --partial "Reached AI_REVIEW_MAX_ITER=1 without PASS."
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
  assert_output --partial "GitHub preflight failed before AI review."
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
  assert_output --partial "Reached AI_REVIEW_MAX_ITER=1 without PASS."
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
  cp "${BMAD_PASS_REPORT}" "$output_file"
  exit 0
fi

echo "unexpected codex invocation: $*" >&2
exit 2
SCRIPT
  chmod +x "$bin_dir/codex"

  cat > "$bin_dir/gh" <<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail

if [[ "${1:-}" == "pr" && "${2:-}" == "comment" ]]; then
  printf 'pr comment %s\n' "$*" >> "${GH_PR_COMMENT_LOG:-/dev/null}"
  exit 0
fi

if [[ "${1:-}" == "api" && "${2:-}" == repos/*/statuses/* ]]; then
  printf 'status %s\n' "$*" >> "${GH_STATUS_LOG:-/dev/null}"
  exit 0
fi

if [[ "${1:-}" == "pr" && "${2:-}" == "view" ]]; then
  printf '287\tfalse\tREVIEW_REQUIRED\thttps://github.example.com/VilnaCRM-Org/user-service/pull/287\t%s\n' "$(git rev-parse HEAD)"
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
  assert_output --partial "Reached AI_REVIEW_MAX_ITER=1 without PASS."
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
EXPANDED_QUALITY_SCORECARD: PASS
WHOLE_CODEBASE_IMPACT: PASS
GRAPH_IMPACT_CONTEXT: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
FR_NFR_MIN_SCORE: 5/5
NFR_CATALOG_MIN_SCORE: 5/5
EXPANDED_QUALITY_MIN_SCORE: 5/5
IMPACT_ANALYSIS_MIN_SCORE: 5/5
GITHUB_COMPLETION_STATE: PASSING
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

Expanded Quality Scorecard:
- Functional Suitability: 5/5 PASS
- Performance Resource Sustainability: 5/5 PASS
- Compatibility Coexistence: 5/5 PASS
- Interaction Capability Accessibility: 5/5 PASS
- Reliability Resilience: 5/5 PASS
- Security Privacy Accountability: 5/5 PASS
- Maintainability Testability: 5/5 PASS
- Flexibility Portability: 5/5 PASS
- Safety Harm Prevention: 5/5 PASS
- Data Quality Integrity: 5/5 PASS
- Operational Excellence Releaseability: 5/5 PASS
- Observability Diagnosability: 5/5 PASS
- Supply-Chain Integrity: 5/5 PASS
- Compliance Governance: 5/5 PASS
- Sustainability Resource Impact: 5/5 PASS
- AI Automation Governance: 5/5 PASS

Whole-Codebase Impact Analysis:
- Runtime paths: wrapper-generated local relationship graph edge evidence inspected in codebase-graph-impact-context.md: 5/5 PASS
- Architecture and layer boundaries: 5/5 PASS
- Domain model: 5/5 PASS
- Persistence and database: 5/5 PASS
- Public API and schema: 5/5 PASS
- Async events and queues: 5/5 PASS
- Configuration and environment: 5/5 PASS
- Dependencies and lockfiles: 5/5 PASS
- CI and workflows: 5/5 PASS
- Tests and fixtures: 5/5 PASS
- Documentation: 5/5 PASS
- Operations and observability: 5/5 PASS
- Security and privacy: 5/5 PASS
- Backward compatibility: 5/5 PASS

Graph Impact Context:
- Provider/artifact path: wrapper-generated local relationship graph at codebase-graph-impact-context.md; changed-file relationship edges inspected and source files validated: 5/5 PASS

Manual Test Evidence:
- Manual evidence reviewed: 5/5 PASS
- Not applicable for shell validation fixture.

QA Verification:
- QA verification completed: 5/5 PASS
- make ci: PASS

GitHub Completion Gate:
- GitHub completion verified: 5/5 PASS
- reviewDecision REVIEW_REQUIRED, unresolved threads 0.

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
  cp "${BMAD_PASS_REPORT}" "$output_file"
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
