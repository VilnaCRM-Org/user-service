# Phase 09 — Refactor from PR Comments

**Goal:** Implement all clear, safe PR comments. Document ambiguous ones. Run validation.

## Inputs

- `.ai/tasks/<slug>/pr-action-plan.md` — the classified action plan (from phase 08)
- `.ai/tasks/<slug>/spec.md` — acceptance criteria (must not be violated)
- `.ai/tasks/<slug>/impact-analysis.md` — boundary and risk context

## Outputs

- Modified application files
- `.ai/tasks/<slug>/implementation-log.md` — updated with each change
- `.ai/tasks/<slug>/needs-human-decision.md` — created if any items are ambiguous or conflicting
- `.ai/tasks/<slug>/validation.md` — updated after each meaningful change and at end

## Process

### Step 1 — Process clear items

For each action item with status `clear`:

1. Read the referenced file and the comment.
2. Make the minimal change that satisfies the comment.
3. Append to `implementation-log.md` (file changed, comment ID, what was done).
4. If the change touches logic: run targeted validation immediately (e.g., `make phpunit`).

### Step 2 — Document ambiguous items

For each action item with status `ambiguous`:

- Add to `needs-human-decision.md`:
  - Comment ID, reviewer, comment text
  - Why it is ambiguous
  - Options considered
  - Recommendation (if any)
- Do not implement. Do not guess.

### Step 3 — Document spec/architecture conflicts

For each item that conflicts with `spec.md` or an architecture boundary:

- Add to `needs-human-decision.md` with full context.
- Do not implement without explicit approval.

### Step 4 — Full validation

After all clear items are processed:

```
dev-cycle.sh validate <task-folder>
```

If `make ci` exists, it must pass before declaring refactor complete.

## Hard Constraints (same as implementation phase)

- No push to remote
- No destructive commands
- No secrets in output
- No scope creep beyond the PR comments being addressed

## Quality Gates

- [ ] All `clear` action items from `pr-action-plan.md` are implemented
- [ ] All `ambiguous` and `needs-human-decision` items are documented in `needs-human-decision.md`
- [ ] `implementation-log.md` has an entry for each change
- [ ] Full validation passes (all commands exit 0)
- [ ] `make ci` passes (if available)
- [ ] No push to remote was made

When done, update the phase checklist in `task.md`.
