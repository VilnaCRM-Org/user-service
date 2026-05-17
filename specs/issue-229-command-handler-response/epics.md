# Epics And Stories

## BMAD Context

- BMAD command surface used locally: `create-epics-stories`.

## Epic 1: Return Response Directly From Confirm Password Reset Handler

### Story 1.1: Make command input-only

As a backend maintainer, I want `ConfirmPasswordResetCommand` to carry only input data so that command state is not mutated to communicate handler results.

Acceptance criteria:

- Response property is removed.
- `getResponse()`, `setResponse()`, and `markCompleted()` are removed.
- Command construction still exposes token and new password.

### Story 1.2: Return response from handler

As a backend maintainer, I want `ConfirmPasswordResetCommandHandler` to return `ConfirmPasswordResetCommandResponse` so that success is explicit and side-effect free from the command perspective.

Acceptance criteria:

- `__invoke()` returns `ConfirmPasswordResetCommandResponse`.
- Existing password reset side effects remain in the same order.
- No command response mutation remains in the handler.

### Story 1.3: Align tests

As a reviewer, I want tests to prove the new contract without relying on command mutation.

Acceptance criteria:

- Handler success path asserts the returned response object.
- Command test verifies constructor input only.
- Controller and resolver mock callbacks no longer call `setResponse()`.
- Focused tests pass.

## Epic 2: Verify PR Readiness

### Story 2.1: Run quality gates

Acceptance criteria:

- Focused PHPUnit passes.
- Psalm, Deptrac, PHP Insights, and CI pass.
- PR has green GitHub checks and no unresolved review comments.
