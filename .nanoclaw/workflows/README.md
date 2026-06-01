# NanoClaw Dev Cycle Workflow

A deterministic, auditable development workflow for the `user-service` repository.
Inspired by SDD (Specification-Driven Development), BMAD, and TDD principles.
Every task produces a complete paper trail of decisions, analysis, and validation results.

---

## Prerequisites

| Tool                        | Purpose                                  | Install                                                                         |
| --------------------------- | ---------------------------------------- | ------------------------------------------------------------------------------- |
| `bash` ≥ 4                  | Script runtime                           | system                                                                          |
| `git`                       | Version control                          | system                                                                          |
| `gh`                        | GitHub CLI — CI checks, PR comments      | `brew install gh` / `apt install gh` / [cli.github.com](https://cli.github.com) |
| `make`                      | Build targets                            | system                                                                          |
| `composer`                  | PHP dependency management                | [getcomposer.org](https://getcomposer.org)                                      |
| `docker` + `docker compose` | Container runtime for `make ci`          | [docs.docker.com](https://docs.docker.com/get-docker/)                          |
| `jq`                        | JSON parsing (optional, improves output) | `brew install jq` / `apt install jq`                                            |

---

## The 11 Phases

| #   | Phase               | Description                                                                     |
| --- | ------------------- | ------------------------------------------------------------------------------- |
| 01  | Task Intake         | Capture task description and baseline git state; create task folder             |
| 02  | Specification       | Produce unambiguous spec: problem, behavior, acceptance criteria, test strategy |
| 03  | Impact Analysis     | Map every impacted file, module, migration risk, and CI job                     |
| 04  | Implementation Plan | Step-by-step plan: files to change, tests to add, validation commands, rollback |
| 05  | Implementation      | Execute the plan exactly — no scope creep, no secrets, no push                  |
| 06  | Local Validation    | Run all validation gates; max 3 fix attempts for `make ci`                      |
| 07  | CI Investigation    | Diagnose failing CI jobs; produce fix priority list                             |
| 08  | PR Comments         | Classify every PR comment; produce action plan (no implementation)              |
| 09  | Refactor            | Implement clear PR comments; document ambiguous ones; re-validate               |
| 10  | Final Review        | Full review checklist + `final-summary.md` ready for handoff                    |
| 11  | Done                | All gates passed, summary written, ready to merge                               |

---

## CLI Reference

### `start "<description>"`

Begin a new task. Creates `.ai/tasks/<timestamp-slug>/` with all artifact files.

```bash
dev-cycle.sh start "add email verification endpoint"
# → .ai/tasks/20240101-120000-add-email-verification/
```

### `spec <task-folder>`

Phase 02. Print the spec prompt and required sections.

```bash
dev-cycle.sh spec .ai/tasks/20240101-120000-add-email-verification
```

### `impact <task-folder>`

Phase 03. Dump repo context (Makefile, composer.json, Docker files, CI workflows, test dirs) and print the impact analysis prompt.

```bash
dev-cycle.sh impact .ai/tasks/20240101-120000-add-email-verification
```

### `plan <task-folder>`

Phase 04. Verify prior phases exist, print the plan prompt.

```bash
dev-cycle.sh plan .ai/tasks/20240101-120000-add-email-verification
```

### `implement <task-folder>`

Phase 05. Print safety reminder and implementation prompt.

```bash
dev-cycle.sh implement .ai/tasks/20240101-120000-add-email-verification
```

### `validate <task-folder>`

Phase 06. Detect and run all available validation commands. Max 3 fix attempts for `make ci`.

```bash
dev-cycle.sh validate .ai/tasks/20240101-120000-add-email-verification
```

### `ci <task-folder> <pr-number-or-url>`

Phase 07. Fetch CI check results and failed job data. Store raw data under `.ai/ci/pr-<N>/`.

```bash
dev-cycle.sh ci .ai/tasks/20240101-120000-add-email-verification 42
dev-cycle.sh ci .ai/tasks/20240101-120000-add-email-verification https://github.com/org/repo/pull/42
```

### `pr-comments <task-folder> <pr-number-or-url>`

Phase 08. Fetch and group PR review and issue comments. Store raw data under `.ai/reviews/pr-<N>/`.

```bash
dev-cycle.sh pr-comments .ai/tasks/20240101-120000-add-email-verification 42
```

### `refactor <task-folder> <pr-number-or-url>`

Phase 09. Implement clear PR comments, document ambiguous ones, run full validation.

```bash
dev-cycle.sh refactor .ai/tasks/20240101-120000-add-email-verification 42
```

### `review <task-folder>`

Phase 10. Run final review checklist, produce `review.md` and `final-summary.md`.

```bash
dev-cycle.sh review .ai/tasks/20240101-120000-add-email-verification
```

### `full "<description>"`

Convenience shortcut: runs `start` → `spec` → `impact` → `plan`, then pauses.
Use when you want all planning phases done before you begin implementation.

```bash
dev-cycle.sh full "refactor auth middleware for session compliance"
# → Runs phases 01–04, then prints:
# "Review plan.md and run 'implement' when ready."
```

---

## Directory Layout

```
.nanoclaw/
  workflows/
    dev-cycle.sh              Main workflow driver script
    config.example.env        Configuration template
    prompts/
      01-task-intake.md       Phase 01 agent prompt
      02-specification.md     Phase 02 agent prompt
      03-impact-analysis.md   Phase 03 agent prompt
      04-implementation-plan.md
      05-implementation.md
      06-local-validation.md
      07-ci-investigation.md
      08-pr-comments.md
      09-refactor.md
      10-final-review.md

.ai/
  tasks/
    <timestamp-slug>/
      task.md                 Task description + phase checklist
      spec.md                 Specification (phase 02)
      impact-analysis.md      Impact analysis (phase 03)
      plan.md                 Implementation plan (phase 04)
      implementation-log.md   Change log (phase 05)
      validation.md           Validation results (phase 06)
      ci.md                   CI investigation notes (phase 07)
      pr-comments.md          Classified PR comments (phase 08)
      pr-action-plan.md       PR action plan (phase 08)
      needs-human-decision.md Ambiguous / conflicting items (phase 09)
      review.md               Final review checklist (phase 10)
      final-summary.md        Handoff summary (phase 10)
  reviews/
    pr-<N>/
      metadata.json
      review-comments.json
      issue-comments.json
  ci/
    pr-<N>/
      metadata.json
      checks.txt
      runs.json
```

---

## Recommended Daily Workflow

**Starting a new task:**

```bash
# 1. Plan (phases 01–04)
dev-cycle.sh full "your task description here"

# 2. Review the plan
cat .ai/tasks/<slug>/plan.md

# 3. Implement
dev-cycle.sh implement .ai/tasks/<slug>

# 4. Validate locally
dev-cycle.sh validate .ai/tasks/<slug>

# 5. Final review, then push
dev-cycle.sh review .ai/tasks/<slug>
# → read final-summary.md → git push (with explicit approval)
```

**After a PR is open:**

```bash
# Investigate CI failures
dev-cycle.sh ci .ai/tasks/<slug> <pr-number>

# Analyze review comments
dev-cycle.sh pr-comments .ai/tasks/<slug> <pr-number>

# Implement safe comments
dev-cycle.sh refactor .ai/tasks/<slug> <pr-number>
```

---

## Configuration

Copy `config.example.env` to `config.env` in the same directory and adjust:

```env
NANOCLAW_WORKFLOW_MAX_FIX_ATTEMPTS=3   # Max CI fix loops before giving up
NANOCLAW_WORKFLOW_LOG_LEVEL=info       # debug | info | warn
NANOCLAW_WORKFLOW_COLORS=auto          # auto | always | never
NANOCLAW_WORKFLOW_REPO_ROOT=           # Override repo root (auto-detected)
NANOCLAW_WORKFLOW_GH_REMOTE=origin     # Git remote for gh commands
```

---

## Safety Rules

These constraints are enforced at the script level and in every prompt:

1. **Never push to remote** without explicit user approval in the current turn.
2. **Never run destructive commands:** `rm -rf`, `git reset --hard`, `git clean -fd`, `docker compose down -v`, `docker volume rm`, `docker system prune --volumes`, `DROP DATABASE`, `reboot`.
3. **Never modify** `.env`, lock files, CI workflow files, or static analysis configs unless the implementation plan explicitly calls for it.
4. **Never expose secrets** in output, logs, or commit messages.
5. **Never deviate from the plan** without documenting the deviation in `implementation-log.md`.

---

## Auditing

Every task folder under `.ai/tasks/` is a complete audit trail:

- What was planned and why (`task.md`, `spec.md`, `plan.md`)
- What was done and how (`implementation-log.md`)
- That it was validated (`validation.md`)
- That review comments were addressed (`pr-action-plan.md`, `needs-human-decision.md`)
- That it is ready to merge (`final-summary.md`)

Commit the `.ai/tasks/<slug>/` folder alongside your code changes to preserve the trail in git history.
