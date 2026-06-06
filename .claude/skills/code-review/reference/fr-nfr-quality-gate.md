# Strict BMAD FR/NFR Quality Gate

Use this protocol for every PR review. The reviewer is responsible for finding
missing behavior, missing test coverage, weak CI evidence, flaky tests,
security issues, and quality-attribute regressions before declaring a PR clean.

## Inputs To Load

Collect current-state evidence before reviewing:

- PR head SHA, base branch, diff, changed files, PR description, linked issue, reviewer comments, and check rollup.
- Product docs and planning artifacts: PRD, stories, epics, architecture, implementation readiness, run summaries, NFR evidence, manual evidence, and API docs.
- Existing tests across unit, integration, E2E/Behat, contract/API, mutation/Infection, performance/load, smoke, browser/manual bridge, and generated-spec validation.
- CI workflows and required checks that prove the changed behavior in automation.
- Current PR check status for the exact head SHA. If branch protection reports no required contexts, enforce the expected check allowlist in this guide anyway.
- Current-head codebase graph evidence (`graphify-out`, graph queries, dependency maps, architecture diagrams, or equivalent). Search for graph artifacts and graph tooling before reviewing. If graph evidence is missing, mark `GRAPH_IMPACT_CONTEXT: MISSING` and compensate with explicit current-head code-search impact evidence. If graph evidence is stale for the reviewed SHA, mark it stale, fail the graph context, and replace it with a current graph run or explicit current-head code-search impact evidence.

## Mandatory Review Flow

1. Post `BMAD FR/NFR Review Gate` as `pending` on the current PR head SHA when a GitHub PR exists.
2. Extract all FRs and NFRs from the loaded inputs. Preserve IDs when present. Add inferred changed-behavior requirements only when evidence from diff/docs supports them.
3. For every FR and NFR, generate a test matrix with these case types:
   - Positive: expected valid flow and boundary-valid variants.
   - Negative: invalid input, unauthorized/forbidden state, disabled feature flag, malformed payload, missing dependency, failed external service, and conflict/idempotency cases.
   - Edge: null/empty/min/max, duplicate/replay, concurrency, ordering, timeout, stale state, unusual content type, unusual method, localization/encoding, and backwards-compatibility cases.
   - Regression: cases implied by prior bug fixes, reviewer comments, mutation survivors, or generated spec drift.
   - NFR-specific: security, availability, reliability, performance, operability, observability, compatibility, scalability, maintainability, and data protection cases.
4. Map each generated case to evidence:
   - Automated tests: file path, test method/scenario, assertion type.
   - CI check: workflow/job name and what it proves.
   - Mutation/coverage: MSI, covered MSI, coverage target, or targeted mutation evidence.
   - Contract/spec: OpenAPI, GraphQL SDL, Schemathesis, backwards-compatibility checks.
   - Manual evidence: record separately as supporting context only. It cannot satisfy `AUTO_TEST_COVERAGE` or `CI_COVERAGE`. If automation is impossible or deliberately deferred, mark the case WARN/FAIL with risk, owner, and follow-up.
5. Verify the expected current-head CI checks. Do not infer pass from branch protection alone.
6. Mark uncovered applicable cases as blocking review findings. Suggest or implement tests before passing the gate.
7. Review all changed and adjacent tests for flaky-test risk:
   - Random data not isolated or not seeded through project helpers.
   - Time, sleeps, eventual consistency, external network, order dependency, shared mutable state, rate-limit leakage, non-unique identifiers, or weak assertions.
   - CI-only behavior, environment-variable dependence, locale/timezone dependence, and test pollution between suites.
8. Review whole-codebase impact using graph evidence and code search:
   - Entry points, listeners, controllers, resolvers, services, handlers, config, generated specs, docs, migrations, fixtures, CI jobs, and observability.
   - Downstream callers and indirect paths affected by changed behavior.
   - Graph discovery is mandatory. `GRAPH_IMPACT_CONTEXT: MISSING` is allowed
     only as an explicit failing marker with compensating current-head code
     search evidence.
