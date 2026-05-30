---
workflowType: prd
project_name: BMAD FR/NFR Review Gate
author: Codex
date: 2026-05-17
revision: 1
---

# PRD: BMAD FR/NFR Review Gate

## Requirements

| ID    | Requirement                                                                                                                                                                                                                                        | Priority |
| ----- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------- |
| FR-01 | A developer can run a BMAD review gate against a spec bundle or spec file.                                                                                                                                                                         | P0       |
| FR-02 | The gate passes the spec path, manual evidence path, PR number, base ref, score threshold, NFR categories, expanded quality dimensions, and graph-backed whole-codebase impact context into the review prompt.                                     | P0       |
| FR-03 | The reviewer must extract every FR, NFR, acceptance criterion, story requirement, and implementation-readiness requirement from the BMAD source.                                                                                                   | P0       |
| FR-04 | The reviewer must score every applicable requirement from 1 to 5.                                                                                                                                                                                  | P0       |
| FR-05 | The gate requires 5/5 for every applicable FR, NFR, NFR catalog category, expanded quality dimension, graph-backed whole-codebase impact surface, manual-test requirement, QA checkpoint, GitHub gate, and CI gate.                                | P0       |
| FR-06 | The gate allows not-applicable decisions only with concrete reason and source evidence.                                                                                                                                                            | P0       |
| FR-07 | The gate treats a PASS without required scorecard markers as failure.                                                                                                                                                                              | P0       |
| FR-08 | The gate can run verification after a PASS review before exiting successfully.                                                                                                                                                                     | P0       |
| FR-09 | The gate reuses the existing AI reviewer/fixer loop instead of creating a parallel agent runner.                                                                                                                                                   | P0       |
| FR-10 | BMAD mode forces the 5/5 threshold, pinned NFR categories, and full required marker list even when generic `AI_REVIEW_*` variables are set.                                                                                                        | P0       |
| FR-11 | The workflow is discoverable through Make help and AI skill documentation.                                                                                                                                                                         | P1       |
| FR-12 | BMAD mode can publish a concise final PASS/FAIL review summary as a GitHub PR comment.                                                                                                                                                             | P0       |
| FR-13 | BMAD mode can publish a GitHub-visible commit status for the review gate, including failure during remediation and success after final PASS.                                                                                                       | P0       |
| FR-14 | A PR completion run records scoped external AI review fixes and CI/security remediation that were required to make the PR review-ready.                                                                                                            | P0       |
| FR-15 | The reviewer must analyze related whole-codebase impact surfaces, not only changed files.                                                                                                                                                          | P0       |
| FR-16 | The reviewer must apply expanded quality lenses beyond the base NonFunctionals.com catalog, including ISO 25010, data quality, privacy, operational excellence, supply-chain, sustainability, and automation-governance concerns where applicable. | P0       |
| FR-17 | The gate requires graph/relationship impact evidence for whole-codebase scoring and can use Graphify, codebase-memory, Deptrac, or an equivalent generated local relationship graph without making one external graph product mandatory.           | P0       |

## Non-Functional Requirements

| ID     | Category         | Requirement                                                                                                                                                |
| ------ | ---------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------- |
| NFR-01 | Maintainability  | Keep the BMAD gate implementation in tracked Bash, Markdown, Make, and Bats files with no PHP runtime dependency added for the gate.                       |
| NFR-02 | Automatability   | The target must support non-interactive execution with deterministic exit code semantics from the underlying review loop.                                  |
| NFR-03 | Dependability    | The gate must fail closed on missing specs, missing evidence, missing markers, failed verification, or unverified GitHub/CI state.                         |
| NFR-04 | Security         | Prompts and reports must not request or print secrets, tokens, private keys, or sensitive environment values.                                              |
| NFR-05 | Manageability    | Operators can configure spec path, manual evidence, PR number, base ref, agents, log dir, max iterations, verify command, and GitHub result publishing.    |
| NFR-06 | Interoperability | The gate supports existing Codex/Claude review adapters, BMAD markdown specs, GitHub CLI context, and `make ci`.                                           |
| NFR-07 | Usability        | Skill docs include a minimal command, optional inputs, PASS markers, and manual evidence format.                                                           |
| NFR-08 | Performance      | The wrapper adds minimal local work before invoking the existing AI review loop.                                                                           |
| NFR-09 | Availability     | If remote GitHub/CI data or the applicable PR check rollup cannot be verified, the review must not pass.                                                   |
| NFR-10 | Analysability    | The reviewer receives mandatory graph/relationship context so it can trace affected callers, contracts, data paths, tests, docs, and operational surfaces. |
| NFR-11 | Portability      | Whole-codebase impact analysis works without a mandatory external graph product because the wrapper can generate a bounded local relationship graph.       |

