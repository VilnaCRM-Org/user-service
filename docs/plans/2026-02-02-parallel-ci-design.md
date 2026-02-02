# Parallel CI (Taskfile) Design

Date: 2026-02-02
Status: Approved

## Goal

Reduce `make ci` runtime by parallelizing independent checks while keeping results deterministic and readable for humans and AI agents.

## Constraints

- Commands must execute on the host via `make`, but PHP commands must run inside the `php` container.
- Preserve the existing success marker: `✅ CI checks successfully passed!`.
- Avoid race conditions from tools that modify files (e.g., `phpcsfixer`, `phpinsights --fix`).
- Keep logs readable and attributable to each task.

## Proposed Approach

Use `go-task/task` as the parallel orchestrator, invoked from `make ci` on the host. The Taskfile runs Docker commands to execute PHP tasks inside the container.

### Installation and Invocation

- `make ci` **requires** `task` to be installed on the host.
- If `task` is missing, `make ci` fails fast with a clear install message.
- A `make ci-sequential` target remains available as a fallback option.

### Logging Strategy

- Use `output: group` so each task’s output is grouped and non-interleaved.
- This is AI-friendly and makes failures easy to isolate.

## Execution Graph

### Preflight (Sequential)

These steps are sequential because they can modify files and must complete before analysis begins:

1. `phpcsfixer`
2. `phpmd`
3. `phpinsights`

### Parallel Stage

After preflight, run these in parallel:

- Static analysis: `composer-validate`, `check-requirements`, `check-security`, `psalm`, `psalm-security`
- Architecture: `deptrac`
- Tests (internal sequence): `setup-test-db → unit-tests → integration-tests → behat`
- Mutation: `infection`
- OpenAPI (internal sequence): `generate-openapi-spec`, then parallel `openapi-diff`, `validate-openapi-spec`, `schemathesis-validate`
  - `validate-openapi-spec` depends on `build-spectral-docker`.

### Success Criteria

The CI run is successful only if the final output includes:

```
✅ CI checks successfully passed!
```

## Risks and Mitigations

- **File mutation during analysis**: mitigated by running all mutating steps in the preflight sequence.
- **DB contention in tests**: tests are kept sequential in a single task with a shared DB setup step.
- **OpenAPI tool dependencies**: `generate-openapi-spec` and Spectral image build are explicit dependencies.

## Rollback Plan

- Use `make ci-sequential` to restore the original sequential behavior.
- Removing `Taskfile.yaml` reverts the repository to sequential CI without breaking other tooling.
