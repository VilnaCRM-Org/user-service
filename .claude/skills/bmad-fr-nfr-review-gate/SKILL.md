---
name: bmad-fr-nfr-review-gate
description: Run a BMAD spec-driven post-implementation review gate. Use after implementing a GitHub PR, feature, bugfix, or task with BMAD specs to verify every FR/NFR, pinned NonFunctionals.com NFR category, expanded quality dimension, whole-codebase impact surface, manual test expectation, QA best practice, GitHub review comment, approval, and CI check before completion.
---

# BMAD FR/NFR Review Gate

Use this skill after implementation when a PR, feature, bugfix, or task has
BMAD specs under `specs/`. The gate checks whether the implementation
corresponds to every functional and non-functional requirement, verifies
expanded quality dimensions and related whole-codebase impact, then blocks
completion until all applicable rows score 5/5.

## Inputs

- BMAD spec bundle or file: `BMAD_REVIEW_SPEC_PATH=specs/my-bundle`
- Optional manual evidence: `BMAD_REVIEW_MANUAL_EVIDENCE=<path>`
- Optional PR number: `BMAD_REVIEW_PR=<number>`
- Optional base ref: `BMAD_REVIEW_BASE=<base-ref>`
- Graph impact context from Graphify/codebase-memory/Deptrac/manual notes:
  `BMAD_REVIEW_IMPACT_CONTEXT=<path>`
- Optional publishing toggles: `BMAD_REVIEW_POST_PR_COMMENT=true|false` and
  `BMAD_REVIEW_POST_GITHUB_STATUS=true|false` (BMAD wrapper defaults both to
  `true`)
- Optional status context: `BMAD_REVIEW_STATUS_CONTEXT='BMAD FR/NFR Review Gate'`
- Optional status self-filter override:
  `BMAD_REVIEW_STATUS_EXCLUDED_CONTEXT=<check-context>`; defaults to the final
  status context.

## Pinned NFR Catalog

The gate uses these NonFunctionals.com catalog categories:

- Performance
- Usability
- Maintainability
- Availability
- Interoperability
- Security
- Manageability
- Automatability
- Dependability

Do not add, remove, or rename categories during a review unless the skill is
being intentionally updated.

## Expanded Quality And Impact

The gate also requires an Expanded Quality Scorecard covering:

- Functional Suitability
- Performance Resource Sustainability
- Compatibility Coexistence
- Interaction Capability Accessibility
- Reliability Resilience
- Security Privacy Accountability
- Maintainability Testability
- Flexibility Portability
- Safety Harm Prevention
- Data Quality Integrity
- Operational Excellence Releaseability
- Observability Diagnosability
- Supply-Chain Integrity
- Compliance Governance
- Sustainability Resource Impact
- AI Automation Governance

The Whole-Codebase Impact Analysis must cover changed and related surfaces:
runtime paths, architecture/layer boundaries, domain model, persistence,
public API/schema, async events/queues, config/env, dependencies/lockfiles,
CI/workflows, tests/fixtures, docs, operations/observability,
security/privacy, and backward compatibility.

Graph/relationship evidence is required for whole-codebase impact scoring.
Graphify, codebase-memory MCP, Deptrac graph output, CodeQL, SCIP, or similar
tools can be supplied as impact context. If no context is supplied, the wrapper
generates a bounded local graph/relationship context from changed files and
direct symbol references; the reviewer still has to inspect related code rather
than relying only on changed files.

## Scoring Contract

| Score | Meaning                                                          |
| ----- | ---------------------------------------------------------------- |
| 1/5   | Requirement not addressed or evidence absent                     |
| 2/5   | Partial implementation with major gaps                           |
| 3/5   | Implemented but missing tests, evidence, or important edge cases |
| 4/5   | Implemented and mostly verified with minor unresolved risk       |
| 5/5   | Fully implemented, verified, traceable, and review-ready         |

PASS requires all applicable FRs, NFRs, NFR catalog categories, expanded
quality dimensions, whole-codebase impact surfaces, manual-test requirements,
QA checkpoints, GitHub completion checks, and CI checks to score 5/5. A
not-applicable row is allowed only with a concrete reason and source evidence.
Missing evidence fails closed.

## Workflow

1. Read the BMAD spec bundle: PRD, architecture, epics/stories, research, and
   implementation-readiness files when present.
2. Extract every FR, NFR, acceptance criterion, story requirement, and readiness
   requirement with source path evidence.
3. Run the gate:

   ```bash
   BMAD_REVIEW_SPEC_PATH=specs/my-bundle make bmad-fr-nfr-review-gate
   ```

4. If manual testing is required, record evidence in a markdown file and rerun:

   ```bash
   BMAD_REVIEW_SPEC_PATH=specs/my-bundle \
   BMAD_REVIEW_MANUAL_EVIDENCE=var/manual-test-evidence/<task>.md \
   make bmad-fr-nfr-review-gate
   ```

5. If the review reports `STATUS: FAIL`, apply fixes within the current PR
   scope, rerun `make ci`, then rerun the gate. When GitHub publishing is
   enabled, failed review iterations publish a failing commit status before the
   fix loop continues.
6. Fetch and address GitHub comments with `make pr-comments` when a PR exists.
7. Do not mark the PR/task complete until the gate reports `STATUS: PASS`,
   `make ci` passes, GitHub comments are resolved, required checks pass, and no
   requested-changes review remains.
8. For PR work, leave the final BMAD result visible on the PR through the
   generated PR comment and `BMAD FR/NFR Review Gate` commit status.

## Required PASS Markers

The review output must include:

```text
FR_NFR_SCORECARD: PASS
NFR_CATALOG_SCORECARD: PASS
EXPANDED_QUALITY_SCORECARD: PASS
WHOLE_CODEBASE_IMPACT: PASS
GRAPH_IMPACT_CONTEXT: PASS
MANUAL_TEST_EVIDENCE: PASS
QA_BEST_PRACTICES: PASS
GITHUB_COMPLETION_GATE: PASS
CI_GATE: PASS
```

The wrapper treats a `STATUS: PASS` without these markers as failure. In BMAD
mode, `STATUS: PASS` or `STATUS: FAIL` must also be the exact first line of the
review output. PASS also requires `EXPANDED_QUALITY_MIN_SCORE: 5/5` and
`IMPACT_ANALYSIS_MIN_SCORE: 5/5` evidence markers.

## GitHub Publishing

For BMAD wrapper runs, PR comment and commit-status publishing default to on.
Set `BMAD_REVIEW_POST_PR_COMMENT=false` or
`BMAD_REVIEW_POST_GITHUB_STATUS=false` only for dry runs or tests that must not
write to GitHub. The commit-status context defaults to
`BMAD FR/NFR Review Gate`; the loop ignores that same context while checking the
rest of the PR check rollup, so an earlier failed gate status does not block the
next remediation run from starting.

## Manual Evidence Format

Manual evidence must include:

- tester
- date
- scenario
- steps
- observed result
- linked artifacts or command output when available
- related FR/NFR IDs or NFR catalog categories

Do not fabricate manual evidence. If evidence is absent, leave the gate failing
and report the exact manual action required.

## Verification

Run focused checks for this skill change:

```bash
bash -n scripts/ai-review-loop.sh
bash -n scripts/bmad-fr-nfr-review-gate.sh
bats tests/CLI/bats/make_ai_review_loop_tests.bats
bats tests/CLI/bats/make_bmalph_tests.bats
git diff --check
```

For production code changes, also run:

```bash
make ci
```
