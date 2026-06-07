# Epics: BMAD/AI Review Loop Design Coverage

## Assumptions

- No UX artifact is in scope.
- Existing `STATUS: PASS` / `STATUS: FAIL` parser behavior must remain
  unchanged.
- Prompt policy is the primary implementation mechanism.
- Script changes are limited to Claude review-policy bridging.
- `make ci` is the authoritative validation gate for the Bats prompt/loop
  tests.

## Epic 1: Expand AI Review Loop Design Coverage With Agent Parity

Deliver prompt-driven FR/NFR, system design, design-pattern, code-smell, and
repository-architecture review coverage across supported local AI review agents
while preserving the current review-loop parser, fix-loop mechanics, and
contributor workflow.

### Story 1.1: Expand Review Prompt Criteria

As a contributor, I want `scripts/ai-review-prompts/review.md` to assess FR/NFR
coverage, system design relevance, design-pattern fit, code smells, and
repository architecture so AI review feedback catches material design and
maintainability risks.

Acceptance criteria:

- Review prompt preserves exact first-line output contract: `STATUS: PASS` or
  `STATUS: FAIL`.
- Prompt explicitly covers FR intent and NFRs: security, performance,
  reliability, observability, maintainability, backwards compatibility, and
  testability.
- System design checks apply only when changed behavior justifies them.
- Design-pattern and code-smell checks flag both underuse and over-engineering
  only when concrete and material.
- Repository DDD, CQRS, Hexagonal Architecture, and framework-free Domain rules
  remain authoritative.

Validation command:

```bash
make ci
```

### Story 1.2: Expand Fix Prompt Remediation Policy

As a contributor, I want failed AI review findings to drive scoped fixes so
remediation addresses FR/NFR, design, pattern, and smell issues without
unrelated refactoring.

Acceptance criteria:

- `scripts/ai-review-prompts/fix.md` requires the smallest coherent scoped
  change.
- Fix guidance rejects unrelated refactoring and design patterns for their own
  sake.
- Fix guidance preserves layer boundaries: Domain framework-free, Application
  owns use cases/contracts, Infrastructure owns adapters.
- Fix prompt output format remains stable.

Validation command:

```bash
make ci
```

### Story 1.3: Add Claude Review Prompt Parity

As a maintainer, I want Claude review execution to receive the same repository
review criteria as Codex while preserving Claude's built-in `/review` path.

Acceptance criteria:

- Codex continues to consume `review.md` directly.
- Claude continues to invoke `/review`.
- Claude receives the substituted repository review policy through appended
  prompt context.
- Claude output must still be parseable by the existing first-10-lines status
  parser.
- Claude stderr remains separated from review stdout.

Validation commands:

```bash
make ci
AI_REVIEW_AGENT=claude make ai-review-loop
```

### Story 1.4: Strengthen CLI Bats Coverage

As a maintainer, I want prompt and loop tests to prove the expanded review
policy and Claude parity remain intact.

Acceptance criteria:

- Bats tests assert review prompt contains FR/NFR, system design, design
  pattern, code smell, and architecture criteria.
- Bats tests assert fix prompt requires scoped design/smell remediation and the
  smallest coherent change.
- Bats tests validate Codex PASS behavior.
- Bats tests validate Claude `/review` invocation, appended criteria, and stderr
  separation.

Validation command:

```bash
make ci
```

### Story 1.5: Sync Contributor Documentation

As a contributor, I want documentation to explain the expanded AI review loop
behavior so the local review gate is predictable before push or PR readiness.

Acceptance criteria:

- Documentation states `make ai-review-loop` includes FR/NFR, system design,
  design-pattern, code-smell, maintainability, and repository-architecture
  checks.
- Documentation preserves the required order: run `make ci`, then
  `make ai-review-loop`, repeat if fixes are applied.
- Claude parity behavior is documented without implying parser or fix-loop
  mechanics changed.

Validation commands:

```bash
make ci
make ai-review-loop
```

## Requirements Coverage Map

| Requirement area                    | Covered by       |
| ----------------------------------- | ---------------- |
| FR review coverage                  | Story 1.1        |
| NFR review coverage                 | Story 1.1        |
| System design checks                | Story 1.1        |
| Design-pattern fit                  | Story 1.1        |
| Code-smell detection                | Story 1.1        |
| Repository architecture enforcement | Stories 1.1, 1.2 |
| Scoped fixer behavior               | Story 1.2        |
| Claude review prompt parity         | Story 1.3        |
| Prompt and loop tests               | Story 1.4        |
| Documentation sync                  | Story 1.5        |
