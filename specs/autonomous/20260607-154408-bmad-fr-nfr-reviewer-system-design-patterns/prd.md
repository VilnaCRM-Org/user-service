# PRD: BMAD/AI FR/NFR Reviewer Design Coverage

## Overview

Improve the local BMAD/AI review loop so it reviews changed code for FR/NFR
coverage, system design quality, design-pattern fit, software engineering best
practices, and code smells. The feature is prompt-driven and must preserve the
existing `STATUS: PASS` / `STATUS: FAIL` parser contract.

Assumptions:

- Repository architecture rules remain authoritative.
- System Design Primer and Refactoring Guru inform review criteria only.
- Implementation is limited to prompts, prompt tests, documentation sync, and
  review-loop plumbing needed to pass the same policy to supported local review
  agents.

## Functional Requirements

1. The review prompt must explicitly assess functional requirement coverage
   against PR/spec/story intent.
2. The review prompt must assess applicable NFRs: security, performance,
   reliability, observability, maintainability, backwards compatibility, and
   testability.
3. The review prompt must assess system design only when relevant to changed
   behavior, data flow, storage, integration, scaling, or reliability.
4. System design review must cover constraints, assumptions, bottlenecks,
   latency/throughput, availability/consistency, caching, async processing, back
   pressure, failure modes, and operational visibility.
5. The review prompt must assess design-pattern fit, including appropriate use
   or overuse of patterns such as Adapter, Strategy, Factory, Decorator,
   Command, Observer, Facade, Proxy, Pipeline, and Ports and Adapters.
6. The review prompt must assess code smells, including bloaters, OO abusers,
   change preventers, dispensables, and couplers.
7. The review prompt must enforce repository architecture: DDD, CQRS, Hexagonal
   Architecture, and framework-free Domain.
8. The fixer prompt must handle FR/NFR, system design, design pattern, and
   code-smell findings with the smallest coherent scoped change.
9. Tests must prove the review and fix prompts retain the expanded criteria.
10. Documentation must explain that `make ai-review-loop` includes design and
    maintainability checks.
11. Claude review execution must receive the same repository review criteria
    while preserving its built-in `/review` path.

## Non-Functional Requirements

1. Preserve exact first-line review output: `STATUS: PASS` or `STATUS: FAIL`.
2. Avoid noisy review failures for trivial or unrelated changes.
3. Review feedback must be concrete, material, actionable, and limited to
   changed code or directly affected behavior.
4. Fix guidance must reject unrelated refactoring and pattern use for its own
   sake.
5. Repository rules must override external design-pattern or system-design
   references.
6. Prompt changes must remain concise enough to keep reviewer focus.

## Acceptance Criteria

1. `scripts/ai-review-prompts/review.md` includes explicit FR/NFR, system
   design, design pattern, code smell, software engineering best practices, and
   repository architecture review criteria.
2. `scripts/ai-review-prompts/review.md` preserves the exact `STATUS: PASS` /
   `STATUS: FAIL` output contract.
3. `scripts/ai-review-prompts/review.md` includes an applicability rule
   preventing heavyweight design feedback for trivial localized changes.
4. `scripts/ai-review-prompts/fix.md` requires scoped remediation for design
   and smell findings.
5. `tests/CLI/bats/make_ai_review_loop_tests.bats` verifies the review and fix
   prompts contain the expanded criteria and that Claude receives the same
   repository criteria.
6. Documentation sync records the expanded AI review loop behavior for
   contributors.

## Out Of Scope

1. Changing the `scripts/ai-review-loop.sh` parser contract or fix-loop
   mechanics.
2. Replacing human review, CodeRabbit, or CI.
3. Requiring heavyweight system design review for every PR.
4. Introducing design patterns purely to satisfy checklist coverage.
5. Weakening DDD, CQRS, Hexagonal Architecture, or Domain-purity rules.

## Validation Plan

1. Run CLI Bats coverage for `tests/CLI/bats/make_ai_review_loop_tests.bats`.
2. Run `make ci` and confirm it ends successfully.
3. Run `make ai-review-loop` and confirm the expanded prompt still returns
   parseable `STATUS: PASS` or `STATUS: FAIL`.
4. If the AI review loop applies fixes, rerun `make ci` and
   `make ai-review-loop` until PASS.

## Traceability

- Research:
  `specs/autonomous/20260607-154408-bmad-fr-nfr-reviewer-system-design-patterns/research.md`
- Product brief:
  `specs/autonomous/20260607-154408-bmad-fr-nfr-reviewer-system-design-patterns/product-brief.md`
- Distillate:
  `specs/autonomous/20260607-154408-bmad-fr-nfr-reviewer-system-design-patterns/product-brief-distillate.md`
- Review prompt: `scripts/ai-review-prompts/review.md`
- Fix prompt: `scripts/ai-review-prompts/fix.md`
- Prompt tests: `tests/CLI/bats/make_ai_review_loop_tests.bats`
