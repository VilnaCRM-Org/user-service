# Product Brief: Confirm Password Reset Handler Response

## Problem

`ConfirmPasswordResetCommandHandler` currently reports success by mutating its command object. This makes the command both an input message and a response carrier, which weakens CQRS clarity and makes handler behavior harder to reason about.

## Goal

Refactor the confirm-password-reset command handler so success is expressed by the handler return value.

## Users

- Backend maintainers working on authentication flows.
- Reviewers enforcing CQRS and immutability expectations.
- Future implementers of the broader command-handler response migration in issue #230.

## Scope

In scope:

- Return `ConfirmPasswordResetCommandResponse` from `ConfirmPasswordResetCommandHandler::__invoke()`.
- Remove response mutation methods from `ConfirmPasswordResetCommand`.
- Update tests and mock expectations for the new return-value contract.
- Preserve current REST and GraphQL externally observable behavior.

Out of scope:

- Refactoring all command handlers.
- Changing `CommandBusInterface` or the Symfony Messenger adapter return contract.
- Adding new endpoint behavior, schema, or database changes.

## Success Metrics

- The handler success-path unit test asserts the returned response object.
- `ConfirmPasswordResetCommand` no longer exposes `getResponse()`, `setResponse()`, or `markCompleted()`.
- Existing controller and resolver tests pass without command response mutation.
- Focused PHPUnit, Psalm, Deptrac, PHP Insights, and CI pass.
