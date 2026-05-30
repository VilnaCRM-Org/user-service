---
workflowType: comparison-report
project_name: BMAD FR/NFR Review Gate
author: Codex
date: 2026-05-30
revision: 1
---

# Comparison Report: Expanded BMAD Reviewer

## Purpose

Compare the previous BMAD FR/NFR reviewer prompt with the expanded reviewer
that adds broader quality dimensions and whole-codebase impact analysis.

Primary test target: PR #286, `feat: add passkey authentication`, head
`c889013e4402ab30060b2bb9dd6cb968fe96783c`.

Comparison artifact directory:
`/home/kravtsov/tmp/bmad-review-compare/pr286-20260530_215213`

## Compared Versions

| Version  | Prompt artifact                                                      | Runner behavior                                             |
| -------- | -------------------------------------------------------------------- | ----------------------------------------------------------- |
| Baseline | `baseline-prompt.md`, copied from PR #287 head before this expansion | Original 9 NFR categories only                              |
| Expanded | `new-prompt.md`, current expanded reviewer prompt from this PR       | 9 NFR categories, 16 quality dimensions, 14 impact surfaces |

Both runs used the same PR head, BMAD spec path, manual evidence path, score
threshold, Codex reviewer, read-only fix sandbox, and no PR comment/status
publishing.

## Result Summary

| Metric                         | Baseline reviewer                           | Expanded reviewer |
| ------------------------------ | ------------------------------------------- | ----------------- |
| Final model review status      | FAIL                                        | PASS              |
| Product/code blockers found    | 0                                           | 0                 |
| Environmental/GitHub blockers  | 1                                           | 0                 |
| New product/code blockers      | n/a                                         | 0                 |
| New false-positive blockers    | n/a                                         | 0                 |
| NFR categories scored          | 9                                           | 9                 |
| Expanded quality dimensions    | 0                                           | 16                |
| Whole-codebase impact surfaces | 0                                           | 14                |
| Required fixes                 | Refresh live GitHub required-check evidence | None              |

## Baseline Findings

Baseline report:
`/home/kravtsov/tmp/bmad-review-compare/pr286-20260530_215213/baseline/review-latest.md`

The baseline reviewer scored the passkey implementation, NFR catalog, manual
evidence, and QA verification as `5/5 PASS`.

It found one blocker:

- Live required-check/branch-protection configuration for PR #286 could not be
  verified because `gh` had intermittent `api.github.com` connectivity errors.
  The connector verified PR state, approvals, statuses, and workflow runs, but
  the baseline reviewer still failed closed on the missing required-check
  configuration.

This was not a product/code defect.

## Expanded Findings

Expanded report:
`/home/kravtsov/tmp/bmad-review-compare/pr286-20260530_215213/new/review-codex-iter1-20260530_220754.md`

The expanded reviewer found no required fixes.

It explicitly reviewed and scored:

- the original 9 NonFunctionals.com categories;
- 16 expanded quality dimensions: functional suitability, resource
  sustainability, compatibility, interaction/accessibility, resilience,
  privacy/accountability, testability, flexibility/portability, safety, data
  quality, releaseability, observability, supply-chain integrity, compliance,
  sustainability, and AI automation governance;
- 14 whole-codebase impact surfaces: runtime paths, architecture/layers, domain
  model, persistence/database, API/schema, async events, config/env,
  dependencies, CI/workflows, tests, docs, operations, security/privacy, and
  backward compatibility.

The expanded reviewer used the generated impact context plus manual repository
relationship checks because Graphify/codebase-memory/CodeQL/SCIP artifacts were
not present. It inspected related passkey runtime paths, rate-limit resolver
changes, public contracts, docs, CI workflows, dependency/lockfile scope, and
operations evidence instead of limiting the review to changed-file names.

## Gate Robustness Finding

The expanded reviewer returned `STATUS: PASS`, but initially used bold Markdown
section headings such as `**Requirement Scorecard:**`. The runner rejected that
PASS because section parsing only accepted plain headings.

Fix implemented in this PR:

- `scripts/ai-review-loop.sh` now accepts bold Markdown scorecard headings.
- `tests/CLI/bats/make_ai_review_loop_tests.bats` includes a regression test:
  `ai-review-loop accepts bold markdown scorecard headings`.

The saved expanded report validates successfully after that parser fix.

## Knowledge Graph Investigation

Graphify is useful as optional context but should not be a hard PASS/FAIL
dependency yet. Recommended layering:

1. Use deterministic repo-native context first: changed files, `rg`, specs,
   tests, docs, CI workflows, dependency metadata, and Deptrac architecture
   rules.
2. Accept optional graph output through `BMAD_REVIEW_IMPACT_CONTEXT`.
3. Use Graphify, codebase-memory MCP, Deptrac graph output, CodeQL, SCIP, or
   comparable tools as supporting relationship evidence when available.
4. Fail only when the reviewer cannot inspect or explicitly rule out affected
   codebase surfaces, not when a specific graph tool is absent.

## Conclusion

The expanded reviewer did not find new code defects in PR #286 because the PR
was already hardened by prior review rounds. The improvement is still material:
it increases required review coverage from 9 NFR rows to 39 scored quality and
impact rows, forces whole-codebase impact analysis, supports optional knowledge
graph context, and caught a real runner robustness issue around Markdown
section parsing.
