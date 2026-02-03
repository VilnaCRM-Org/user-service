---
name: ci-workflow
description: Run comprehensive CI checks before committing changes. Use when the user asks to run CI, run quality checks, validate code quality, or before finishing any task that involves code changes.
---

# CI Workflow Skill

## Context (Input)

- Code changes exist in the working directory
- Ready to validate code quality before commit/PR
- Need to ensure all quality standards are met

## Task (Function)

Execute `make ci` and ensure ALL quality checks pass with success message.

**Success Criteria**: Output ends with "✅ CI checks successfully passed!"

## Parallel Execution

The CI command uses [go-task/task](https://taskfile.dev) for parallel execution. Task runs on the **host machine** and uses `docker compose exec` to run PHP commands inside the container.

Checks run in two stages:

1. **Preflight (sequential)**: `phpcsfixer → phpmd → phpinsights`
2. **Parallel stage**: static analysis, deptrac, tests, mutation, OpenAPI validation

Parallel stage groups:

| Group              | Tasks                                                                    | Dependency              |
| ------------------ | ------------------------------------------------------------------------ | ----------------------- |
| **Static Analysis**| composer-validate, check-requirements, check-security, psalm, psalm-security | None (fully parallel) |
| **Architecture**   | deptrac                                                                  | None                    |
| **Tests**          | unit-tests, integration-tests, behat                                     | setup-test-db first     |
| **Mutation**       | infection                                                                | None                    |
| **OpenAPI**        | openapi-diff, validate-openapi-spec, schemathesis-validate               | generate-openapi-spec first |

### AI-Friendly Output

The Taskfile uses `output: group` mode, which means:
- Each task's complete output is displayed together after completion
- No interleaving of output from parallel tasks
- Error identification is straightforward - look for the failed task's grouped output

### Task Installation

Task must be installed on the host machine. If not installed, `make ci` fails fast and suggests `make ci-sequential`.

**Install Task:**
```bash
# Linux/macOS
sh -c "$(curl --location https://taskfile.dev/install.sh)" -- -d -b ~/.local/bin

# Or via package manager
brew install go-task/tap/go-task  # macOS
snap install task --classic       # Ubuntu
```

## Execution Steps

### Step 1: Run CI

```bash
make ci
```

### Step 2: Check Result

- ✅ **Success**: "✅ CI checks successfully passed!" → Task complete
- ❌ **Failure**: Task fails with grouped error output → Go to Step 3

### Step 3: Fix Failures

Identify failing check from output and apply fix:

| Check           | Command            | Fix                                 |
| --------------- | ------------------ | ----------------------------------- |
| Code style      | `make phpcsfixer`  | Apply auto-fixes                    |
| Static analysis | `make psalm`       | Fix type errors                     |
| Quality metrics | `make phpinsights` | Reduce complexity, fix architecture |
| Tests           | `make unit-tests`  | Debug failing tests                 |
| Mutations       | `make infection`   | Add missing test cases              |

### Step 4: Re-run

```bash
make ci
```

Repeat Steps 2-4 until success message appears.

## Alternative Commands

| Command              | Description                                           |
| -------------------- | ----------------------------------------------------- |
| `make ci`            | Run parallel CI (default, faster)                     |
| `make ci-sequential` | Run sequential CI (manual fallback)                   |
| `task --list`        | List all available Task targets                       |
| `task ci --dry`      | Dry run - shows execution plan without running        |
| `task preflight`     | Run mutating preflight checks only                    |
| `task ci-parallel`   | Run only parallel stage groups                        |
| `task static-analysis` | Run only static analysis group                      |
| `task tests`         | Run only test group                                   |

## Constraints (Parameters)

**NEVER decrease these thresholds**:

- min-quality: 100%
- min-complexity: 93%
- min-architecture: 100%
- min-style: 100%
- mutation MSI: 100%
- test coverage: 100%

**DO NOT**:

- Lower quality thresholds
- Skip failing checks
- Commit without "✅ CI checks successfully passed!" message
- Run commands outside Docker container (use `make` or `docker compose exec php`)

## Format (Output)

**Required final output**:

```
✅ CI checks successfully passed!
```

## Verification Checklist

- [ ] `make ci` executed
- [ ] All checks passed (composer, security, style, psalm, tests, mutations)
- [ ] Output shows "✅ CI checks successfully passed!"
- [ ] Zero test failures
- [ ] Zero escaped mutants
- [ ] No quality threshold decreased

## Rollback

If parallel execution causes issues:

1. Use `make ci-sequential` for the original sequential behavior
2. The Taskfile.yaml can be removed without affecting sequential CI
