# PRD: Issue 229 Command Handler Return Response

## BMAD Context

- BMAD command surface used locally: `create-prd`.
- Upstream artifacts: `research.md`, `product-brief.md`.

## Functional Requirements

1. `ConfirmPasswordResetCommandHandler::__invoke()` must return `ConfirmPasswordResetCommandResponse` on successful completion.
2. The handler must no longer call `$command->setResponse()` or `$command->markCompleted()`.
3. `ConfirmPasswordResetCommand` must represent only command input data for token and new password.
4. Existing password reset side effects must remain unchanged:
   - validate token
   - load user
   - hash and save new password
   - mark reset token as used and persist it
   - clear account lockout failures for normalized email
   - dispatch `SignOutAllCommand` with reason `password_reset`
   - publish password reset confirmation event
5. REST and GraphQL confirm-password-reset entrypoints must retain their current null/204 response behavior.

## Non-Functional Requirements

1. Keep the PR limited to issue #229.
2. Preserve static analysis and architecture boundaries.
3. Keep tests focused and deterministic.
4. Do not change API documentation or persistence because external behavior and data shape do not change.

## Acceptance Criteria

1. Handler success test asserts an instance of `ConfirmPasswordResetCommandResponse` is returned.
2. Command test verifies construction/input only and contains no response mutation assertion.
3. Controller and resolver tests dispatch the command without setting a response in mock callbacks.
4. Focused PHPUnit tests pass.
5. `make psalm`, `make deptrac`, `make phpinsights`, and `make ci` pass before PR readiness.

## Traceability

- GitHub issue #229 requested the handler signature change and removal of command-object response mutation.
- Broader command-handler response migration remains traceable to issue #230.