9. Score system quality attributes. Every listed attribute must appear in the report with a numeric `0-5` score or `N/A`. N/A requires a one-line reason. Every score below `5` requires either a concrete improvement suggestion or evidence that no practical PR-scope improvement exists.
10. Re-run the gate after fixes. Continue until no actionable findings remain.
11. Final GitHub status:
    - `success`: strict gate and expected automated checks passed for the current SHA.
    - `failure`: actionable gate findings remain.
    - `pending`: review or required remote checks are still running.

Human/code-owner approvals are not BMAD gate inputs. Do not request, wait for,
or require human approvals before posting the BMAD result; report approval state
only as a separate repository merge-policy fact.

## Expected Current-Head PR Checks

The reviewer must inspect check runs/statuses for the exact reviewed SHA, for
example:

```bash
gh pr view "$PR_NUMBER" --repo "$OWNER/$REPO" --json headRefOid,statusCheckRollup
```

The BMAD gate stays `pending` or `failure` until the expected current-head check
set is complete and successful. Treat missing or pending checks as
`PENDING_REMOTE` in the report. This applies even when branch protection has an
empty required-check list.

Expected checks for this repository include: PHPUnit, Behat, K6, Infection,
Schemathesis, Spectral Lint, `Openapi-diff@GraphQL spec backward comparability`,
`openapi-diff@openapi-diff`, Psalm, PHP Insights checks, Deptrac, lint,
symfony-checks, Run Bats Core Tests, Cache Integration Tests, Memory leak tests,
test-and-report, qlty check, qlty fmt, CodeRabbit, and security/snyk (Kravalg).
If a check is intentionally absent for a PR, record the reason and the
workflow/config evidence proving why it is not applicable. Use
`check-name@workflow-name` when two expected checks differ only by casing or
similar names; every current-head check with that expected name/workflow pair
must complete successfully unless the report identifies the exact check/workflow
and workflow/config evidence proving it is not applicable.

## System Quality Attributes To Score

Source list: <https://en.wikipedia.org/wiki/List_of_system_quality_attributes>.

Score every attribute below as `0-5` or `N/A`. Group low-signal N/A rows in
the final report only when each grouped attribute has the same reason.

accessibility, accountability, accuracy, adaptability, administrability,
affordability, agility, analyzability, auditability, autonomy, availability,
compatibility, composability, confidentiality, configurability, convenience,
correctness, credibility, customizability, debuggability, degradability,
determinability, demonstrability, dependability, deployability, discoverability,
distributability, durability, effectiveness, efficiency, elasticity,
evolvability, extensibility, failure transparency, familiarity, fault-tolerance,
fidelity, flexibility, inspectability, installability, integrity, interactivity,
interchangeability, interoperability, intuitiveness, learnability,
localizability, maintainability, manageability, mobility, modifiability,
modularity, observability, operability, orthogonality, portability, precision,
predictability, process capabilities, producibility, provability,
recoverability, redundancy, relevance, reliability, repairability,
repeatability, reproducibility, resilience, responsiveness, reusability,
robustness, safety, scalability, seamlessness, self-sustainability,
serviceability/supportability, securability, simplicity, stability, standards
compliance, survivability, sustainability, tailorability, testability,
timeliness, traceability, transparency, ubiquity, understandability,
upgradability, usability, vulnerability.

## Numeric Scoring Rules

- `5`: evidence proves the PR preserves or improves the attribute for changed behavior, automated coverage exists where applicable, and no practical PR-scope improvement remains.
- `4`: acceptable for merge, but a practical improvement exists. Include a concrete improvement suggestion or implemented fix.
- `3`: partial or indirect evidence, weak automation, or meaningful maintainability/operability/security concern. Treat as a gate failure until fixed or explicitly accepted as a blocker/follow-up.
- `2`: likely defect, vulnerability, missing test, missing CI gate, broken contract, or operational risk. Gate failure.
- `1`: severe gap with high risk. Gate failure.
- `0`: broken, unreviewable, or evidence contradicts the requirement. Gate failure.
- `N/A`: attribute does not apply to this PR's changed behavior; provide a reason.

