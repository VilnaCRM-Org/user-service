# Phase 05 — Implementation

**Goal:** Execute the implementation plan exactly. No more, no less.

## Inputs

- `.ai/tasks/<slug>/plan.md` — the approved implementation plan
- `.ai/tasks/<slug>/spec.md` — acceptance criteria
- `.ai/tasks/<slug>/impact-analysis.md` — boundary and risk context

## Output

- Modified/created application files (per plan)
- `.ai/tasks/<slug>/implementation-log.md` — updated throughout

## Process

1. Read `plan.md` in full before touching any file.
2. Follow the steps in order. Do not skip or reorder.
3. After each step, append to `implementation-log.md`:
   - Files changed and why
   - Command run (if any) and its output summary
   - Any unresolved questions or deviations from the plan

## Hard Constraints

- **No push to remote.** Never run `git push` without explicit user approval.
- **No destructive commands.** Never run: `rm -rf`, `git reset --hard`, `git clean -fd`, `docker compose down -v`, `docker volume rm`, `docker system prune --volumes`, `DROP DATABASE`, `reboot`.
- **No secrets in output.** Redact any token, password, or key from logs or output.
- **No modification of:** `.env`, lock files (`composer.lock`, `package-lock.json`, `yarn.lock`), CI workflow files (`.github/workflows/`), or static analysis configs (`phpstan.neon`, `psalm.xml`, `.php-cs-fixer.php`) — **unless the plan explicitly calls for it**.
- **Scope control.** Do not fix bugs, clean up code, or add features not in the plan. If you notice something, document it in `implementation-log.md` as a future task.

## implementation-log.md format

```markdown
## Step N — <step name>

**Files changed:**
- `path/to/file.php` — <one-line reason>

**Commands run:**
- `make ...` — exit 0

**Unresolved questions / deviations:**
- <any notes>
```

## Quality Gates

- [ ] All steps from `plan.md` are completed or explicitly skipped with reason
- [ ] `implementation-log.md` has an entry for each step
- [ ] No files outside the plan's scope were modified
- [ ] No secrets appear in any log or output
- [ ] No push to remote was made

When done, update the phase checklist in `task.md`.
