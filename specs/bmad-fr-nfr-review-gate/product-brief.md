---
workflowType: product-brief
project_name: BMAD FR/NFR Review Gate
author: Codex
date: 2026-05-17
revision: 1
---

# Product Brief: BMAD FR/NFR Review Gate

## Problem

BMAD specs can describe detailed functional and non-functional requirements,
but the existing post-implementation review loop only performs generic code
review. A PR can therefore pass generic review while still missing spec-level
FR/NFR evidence, manual test evidence, or an explicit quality assessment across
the full NFR catalog.

## Goal

Create a reusable post-implementation skill and automation path that verifies
implemented PRs, features, bugfixes, and other tasks against their BMAD specs
before completion.

## Users

- AI coding agents implementing BMAD-scoped work.
- Maintainers reviewing PR readiness.
- QA reviewers validating manual and automated evidence.

## Value

- Makes BMAD specs executable as a review contract.
- Creates a consistent 5/5 score gate for FRs and NFRs.
- Forces explicit evidence for manual-test-only behavior.
- Integrates with existing AI review, GitHub review, and CI workflows.

## Product Shape

The MVP is a tracked skill plus Bash wrapper around the existing AI review loop:

- `make bmad-fr-nfr-review-gate`
- `.claude/skills/bmad-fr-nfr-review-gate/SKILL.md`
- `.agents/skills/bmad-fr-nfr-review-gate/SKILL.md`
- BMAD-specific review/fix prompts
- Bats coverage for deterministic gate behavior

## Non-Goals

- Replacing human approval.
- Auto-merging PRs.
- Rewriting BMAD or generated `_bmad/` workflows.
- Generating fake manual evidence.
- Adding PHP runtime dependencies for the BMAD gate.

## Traceability Boundary

The BMAD gate implementation remains Bash, Markdown, Make, and Bats. A PR
completion run can still include adjacent remediation when external AI review
or CI finds a blocker in the same PR. Those changes must be explicitly traced
to their source, scope, and verification evidence; lockfile-only security
updates within existing `composer.json` constraints do not count as adding a
BMAD gate runtime dependency.