The aggregate `SYSTEM_QUALITY_ATTRIBUTES_SCORECARD` marker is `PASS` only when
all applicable attributes are `4` or `5`, every `4` has a concrete improvement
suggestion or implemented fix, security-related attributes meet the stricter
security rule below, and every `N/A` has a reason. Manual-only evidence for an
applicable attribute caps the score at `3`.

Critical attributes for most backend/API PRs cannot be N/A without explicit
reason: correctness, security/securability, confidentiality, integrity,
availability, reliability, resilience, performance/efficiency/responsiveness,
interoperability, maintainability, observability, operability, testability,
traceability, standards compliance, compatibility, scalability, and
deployability.

Security-related attributes are confidentiality, integrity, securability,
vulnerability, safety, accountability, auditability, availability, survivability,
and privacy/data-protection equivalents inferred from the PR. A score below `5`
for any applicable security-related attribute is a blocker unless the weakness
is outside PR scope and is explicitly tracked with owner and risk.

## Minimum Report Sections

Use these exact markers so automation and humans can evaluate the gate:

```text
STATUS: PASS|FAIL
FR_NFR_SCORECARD: PASS|FAIL
TEST_CASE_MATRIX: PASS|FAIL
AUTO_TEST_COVERAGE: PASS|FAIL
CI_COVERAGE: PASS|FAIL|PENDING_REMOTE
FLAKY_TEST_RISK: PASS|FAIL
SYSTEM_QUALITY_ATTRIBUTES_SCORECARD: PASS|FAIL
WHOLE_CODEBASE_IMPACT: PASS|FAIL
GRAPH_IMPACT_CONTEXT: PASS|MISSING|FAIL
GITHUB_COMPLETION_GATE: PASS|FAIL|PENDING_REMOTE_CI
```

Include these exact report headings and table headers:

- `Reviewed SHA and PR URL:`
- `FR/NFR counts: FRs: <n>; NFRs: <n>; generated cases: <n>; covered cases: <n>; uncovered cases: <n>; findings fixed: <n>.`
- `Findings table:` followed by `| Severity | File/Line | Requirement/NFR | Evidence | Fix | Verification |`
- `Test evidence table:` followed by `| Suite/Command | Evidence | Result |`
- `Current-head GitHub check summary:` followed by `| Check | State | Evidence |`
- `Flaky-test risk review:`
- `System quality attribute scorecard:` followed by `| Attribute | Score | Evidence | Improvement/Fix | Blocker |`
  - Include explicit rows for critical/security attributes and this mandatory
    grouped row so automation can verify every remaining attribute was
    considered:
    `| all remaining listed attributes | N/A | <reason> | <fix> | <blocker> |`.
- `Graph/code-search whole-codebase impact evidence:`
- Remaining external blockers, separated from local BMAD gate results.

## Pass/Fail Policy

Fail the gate when any of these are true:

- An applicable FR or NFR has no automated or CI-backed evidence.
- Positive/negative/edge/security/performance cases are missing for changed behavior.
- Mutation, coverage, contract, generated spec, or load evidence is required by the NFR but missing.
- A changed test is plausibly flaky and not fixed.
- A critical system quality attribute scores below `4`.
- An applicable security-related quality attribute scores below `5` without an explicit out-of-scope tracked blocker.
- Any quality attribute scores below `5` and the report omits a concrete improvement suggestion or evidence that no practical PR-scope improvement exists.
- Current-diff review comments, current-diff conversations, or required CI failures remain unresolved. Outdated unresolved comments are historical/non-blocking unless independently revalidated against current code.
- Expected current-head PR checks are missing, pending, cancelled, skipped without a documented reason, or failing.
- The report was generated for a stale SHA.
- Graph impact context is missing, stale for the reviewed SHA, references deleted files, uses old graph counts, or is not replaced by current-head code-search impact evidence.
