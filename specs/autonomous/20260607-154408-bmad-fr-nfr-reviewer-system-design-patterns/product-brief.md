# Product Brief: BMAD/AI FR/NFR Reviewer Design Coverage

## Problem

The local BMAD/AI review loop checks code quality and requirements, but it needs
a clearer product contract for reviewing system design, software engineering
best practices, design-pattern fit, and code smells. Without explicit guidance,
AI review feedback can miss material design risks or produce noisy, over-broad
refactoring suggestions.

## Goals

- Expand the reviewer to assess FR/NFR coverage, system design tradeoffs,
  design patterns, and code smells.
- Keep feedback concrete, scoped to changed code, and aligned with Google
  Engineering Practices.
- Preserve the existing `STATUS: PASS` / `STATUS: FAIL` parser contract.
- Ensure the fixer handles design and smell findings with the smallest coherent
  scoped change.
- Sync documentation so contributors understand the AI review loop's expanded
  role.

## Non-Goals

- Do not replace human review, CodeRabbit, or CI.
- Do not require heavyweight system design review for trivial changes.
- Do not introduce design patterns for their own sake.
- Do not change the `scripts/ai-review-loop.sh` parser or status contract.
- Do not weaken repository DDD, CQRS, Hexagonal Architecture, or Domain-purity
  rules.

## Users

- Backend contributors preparing PRs.
- Maintainers reviewing architectural and maintainability risks.
- AI review/fix agents running through `make ai-review-loop`.
- New contributors learning the repository workflow through onboarding docs.

## Assumptions

- Repository rules override external references.
- System Design Primer and Refactoring Guru inform review criteria, but feedback
  must remain repo-specific.
- The implementation surface is primarily prompt, test, review-loop plumbing,
  and documentation updates.
- The loop remains prompt-driven, Codex-default, and compatible with multiple AI
  agents. Claude parity may require passing the repository prompt into its
  `/review` invocation.

## Scope

- Update `scripts/ai-review-prompts/review.md`.
- Update `scripts/ai-review-prompts/fix.md`.
- Update `scripts/ai-review-loop.sh` only enough to give every supported local
  reviewer the same repository review policy.
- Add or update Bats coverage for prompt criteria.
- Sync `.claude/skills/code-review/SKILL.md`.
- Sync `docs/onboarding.md`.

## Acceptance Criteria

1. The reviewer prompt explicitly checks FR/NFR coverage, system design,
   design-pattern fit, code smells, software engineering best practices, and
   repository architecture.
2. The reviewer keeps the exact first-line `STATUS: PASS` or `STATUS: FAIL`
   contract.
3. The reviewer includes an applicability rule to avoid noisy findings on
   trivial or unrelated changes.
4. The fixer prompt requires scoped fixes and rejects unrelated refactoring.
5. Tests prove the review and fix prompts retain the expanded criteria and that
   supported local reviewers receive the same repository criteria.
6. Documentation explains that `make ai-review-loop` includes design and
   maintainability checks.

## Risks

- Broader review criteria may create false positives.
- The fixer may over-refactor if findings are vague.
- External pattern guidance could conflict with local architecture unless repo
  rules stay authoritative.
- Prompt growth could reduce reviewer focus if not kept concise.