## NFR Catalog

Every review must explicitly evaluate these NonFunctionals.com categories:
Performance, Usability, Maintainability, Availability, Interoperability,
Security, Manageability, Automatability, and Dependability.

Every review must also explicitly evaluate these expanded quality dimensions:
Functional Suitability, Performance Resource Sustainability, Compatibility
Coexistence, Interaction Capability Accessibility, Reliability Resilience,
Security Privacy Accountability, Maintainability Testability, Flexibility
Portability, Safety Harm Prevention, Data Quality Integrity, Operational
Excellence Releaseability, Observability Diagnosability, Supply-Chain
Integrity, Compliance Governance, Sustainability Resource Impact, and AI
Automation Governance.

Every review must explicitly evaluate these whole-codebase impact surfaces:
Runtime paths, Architecture and layer boundaries, Domain model, Persistence and
database, Public API and schema, Async events and queues, Configuration and
environment, Dependencies and lockfiles, CI and workflows, Tests and fixtures,
Documentation, Operations and observability, Security and privacy, and Backward
compatibility.

## Acceptance Criteria

| ID    | Criteria                                                                                                                                                                                                                     |
| ----- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AC-01 | `make help` lists `bmad-fr-nfr-review-gate`.                                                                                                                                                                                 |
| AC-02 | Running the wrapper without a spec path fails with a helpful error.                                                                                                                                                          |
| AC-03 | Missing spec or manual evidence paths fail before invoking an AI agent.                                                                                                                                                      |
| AC-04 | The BMAD review prompt receives substituted spec, manual evidence, PR, base ref, threshold, category, expanded-quality, impact-surface, status, and graph-impact-context values.                                             |
| AC-05 | A fake reviewer PASS with all required markers, including `EXPANDED_QUALITY_SCORECARD: PASS`, `WHOLE_CODEBASE_IMPACT: PASS`, `GRAPH_IMPACT_CONTEXT: PASS`, and `CI_GATE: PASS`, exits successfully when verification passes. |
| AC-06 | A fake reviewer PASS without required markers is treated as failure.                                                                                                                                                         |
| AC-07 | BMAD wrapper/Make execution cannot be downgraded by generic `AI_REVIEW_SCORE_THRESHOLD`, `AI_REVIEW_NFR_CATEGORIES`, or `AI_REVIEW_REQUIRED_GATE_MARKERS` values.                                                            |
| AC-08 | BMAD mode rejects `STATUS: PASS` unless it is the exact first output line.                                                                                                                                                   |
| AC-09 | Documentation explains the 5/5 rule, manual evidence format, and pinned NFR catalog.                                                                                                                                         |
| AC-10 | The Codex and Claude skills route agents to the new Make target.                                                                                                                                                             |
| AC-11 | If GitHub reports no required checks for the PR branch, the CI gate verifies every visible current PR check instead of failing solely because `--required` is empty.                                                         |
| AC-12 | A final BMAD PASS or terminal FAIL can post a PR comment containing the result, commit, status context, and bounded review/verification excerpts.                                                                            |
| AC-13 | BMAD review execution can publish a pending status at start, a failure status when the reviewer finds fixable issues, and a success status after verified PASS.                                                              |
| AC-14 | The BMAD status context is excluded from PR check corroboration so a previous failed gate status does not prevent the next remediation run from starting.                                                                    |
| AC-15 | Any adjacent application-code or dependency-lock remediation in the PR has explicit source, reason, verification, and scope evidence in manual evidence.                                                                     |
| AC-16 | A BMAD PASS without expanded-quality score evidence is rejected.                                                                                                                                                             |
| AC-17 | A BMAD PASS without whole-codebase impact score evidence is rejected.                                                                                                                                                        |
| AC-18 | When no impact context is supplied, the wrapper creates a bounded local graph/relationship context handoff file for the reviewer.                                                                                            |

## Scoring Rubric

| Score | Meaning                                                          |
| ----- | ---------------------------------------------------------------- |
| 1/5   | Requirement not addressed or evidence absent                     |
| 2/5   | Partial implementation with major gaps                           |
| 3/5   | Implemented but missing tests, evidence, or important edge cases |
| 4/5   | Implemented and mostly verified with minor unresolved risk       |
| 5/5   | Fully implemented, verified, traceable, and review-ready         |
