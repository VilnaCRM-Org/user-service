# Phase 06 — Local Validation

**Goal:** Prove the implementation is correct by running every available validation gate and reaching a clean pass.

## Inputs

- `.ai/tasks/<slug>/implementation-log.md` — what was changed
- `.ai/tasks/<slug>/spec.md` — acceptance criteria to verify
- Output from `dev-cycle.sh validate <task-folder>`

## Output

- `.ai/tasks/<slug>/validation.md` — populated by the script; supplement with analysis here

## Process

Run:

```
dev-cycle.sh validate <task-folder>
```

The script runs validation commands in this priority order:

1. `make ci` (primary gate — up to 3 fix attempts)
2. Remaining detected commands (`make test`, `make phpstan`, `make psalm`, `make cs-check`, etc.)

## On Failure

For each failed command, capture in `validation.md`:

1. The command and its exit code
2. The last 30 lines of output
3. Your diagnosis of the root cause
4. The fix you applied
5. The result of the re-run

Do not guess at fixes. Read the error output carefully before acting.

## Fix Attempt Rules

- Maximum 3 fix attempts per validation command (script enforces this for `make ci`).
- After each fix attempt, re-run **only the failed command** first, then the full suite once it passes.
- If a fix is not obvious within 3 attempts: document the failure in `validation.md`, mark the blocker in `task.md`, and surface it to the user rather than guessing forward.

## Acceptance Criteria Verification

After all commands pass, manually verify each acceptance criterion from `spec.md`:

- For each criterion, state how it was verified (test name, manual check, log line, etc.)

## Quality Gates

- [ ] All detected validation commands pass (exit 0)
- [ ] `validation.md` has a pass/fail entry for every command run
- [ ] Every acceptance criterion from `spec.md` is marked verified with evidence
- [ ] No secrets appear in logs
- [ ] No push to remote was made

When done, update the phase checklist in `task.md`.
