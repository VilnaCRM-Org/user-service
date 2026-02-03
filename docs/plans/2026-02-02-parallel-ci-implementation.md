# Parallel CI Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Make `make ci` run parallel CI via Taskfile with a sequential preflight while failing fast if Task is missing.

**Architecture:** A Taskfile orchestrates CI with a sequential preflight for mutating steps and a parallel stage for all remaining checks. `make ci` is the host entrypoint and requires `task` on the host.

**Tech Stack:** Makefile, go-task/task, Docker Compose, Symfony console, PHPUnit, Psalm, Deptrac, PHPInsights, PHPMD, Schemathesis.

## Task 1: Update `make ci` to Require Task

**Files:**

- Modify: `Makefile`

**Step 1: Verify current behavior (baseline)**

Run: `make ci-sequential`

Expected: sequential CI starts and ends with `✅ CI checks successfully passed!`.

**Step 2: Update `ci` target to fail fast without Task**

Edit `Makefile` `ci` target to fail if `task` is not installed and point users to `make ci-sequential`.

```make
ci: ## Run comprehensive CI checks with parallelization (excludes bats and load tests)
	@if ! command -v task >/dev/null 2>&1; then \
		echo "❌ Task is required for parallel CI. Install: sh -c \"$$(curl --location https://taskfile.dev/install.sh)\" -- -d -b ~/.local/bin"; \
		echo "➡️  Fallback: make ci-sequential"; \
		exit 1; \
	fi
	@task ci
```

**Step 3: Verify failure message**

Run: `PATH=/usr/bin:/bin make ci`

Expected: failure message with install instruction and fallback hint.

## Task 2: Rework Taskfile for Preflight + Parallel Stage

**Files:**

- Modify: `Taskfile.yaml`

**Step 1: Dry-run current task graph**

Run: `task --dry ci`

Expected: current ordering shows all groups and no explicit preflight.

**Step 2: Implement preflight and parallel stage**

Update `Taskfile.yaml` to:

- Add `preflight` task: sequential `phpcsfixer → phpmd → phpinsights`.
- Update `ci` to run `preflight` then `task --parallel ci-parallel`.
- Update `ci-parallel` to run `static-analysis`, `deptrac`, `tests`, `mutation`, `openapi` in parallel.
- Update `static-analysis` to use `task --parallel` across its checks.
- Keep `tests` sequential internally.
- Make `openapi` run `generate-openapi-spec` once, then parallel `openapi-diff`, `validate-openapi-spec`, `schemathesis-validate`.
- Add `build-spectral-docker` task and depend on it inside `validate-openapi-spec`.
- Align Schemathesis headers with Makefile: `X-Schemathesis-Test: cleanup-users`.
- Ensure success message is `✅ CI checks successfully passed!`.

Example changes:

```yaml
ci:
  cmds:
    - echo "🚀 Starting parallel CI checks..."
    - task preflight
    - task --parallel ci-parallel
    - echo ""
    - echo "✅ CI checks successfully passed!"

preflight:
  desc: Run mutating checks sequentially
  cmds:
    - task phpcsfixer
    - task phpmd
    - task phpinsights

ci-parallel:
  desc: Run CI groups in parallel
  cmds:
    - task --parallel static-analysis deptrac tests mutation openapi

static-analysis:
  desc: Run static checks in parallel
  cmds:
    - task --parallel composer-validate check-requirements check-security psalm psalm-security
```

**Step 3: Dry-run to validate ordering**

Run: `task --dry ci`

Expected: `preflight` runs first, then the parallel stage with the listed groups.

## Task 3: Align CI Skill Documentation

**Files:**

- Modify: `.claude/skills/ci-workflow/SKILL.md`

**Step 1: Update success criteria and parallelization notes**

- Restore success message to `✅ CI checks successfully passed!`.
- Document preflight + parallel stage.
- Note that `make ci` fails fast if Task is missing, and `make ci-sequential` is the fallback.

**Step 2: Verify consistency**

Check that the doc matches `Makefile` and `Taskfile.yaml` (commands, messages, and task grouping).

## Task 4: Verification

**Files:**

- Modify: `Taskfile.yaml`
- Modify: `Makefile`
- Modify: `.claude/skills/ci-workflow/SKILL.md`

**Step 1: List tasks**

Run: `task --list`

Expected:
```text
shows updated tasks and descriptions.
```

**Step 2: Run full CI**

Run: `make ci`

Expected: grouped output by task, no interleaving, and final line:

```text
✅ CI checks successfully passed!
```

**Step 3: Verify sequential fallback still works**

Run: `make ci-sequential`

Expected: sequential CI passes with the same success marker.
