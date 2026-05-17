---
stepsCompleted: [init, architecture]
bmalphCommand: create-architecture
project_name: 'VilnaCRM User Service'
date: '2026-05-10'
---

# Architecture - Register User CQRS Refactor

## Decision

Use an Application-layer query handler for email lookup, keep API entry points
delegating through a shared registration orchestrator, and keep the registration
command handler write-only.

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
- transform the command into a `User`;
- hash the password;
- save the user;
- publish the registration event.

If persistence fails, the repository detaches the rejected document before
rethrowing so the failed `User` is not accidentally flushed later by a reused
ODM document manager.

### Processor and Resolver

`RegisterUserProcessor` and `RegisterUserMutationResolver` delegate the shared
registration workflow to `RegisterUserOrchestrator`. The orchestrator should:

1. Use `FindUserByEmailQueryHandlerInterface` to return an existing user without
   dispatching a command.
2. Create `RegisterUserCommand` from the API input when no user exists.
3. Dispatch the command.
4. On dispatch failure, re-run the email query and return the concurrent winner
   when another request created the user; otherwise rethrow the original error.
5. After successful dispatch, run the email query again and return the persisted
   user.

The post-failure lookup keeps concurrent duplicate registrations idempotent while
preserving the command handler as the write side of the flow.

## Dependency Boundaries

- Application query handler depends on Domain repository interface.
- Processor/resolver depend on `RegisterUserOrchestrator`.
- The orchestrator depends on the Application command factory, command bus, and
  email query handler.
- The query handler and registration command handler both use `EmailNormalizer`
  so direct reads and registration writes use the same email form.
- Domain layer is unchanged.
- The cached user repository skips writing new negative email lookups and deletes
  stale negative email-cache entries before falling back to the inner repository.

## Testing Strategy

- Command test: constructor only.
- Command handler tests: verify normalized create/save/event path and rethrow
  without publishing when persistence fails.
- Query handler tests: found and not-found cases.
- Email normalizer test: trims and lowercases ASCII and multibyte input.
- Orchestrator tests: existing-user short circuit, dispatch plus post-dispatch
  lookup, concurrent winner recovery after dispatch failure, and rethrow when no
  concurrent user exists.
- Processor tests: delegation to the orchestrator.
- Resolver tests: validation/transform plus delegation to the orchestrator.

## Documentation

Update docs to state that registration reads are handled by the Application
query handler while the registration command handler remains write-only.
