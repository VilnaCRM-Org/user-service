# Skill Sweep

All `.claude/skills/*/SKILL.md` files were enumerated and opened for this
new-feature verification gate.

## Applicable Skills

| Skill                      | Result                                                                                                                                                                                                                                                                                                                                                                                       |
| -------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `bmad-autonomous-planning` | Applied. Bundle created under `specs/autonomous/20260607-154408-bmad-fr-nfr-reviewer-system-design-patterns/` with research, brief, PRD, architecture, epics, readiness, and summary artifacts.                                                                                                                                                                                              |
| `bmad-fr-nfr-review-gate`  | Applied. Skill requirements were read; local wrapper syntax and Bats verification passed. A pre-commit local dry run was attempted and failed closed because `HEAD` still matched `origin/main`, the worktree was dirty, and PR/GitHub corroboration was unavailable; this is expected before commit/PR. The live BMAD gate remains pending until final commit and PR context are available. |
| `clean-architecture-llm`   | Applied. The AI review workflow remains tooling/docs scoped; prompt policy is explicit; no Domain/Application/Infrastructure runtime LLM code or live model tests were added.                                                                                                                                                                                                                |
| `code-review`              | Applied. `make ai-review-loop` contract is preserved; review/fix prompts were expanded; Claude receives the repository policy while keeping `/review`. PR comment checks remain pending until a PR exists.                                                                                                                                                                                   |
| `code-organization`        | Applied. No new PHP classes or directories were introduced. Script/test/docs changes stay in existing locations.                                                                                                                                                                                                                                                                             |
| `documentation-sync`       | Applied. Updated `.claude/skills/code-review/SKILL.md` and `docs/onboarding.md` for the expanded reviewer behavior and Claude parity.                                                                                                                                                                                                                                                        |
| `testing-workflow`         | Applied. Added Bats coverage for review prompt criteria, applicability guardrails, NFR checklist retention, fix prompt scoping, stale-base regression, Claude policy parity, and gate-policy validation-support file classification. Focused Bats passed with 8 tests and the full Bats helper suite passed with 95 tests.                                                                   |
| `ci-workflow`              | Applied. Full isolated `make ci` passed on 2026-06-07 after the final Bats/doc updates, including unit, integration, Behat, OpenAPI, Schemathesis, Deptrac, Psalm, security, and Infection gates.                                                                                                                                                                                            |
| `quality-standards`        | Applicable through `make ci`; no thresholds or suppressions were changed.                                                                                                                                                                                                                                                                                                                    |

## Not Applicable

| Skill                           | Reason                                                                                                                                  |
| ------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------- |
| `api-platform-crud`             | No API Platform resource, DTO, endpoint, processor, serializer group, or CRUD behavior changed.                                         |
| `cache-management`              | No caching, cache keys, TTLs, invalidation, cache pools, or HTTP cache headers changed.                                                 |
| `complexity-management`         | No PHP complexity-bearing source code changed; shell change is minimal and covered by Bats. Full PHPInsights remains part of `make ci`. |
| `database-migrations`           | No entity, repository, Doctrine mapping, migration, schema, or index changed.                                                           |
| `deptrac-fixer`                 | No PHP layer dependency changed. `make deptrac` remains part of `make ci`.                                                              |
| `documentation-creation`        | Existing docs were updated; this was not initial documentation creation from scratch.                                                   |
| `implementing-ddd-architecture` | No Domain/Application/Infrastructure model, CQRS handler, repository, or domain event was added.                                        |
| `load-testing`                  | No API/runtime performance surface or K6 scenario changed.                                                                              |
| `observability-instrumentation` | No business metric, event subscriber, EMF logging, or observability runtime code changed.                                               |
| `openapi-development`           | No OpenAPI factory, processor, generated spec, endpoint schema, or Schemathesis behavior changed.                                       |
| `query-performance-analysis`    | No database query, endpoint, index, or persistence performance path changed.                                                            |
| `structurizr-architecture-sync` | No application architecture component, dependency relationship, runtime adapter, endpoint, entity, or C4-relevant component changed.    |

## Validation Evidence

- `bash -n scripts/ai-review-loop.sh`: passed.
- `bash -n scripts/bmad-fr-nfr-review-gate.sh`: passed.
- `git diff --check`: passed.
- `bats -f "code-review gate policy classifies|review prompt requires|review prompt prevents|review prompt retains|fix prompt constrains|claude agent receives|prefers origin base|local base branch before" tests/CLI/bats/make_ai_review_loop_tests.bats`: passed, 8 tests.
- `bats tests/CLI/bats/make_ai_review_loop_tests.bats tests/CLI/bats/make_bmalph_tests.bats`: passed, 95 tests.
- `make ci`: passed after the final Bats/doc updates.
- `make ai-review-loop`: passed after final `make ci` and fixer iterations; Codex reviewer reported `STATUS: PASS` and `0 issues`.
- `BMAD_REVIEW_SPEC_PATH=specs/autonomous/20260607-154408-bmad-fr-nfr-reviewer-system-design-patterns make bmad-fr-nfr-review-gate`: pre-commit dry run failed closed for expected no-commit/no-PR state; final PR-head run pending.
