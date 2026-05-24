# Phase 10 — Final Review

**Goal:** Verify the complete change set is correct, safe, and ready to merge. Produce the final summary.

## Inputs

- `git diff --stat` and changed file contents
- `.ai/tasks/<slug>/task.md` — original task and phase checklist
- `.ai/tasks/<slug>/spec.md` — acceptance criteria
- `.ai/tasks/<slug>/implementation-log.md`
- `.ai/tasks/<slug>/validation.md`
- `.ai/tasks/<slug>/ci.md` (if applicable)
- `.ai/tasks/<slug>/pr-action-plan.md` (if applicable)

## Outputs

- `.ai/tasks/<slug>/review.md`
- `.ai/tasks/<slug>/final-summary.md`

## Review Checklist (complete in review.md)

For each item, state: PASS / FAIL / N/A + brief evidence.

- [ ] **Scope control** — changes are confined to what was planned; no scope creep
- [ ] **Debug code** — no `var_dump`, `print_r`, `dd()`, `console.log`, commented-out blocks
- [ ] **Secrets** — no API keys, passwords, tokens, or credentials in code or output
- [ ] **Destructive operations** — no dangerous runtime commands without guards
- [ ] **Test coverage** — new behavior has corresponding tests; no coverage regressions
- [ ] **Architecture boundaries** — no unexpected cross-module dependencies
- [ ] **Coding standards** — `make cs-check` / `make phpstan` / `make psalm` pass
- [ ] **Unresolved TODOs** — no `TODO` or `FIXME` added without a tracking issue
- [ ] **Validation** — all local validation commands pass
- [ ] **CI** — all CI checks pass (or failures are documented and unrelated)
- [ ] **Acceptance criteria** — every criterion from spec.md is verifiably met

## final-summary.md Required Sections

Write this document as if handing off to a reviewer who has no prior context.

### Task Summary
One paragraph: what was the task and why did it need to be done?

### Implementation Summary
What approach was taken? Key design decisions and why.

### Changed Files
Table: file path → change type → one-line description.

### Validation Commands Run
List every command run, with pass/fail status.

### Validation Status
Overall: PASSED / FAILED (with details if failed)

### CI Status
Overall: PASSED / FAILED / NOT CHECKED (with job breakdown if failed)

### PR Comments Handled
Table: comment ID → status (implemented / deferred / needs-decision) → brief note.
Write "N/A" if no PR comments were addressed.

### Unresolved Risks
Any remaining open questions, deferred items, or known limitations.

### Recommended Commit Message
```
<type>(<scope>): <subject>

<body — 2–4 lines explaining what and why>

<footer — references, breaking changes>
```

### Recommended PR Response
Draft response to post in the PR: summarize what was done, what was deferred and why, and what reviewers should focus on.

## Quality Gates

- [ ] All review checklist items are filled in (no blanks)
- [ ] final-summary.md contains all 10 required sections
- [ ] Recommended commit message follows conventional commits format
- [ ] Phase checklist in task.md is fully checked (all 11 items)
- [ ] No push to remote was made

When done, update the phase checklist in `task.md` — mark all remaining items including `done`.
