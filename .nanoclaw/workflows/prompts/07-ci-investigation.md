# Phase 07 — CI Investigation

**Goal:** Diagnose every failing CI check on the PR and produce an actionable fix plan.

## Inputs

- `.ai/ci/pr-<N>/metadata.json` — PR metadata
- `.ai/ci/pr-<N>/checks.txt` — check statuses
- `.ai/ci/pr-<N>/runs.json` — workflow run list
- Output from `dev-cycle.sh ci <task-folder> <pr-number>`

## Output

- `.ai/tasks/<slug>/ci.md`

## Required Sections in ci.md

### 1. PR Summary
Title, branch, base branch, author, URL.

### 2. Check Status Overview
Table: check name → status → conclusion → duration. Mark failing checks clearly.

### 3. Failure Analysis
For each failing job:
- Job name and workflow file
- Failure category: test failure / lint error / type error / build failure / infra issue / timeout
- Key error lines (copy the most relevant 10–20 lines from the log)
- Root cause hypothesis
- Proposed fix (concrete file + change, or "needs investigation")
- Estimated effort: trivial / small / medium / large

### 4. Fix Priority Order
Numbered list: in what order should the fixes be applied? Start with the most blocking.

### 5. Checks to Ignore
List any checks that failed for reasons unrelated to this PR (flaky tests, infra issues, unrelated branch failures).

## Investigation Process

1. Read `checks.txt` to identify failing jobs.
2. For each failing job: fetch the log with `gh run view <id> --log-failed`.
3. Search the log for: `FAIL`, `ERROR`, `Fatal`, `Exception`, `assert`, `expected`, `actual`.
4. Cross-reference with the changed files from PR metadata.
5. Do not guess at fixes without reading the actual log output.

## Quality Gates

- [ ] Every failing check has a Failure Analysis entry
- [ ] Every entry has a root cause hypothesis and proposed fix
- [ ] Fix Priority Order is present and complete
- [ ] ci.md does not contain raw JSON dumps — human-readable only
- [ ] No application code was modified during this phase

When done, update the phase checklist in `task.md`.
