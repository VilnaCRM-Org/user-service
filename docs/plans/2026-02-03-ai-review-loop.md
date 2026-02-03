# AI Review Loop Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a local `make ai-review-loop` command that runs an autonomous Codex (default) review+fix loop against the current PR diff, verifying with `make ci` after each fix, and optionally supports Claude.

**Architecture:** A Make target calls a new bash orchestrator script that detects PR base via `gh`, runs `codex review` with strict PASS/FAIL prompts, iterates fixes with `codex exec`, and logs outputs. Prompts live in `scripts/ai-review-prompts/` to keep behavior consistent and reviewable.

**Tech Stack:** Bash, Make, GitHub CLI (`gh`), Codex CLI (`codex`), optional Claude CLI, Bats for CLI tests, existing Make-based CI.

---

### Task 1: Add Bats tests for the new review loop entry point

**Files:**
- Create: `tests/CLI/bats/make_ai_review_loop_tests.bats`

**Step 1: Write the failing test**

```bash
#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

@test "make help includes ai-review-loop" {
  run make help
  assert_success
  assert_output --partial "ai-review-loop"
}

@test "ai-review-loop fails with helpful message when Codex command is missing" {
  run AI_REVIEW_CODEX_CMD=codex-missing ./scripts/ai-review-loop.sh
  assert_failure
  assert_output --partial "Codex CLI (codex) is required"
}
```

**Step 2: Run test to verify it fails**

Run: `make bats`
Expected: FAIL because `scripts/ai-review-loop.sh` does not exist yet.

**Step 3: Commit**

```bash
git add tests/CLI/bats/make_ai_review_loop_tests.bats
git commit -m "test: add bats coverage for ai-review-loop"
```

---

### Task 2: Add AI review prompt templates

**Files:**
- Create: `scripts/ai-review-prompts/review.md`
- Create: `scripts/ai-review-prompts/fix.md`

**Step 1: Write the failing test**

No additional tests required beyond Task 1.

**Step 2: Write minimal implementation**

`review.md` must force the first line to be `STATUS: PASS` or `STATUS: FAIL`, then a numbered list of issues. It must say: review current PR diff based on provided base branch, and do not run tools.

`fix.md` must instruct: edit files only, use `make` for any PHP tooling, and keep changes within the PR scope. It should require a short summary + list of files changed.

**Step 3: Commit**

```bash
git add scripts/ai-review-prompts/review.md scripts/ai-review-prompts/fix.md
git commit -m "chore: add ai review prompt templates"
```

---

### Task 3: Implement the review loop orchestrator script

**Files:**
- Create: `scripts/ai-review-loop.sh`

**Step 1: Write the failing test**

Covered by Task 1.

**Step 2: Write minimal implementation**

Key behaviors to implement:
- Validate `codex` availability (configurable via `AI_REVIEW_CODEX_CMD`, default `codex`). Fail fast with a helpful message.
- Optional Claude support via `AI_REVIEW_AGENT`/`AI_REVIEW_AGENTS` and `AI_REVIEW_CLAUDE_CMD`.
- Detect PR base via `gh pr view --json baseRefName -q .baseRefName`. If missing/unavailable, fall back to `AI_REVIEW_BASE` or `origin/main` with a warning.
- Fetch base branch if not present locally.
- Run `codex review --base <base>` with prompt file, parse first line for `STATUS: PASS|FAIL`.
- On FAIL, run `codex exec` with fix prompt, then run `AI_REVIEW_VERIFY_CMD` (default `make ci`).
- Loop until PASS or `AI_REVIEW_MAX_ITER` (default 3), logging to `var/ai-review/`.

**Step 3: Run test to verify it passes**

Run: `make bats`
Expected: PASS for the new tests.

**Step 4: Commit**

```bash
git add scripts/ai-review-loop.sh
git commit -m "feat: add ai review loop script"
```

---

### Task 4: Wire the Makefile target

**Files:**
- Modify: `Makefile`

**Step 1: Write the failing test**

Covered by Task 1 (make help must list `ai-review-loop`).

**Step 2: Write minimal implementation**

Add a new target:
- `ai-review-loop: ## Run local AI code review + fix loop (Codex default)`
- It should call `./scripts/ai-review-loop.sh`.

**Step 3: Run test to verify it passes**

Run: `make bats`
Expected: PASS.

**Step 4: Commit**

```bash
git add Makefile
git commit -m "chore: add make ai-review-loop target"
```

---

### Task 5: Update documentation and agent rules

**Files:**
- Modify: `AGENTS.md`
- Modify: `docs/onboarding.md`

**Step 1: Write the failing test**

No automated tests; verify by review.

**Step 2: Write minimal implementation**

Add a rule in `AGENTS.md`: after `make ci` and before pushing/marking PR ready, run `make ai-review-loop`. Mention Codex default and optional Claude via env vars.

Update onboarding to mention the local AI review loop as the primary fast path, with CodeRabbit remaining as a final check.

**Step 3: Commit**

```bash
git add AGENTS.md docs/onboarding.md
git commit -m "docs: document ai review loop"
```

---

### Task 6: New Feature Verification Gate (MANDATORY)

**Files:**
- Follow `.claude/skills/` workflows after implementation.

**Step 1: Execute every skill in `.claude/skills/`**

Run each skill’s `SKILL.md` steps in order. If a skill is not applicable, explicitly record “Not applicable” with a concrete reason.

**Step 2: Provide evidence**

For each required command, capture the command and outcome. Use `make` or `docker compose exec php ...` only.

**Step 3: Final verification**

Do not mark the feature complete until all skills are executed and evidence is recorded.

---

**Notes:**
- User explicitly requested working on the current branch without a worktree.
- Use `make` or `docker compose exec php ...` for any PHP tooling; never run PHP directly on host.
