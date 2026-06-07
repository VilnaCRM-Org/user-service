---
workflowType: manual-evidence
project_name: BMAD FR/NFR Reviewer System Design and Pattern Review
author: Codex
date: 2026-06-07
revision: 1
---

# Manual Evidence

## Scope

- Change type: local AI review loop, BMAD review prompt/fix prompt, Bats tests,
  skill documentation, and onboarding documentation.
- User-facing browser or API behavior: not applicable. This change does not add
  or modify Symfony routes, API Platform resources, GraphQL schema, persistence
  mappings, database migrations, or runtime domain behavior.
- Manual verification focus: CLI workflow behavior, prompt contract behavior,
  review-base resolution, and evidence that full repository quality gates still
  pass.

## Scenarios

| Scenario                    | Steps                                                                                           | Observed Result                                                                                                                                                                                                                                                                                                            |
| --------------------------- | ----------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Review prompt scope         | Inspect `scripts/ai-review-prompts/review.md`.                                                  | Prompt now requires review of functional requirements, non-functional requirements, system design, design patterns, code smells, software engineering best practices, and repository architecture while preserving first-line `STATUS: PASS` or `STATUS: FAIL`.                                                            |
| Fix prompt scope            | Inspect `scripts/ai-review-prompts/fix.md`.                                                     | Prompt constrains remediation to the smallest coherent PR-scoped fix and tells the fixer not to add design patterns only to satisfy labels.                                                                                                                                                                                |
| Remote base precedence      | Run focused Bats regression for stale local `main` versus `origin/main`.                        | Review prompt captures `refs/remotes/origin/main`, preventing stale local branches from widening review scope to unrelated historical diffs.                                                                                                                                                                               |
| Claude policy compatibility | Run focused Bats regression for Claude review criteria.                                         | Claude default `/review` remains available while appended policy text carries the repository-specific FR/NFR, system design, pattern, smell, and engineering-practice criteria.                                                                                                                                            |
| Local AI review             | Run `make ai-review-loop` with `AI_REVIEW_BASE=main` and isolated port variables after full CI. | Codex reviewer initially required gate-policy scoping fixes for validation-support files, the loop applied them, reran full CI, and then returned `STATUS: PASS` with `0 issues`.                                                                                                                                          |
| Full CI                     | Run `make ci` with isolated Docker Compose project and port variables.                          | CI completed successfully with the final success banner. Unit tests passed with 2258 tests and 6208 assertions, integration tests passed with 120 tests and 721 assertions, Behat passed with 644 scenarios and 3622 steps, OpenAPI/Schemathesis found no issues, and Infection killed 4543 of 4543 mutants with 100% MSI. |

## Verification Commands

```bash
bash -n scripts/ai-review-loop.sh
git diff --check
bats -f "code-review gate policy classifies|review prompt requires|review prompt prevents|review prompt retains|fix prompt constrains|claude agent receives|prefers origin base|local base branch before" tests/CLI/bats/make_ai_review_loop_tests.bats
bats tests/CLI/bats/make_ai_review_loop_tests.bats tests/CLI/bats/make_bmalph_tests.bats
make ci COMPOSE_PROJECT_NAME=user-service-bmad-design-review MONGODB_PORT=47217 REDIS_PORT=47279 LOCALSTACK_PORT=47566 MAILCATCHER_SMTP_PORT=47125 MAILCATCHER_HTTP_PORT=47180 PHP_DEV_PORT=47081 HTTP_PORT=47080 HTTPS_PORT=47443 HTTP3_PORT=47444 SCHEMATHESIS_API_PORT=47082 SCHEMATHESIS_BASE_URL=http://localhost:47082 STRUCTURIZR_PORT=47085
AI_REVIEW_VERIFY_CMD='make ci COMPOSE_PROJECT_NAME=user-service-bmad-design-review MONGODB_PORT=47217 REDIS_PORT=47279 LOCALSTACK_PORT=47566 MAILCATCHER_SMTP_PORT=47125 MAILCATCHER_HTTP_PORT=47180 PHP_DEV_PORT=47081 HTTP_PORT=47080 HTTPS_PORT=47443 HTTP3_PORT=47444 SCHEMATHESIS_API_PORT=47082 SCHEMATHESIS_BASE_URL=http://localhost:47082 STRUCTURIZR_PORT=47085' make ai-review-loop AI_REVIEW_BASE=main AI_REVIEW_VERIFY_ON_PASS=false COMPOSE_PROJECT_NAME=user-service-bmad-design-review MONGODB_PORT=47217 REDIS_PORT=47279 LOCALSTACK_PORT=47566 MAILCATCHER_SMTP_PORT=47125 MAILCATCHER_HTTP_PORT=47180 PHP_DEV_PORT=47081 HTTP_PORT=47080 HTTPS_PORT=47443 HTTP3_PORT=47444 SCHEMATHESIS_API_PORT=47082 SCHEMATHESIS_BASE_URL=http://localhost:47082 STRUCTURIZR_PORT=47085
```

## Outcomes

- `bash -n scripts/ai-review-loop.sh`: PASS.
- Focused Bats regression set: PASS, 8 tests.
- Full Bats helper suite: PASS, 95 tests.
- `git diff --check`: PASS after final evidence updates.
- `make ci`: PASS, including unit tests (2258 tests, 6208 assertions),
  integration tests (120 tests, 721 assertions), Behat (644 scenarios, 3622
  steps), OpenAPI validation, Schemathesis examples/coverage/fuzzing, Deptrac,
  Psalm, security checks, and Infection at 100% MSI with 4543 of 4543 mutants
  killed.
- `make ai-review-loop`: PASS after final CI and fixer iterations; Codex
  reviewer reported `STATUS: PASS` and `0 issues`.

## Manual-Only Checks

- Browser/device testing: not applicable because no web UI or API behavior was
  added or modified.
- Database migration testing: not applicable because no persistence schema or
  Doctrine mapping changed.
- Load testing: not applicable because the changed runtime path is a local
  developer review workflow and prompt/test documentation; it does not affect
  request handling, persistence, queues, or external service calls.
