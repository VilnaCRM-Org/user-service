# Issue 229 Research

## BMAD Context

- BMALPH CLI preflight: `make bmalph-setup BMALPH_PLATFORM=codex`, `bmalph doctor`, and `bmalph status`.
- BMAD command surface used locally: `analyst`.
- Planning bundle: `specs/issue-229-command-handler-response/`.

## Issue Summary

GitHub issue #229 asks to refactor `ConfirmPasswordResetCommandHandler` so `__invoke()` returns a dedicated response object instead of mutating `ConfirmPasswordResetCommand` through `setResponse()`.

## Current State

- `ConfirmPasswordResetCommandHandler::__invoke()` returns `void`.
- Successful completion currently calls `$command->markCompleted()`, which creates a `ConfirmPasswordResetCommandResponse` inside the command.
- `ConfirmPasswordResetCommand` owns response state through `getResponse()`, `setResponse()`, and `markCompleted()`.
- `ConfirmPasswordResetController` and `ConfirmPasswordResetMutationResolver` dispatch the command but do not read the command response.
- Existing tests assert response mutation in:
  - `tests/Unit/User/Application/Command/ConfirmPasswordResetCommandTest.php`
  - `tests/Unit/User/Application/CommandHandler/ConfirmPasswordResetCommandHandlerTest.php`
  - test callbacks in controller/resolver tests that set a response even though production code does not read it.

## Constraints

- Keep issue #229 scoped to the single handler named by the issue.
- Do not change `CommandBusInterface`; that broader migration belongs to issue #230.
- Do not alter password reset side effects: token validation, password hashing, token persistence, lockout clearing, session revocation, and event publishing.
- Keep DDD/CQRS boundaries: command remains an input message, response DTO remains in Application DTO.

## Implementation Surface

- `src/User/Application/CommandHandler/ConfirmPasswordResetCommandHandler.php`
- `src/User/Application/Command/ConfirmPasswordResetCommand.php`
- `tests/Unit/User/Application/Command/ConfirmPasswordResetCommandTest.php`
- `tests/Unit/User/Application/CommandHandler/ConfirmPasswordResetCommandHandlerTest.php`
- `tests/Unit/User/Application/Controller/ConfirmPasswordResetControllerTest.php`
- `tests/Unit/User/Application/Resolver/ConfirmPasswordResetMutationResolverTest.php`

## Risks

- Changing the shared command bus return contract would expand the PR beyond #229.
- Removing response methods from this command requires updating tests that used response mutation only as a mock artifact.
- Future issue #230 must still handle the remaining command handlers that mutate commands.
