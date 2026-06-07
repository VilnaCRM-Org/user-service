# Research: BMAD FR/NFR Reviewer Design Coverage

## Current State

BMALPH is configured for Codex with planning artifacts under `specs` in
`_bmad/config.yaml`. `_bmad/COMMANDS.md` maps the research stage to the
`analyst` command.

The local AI review loop is prompt-driven. `scripts/ai-review-loop.sh` reads
`scripts/ai-review-prompts/review.md` and
`scripts/ai-review-prompts/fix.md`, defaults to Codex as the reviewer, writes
logs under `var/ai-review`, and parses `STATUS: PASS` or `STATUS: FAIL` from
the first 10 output lines. The script already supports multiple reviewers,
configurable base branch detection, and a verification command that defaults to
`make ci`.

Before this work, the reviewer prompt covered correctness, security,
performance, architecture, tests, repository rules, and Google Engineering
Practices. It did not explicitly require FR/NFR coverage, system design
tradeoffs, design-pattern fit, or code-smell review.

## External Review Criteria To Incorporate

The System Design Primer frames system review around use cases, constraints,
assumptions, component boundaries, bottlenecks, and tradeoffs. Relevant PR
review dimensions include latency versus throughput, availability versus
consistency, caching, replication/failover, database choices, queues, back
pressure, communication style, and security.

Refactoring Guru groups design patterns by intent and applicability. Pattern
review should check whether a changed implementation uses patterns such as
Adapter, Strategy, Factory, Decorator, Command, Observer, Facade, Proxy, or
Pipeline because the problem warrants them, not because pattern use is an end in
itself.

Refactoring Guru groups code smells into Bloaters, Object-Orientation Abusers,
Change Preventers, Dispensables, and Couplers. Concrete smells worth encoding
in the reviewer include long methods, large classes, primitive obsession, long
parameter lists, data clumps, switch-driven behavior, temporary fields,
alternative classes with different interfaces, divergent change, shotgun
surgery, duplicate code, unclear comments, dead code, speculative generality,
feature envy, inappropriate intimacy, message chains, and middle-man objects.

## Implementation Surface

Primary implementation surface:

- `scripts/ai-review-prompts/review.md`
- `scripts/ai-review-prompts/fix.md`
- `tests/CLI/bats/make_ai_review_loop_tests.bats`

Documentation sync surface:

- `.claude/skills/code-review/SKILL.md`
- `docs/onboarding.md`

No change is required in `scripts/ai-review-loop.sh` because the first-line
status contract remains unchanged.

## Risks

Broader review criteria can create noise on small PRs. The reviewer prompt must
include an applicability rule so system design, pattern, and smell feedback
stays concrete and scoped to changed code or directly affected behavior.

The fixer could over-refactor after receiving smell findings. The fixer prompt
must require the smallest coherent scoped change and explicitly reject unrelated
refactoring.

Repository rules must override external references. DDD, CQRS, Hexagonal
Architecture, and the domain framework-free rule stay authoritative.

## Recommended Acceptance Criteria

1. `scripts/ai-review-prompts/review.md` explicitly reviews changed code for
   FR/NFR coverage, system design, pattern fit, code smells, and repository
   architecture.
2. The reviewer prompt keeps the exact first-line `STATUS: PASS` or
   `STATUS: FAIL` contract used by `scripts/ai-review-loop.sh`.
3. `scripts/ai-review-prompts/fix.md` tells the fixer to address design,
   pattern, and smell findings with scoped changes only.
4. Bats tests prove the review and fix prompts retain the new criteria.
5. Documentation explains that the local AI review loop now includes these
   design and maintainability checks.
