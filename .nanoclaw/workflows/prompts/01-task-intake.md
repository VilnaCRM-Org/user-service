# Phase 01 — Task Intake

**Goal:** Capture the task, establish baseline context, and open the task folder.

## Inputs

- User-provided task description (natural language)
- Current git state (branch, latest commit, working tree status)

## Action

Run:

```
dev-cycle.sh start "<task description>"
```

The script will:

1. Detect the repo root
2. Capture git state (branch, HEAD commit, status)
3. Create `.ai/tasks/<timestamp-slug>/` with all artifact skeletons
4. Write `task.md` with description, timestamp, branch, commit, status, and the phase checklist

## Quality Gates

- [ ] `task.md` exists and contains a non-empty task description
- [ ] Phase checklist in `task.md` lists all 11 phases unchecked (except this one)
- [ ] Git state captured accurately (branch name and commit hash visible)
- [ ] No application code was modified

## Next Step

Run `dev-cycle.sh spec <task-folder>` to produce the specification.

When done, update the phase checklist in `task.md`.
