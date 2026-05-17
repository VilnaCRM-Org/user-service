---
stepsCompleted: [init, architecture]
bmalphCommand: create-architecture
project_name: 'VilnaCRM User Service'
date: '2026-05-10'
---

# Architecture - Register User CQRS Refactor

## Decision

Use an Application-layer query handler for email lookup, keep API entry points
delegating through a shared registration orchestrator, and let the registration
command return the idempotent registration result.

## Component Changes

### Command

`RegisterUserCommand` remains in `User/Application/Command` and becomes an
immutable data carrier:

- `email`
- `initials`
- `password`

### Query

Add `FindUserByEmailQueryHandlerInterface` and `FindUserByEmailQueryHandler`
under `User/Application/Query`.

The handler wraps `UserRepositoryInterface::findByEmail()` and returns
`?UserInterface`. Returning null is required because "not found" is a normal
registration decision, unlike `GetUserQueryHandler::handle($id)` where missing
users are exceptional.

Email normalization lives in `EmailNormalizer` so query lookup and write-side
registration share the same trim/lowercase behavior.

### Command Handler

`RegisterUserCommandHandler` should:

- normalize the command email with `EmailNormalizer`;
- return the existing user when the normalized email is already registered;
- transform the command into a `User`;
- hash the password;
- save the user;
- publish the registration event.

If persistence fails after a concurrent request wins the unique-email race, the
handler should load and return that concurrent user instead of surfacing the
database write error. It should return `RegisterUserCommandResponse` for both
existing-user and newly-created-user paths.

### Processor and Resolver

`RegisterUserProcessor` and `RegisterUserMutationResolver` delegate the shared
registration workflow to `RegisterUserOrchestrator`. The orchestrator should:

1. Create `RegisterUserCommand` from the API input.
2. Dispatch the command.
3. Use `CommandResponseTypeGuard` to require `RegisterUserCommandResponse`.
4. Return the response user.

The orchestrator does not pre-read and then post-read around dispatch, because
that sequence is racy under concurrent registrations. Idempotent existing-user
handling belongs inside the command handler where the write operation and
unique-email fallback are closest together.

## Dependency Boundaries

- Application query handler depends on Domain repository interface.
- Processor/resolver depend on `RegisterUserOrchestrator`.
- The orchestrator depends on the Application command factory, command bus, and
  command-response type guard.
- The query handler and registration command handler both use `EmailNormalizer`
  so direct reads and registration writes use the same email form.
- Domain layer is unchanged.
- The cached user repository skips writing new negative email lookups and deletes
  stale negative email-cache entries before falling back to the inner repository.

## Testing Strategy

- Command test: constructor only.
- Command response test: wraps and exposes the returned user.
- Command handler tests: verify normalized create/save/event path,
  existing-user return, concurrent unique-email race fallback, and rethrow when
  no concurrent user exists.
- Query handler tests: found and not-found cases.
- Email normalizer test: trims and lowercases ASCII and multibyte input.
- Orchestrator tests: command dispatch plus guarded command-response return.
- Processor tests: delegation to the orchestrator.
- Resolver tests: validation/transform plus delegation to the orchestrator.

## Documentation

Update `docs/design-and-architecture.md` CQRS section to state that command
handlers can return typed command responses when the write side owns the
idempotent result, while processors/resolvers still avoid duplicating command
dispatch logic by delegating to an orchestrator.
