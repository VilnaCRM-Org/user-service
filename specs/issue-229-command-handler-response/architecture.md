# Architecture: Issue 229 Command Handler Return Response

## BMAD Context

- BMAD command surface used locally: `create-architecture`.
- Relevant repository architecture: Symfony 7, API Platform, DDD, CQRS, hexagonal Application layer.

## Current Design

`ConfirmPasswordResetCommand` currently mixes command input with response state. `ConfirmPasswordResetCommandHandler` mutates that state through `markCompleted()`.

## Target Design

`ConfirmPasswordResetCommandHandler` returns a `ConfirmPasswordResetCommandResponse` directly after completing all side effects. `ConfirmPasswordResetCommand` keeps only input fields.

```php
public function __invoke(
    ConfirmPasswordResetCommand $command
): ConfirmPasswordResetCommandResponse
```

## Boundary Decisions

- `ConfirmPasswordResetCommandResponse` remains in `App\User\Application\DTO`.
- The command stays in `App\User\Application\Command`.
- The handler stays in `App\User\Application\CommandHandler`.
- `CommandBusInterface::dispatch()` remains `void` in this PR because no current confirm-password-reset caller needs the returned response and changing the bus is the broader issue #230 scope.

## Compatibility

- Symfony Messenger handlers may return values, but this application command bus currently ignores them. That is acceptable for issue #229 because the external REST/GraphQL flow does not consume the response.
- Direct unit tests of the handler can assert the returned response.

## Test Strategy

- Update handler unit tests to assert return value on success.
- Update command unit tests to remove response mutation behavior.
- Update controller/resolver tests so command dispatch callbacks validate command fields only.
- Run focused tests for command, handler, controller, resolver.
- Run static quality gates before committing.

## Future Work

Issue #230 can introduce a typed command bus return mechanism and migrate all remaining command handlers.
