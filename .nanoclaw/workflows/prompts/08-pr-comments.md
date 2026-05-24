# Phase 08 — PR Comments Analysis

**Goal:** Read, classify, and group every PR comment into a structured action plan. Do not implement anything yet.

## Inputs

- `.ai/reviews/pr-<N>/metadata.json`
- `.ai/reviews/pr-<N>/review-comments.json` — inline review comments
- `.ai/reviews/pr-<N>/issue-comments.json` — PR-level comments
- `.ai/tasks/<slug>/spec.md` — to cross-reference against

## Outputs

- `.ai/tasks/<slug>/pr-comments.md` — full classified list of comments
- `.ai/tasks/<slug>/pr-action-plan.md` — actionable plan for the refactor phase

## pr-comments.md structure

Group comments by:

### By File
For each file mentioned in comments: list the comments, line reference, reviewer, and content.

### By Reviewer
For each reviewer: summarize their main concerns.

### Classification Table
| # | File | Line | Reviewer | Summary | Category | Status |
|---|------|------|----------|---------|----------|--------|

**Category options:**
- `style` — formatting, naming, cosmetic
- `correctness` — functional bug or logic error
- `architecture` — structural or boundary concern
- `performance` — efficiency issue
- `security` — vulnerability or risk
- `test-coverage` — missing or weak test
- `docs` — missing documentation
- `question` — reviewer asking for clarification (no action needed unless answered)

## pr-action-plan.md structure

### Required Action Items
For each comment requiring a code change:
- ID (e.g., `RC-001`)
- File and line
- Reviewer and comment summary
- Proposed action
- Status: `clear` | `ambiguous` | `already-resolved` | `needs-human-decision`
- Severity: `blocking` | `suggested` | `nit`

### Ambiguous Items
List comments where the intent or correct fix is unclear. Do not attempt these.

### Conflicts with Spec/Architecture
List any comment that would contradict `spec.md` or cross an architecture boundary. These require human decision before acting.

### Already-Resolved Items
List comments that were addressed during the original implementation (with evidence).

## Quality Gates

- [ ] Every comment from both JSON files appears in pr-comments.md
- [ ] Every required action item has a status and severity
- [ ] Ambiguous items are listed separately, not quietly skipped
- [ ] No application code was modified during this phase
- [ ] pr-action-plan.md is the input for the refactor phase — it must be complete

When done, update the phase checklist in `task.md`.
