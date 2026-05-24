---
stepsCompleted: [init, architecture]
bmalphCommand: create-architecture
project_name: 'VilnaCRM User Service'
date: '2026-05-10'
---

# Architecture - Register User CQRS Refactor

## Decision

Use an Application-layer query handler for email lookup inside the registration
command handler, keep API entry points dispatching CQRS commands through the
command bus, and return created-user data through a command response DTO.

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

The handler wraps `UserRepositoryInterface::findByEmailCaseInsensitive()` and returns
`?UserInterface`. Returning null is required because "not found" is a normal
registration decision, unlike `GetUserQueryHandler::handle($id)` where missing
users are exceptional.

Email normalization lives in `EmailNormalizer` so query lookup and write-side
registration share the same trim/lowercase behavior.

### Command Handler

`RegisterUserCommandHandler` should:

- normalize the command email with `EmailNormalizer`;
- query by email before writing and reject duplicates;
- transform the command into a `User`;
- hash the password;
- save the user;
- publish the registration event;
- return the created user in `RegisterUserCommandResponse`.

If persistence fails, the repository detaches only the failed user before
rethrowing so that failed write is not accidentally flushed later by a reused ODM
document manager, without discarding unrelated managed `User` changes.

### Processor and Resolver

`RegisterUserProcessor` and `RegisterUserMutationResolver` use
`RegisterUserCommandDispatcher` to share `RegisterUserCommand` creation,
`CommandBusInterface` dispatch, and `RegisterUserCommandResponse` validation
with `CommandResponseTypeGuard`.

The `RegisterUserCommandHandler` owns the registration write workflow:

1. Normalize the email.
2. Use `FindUserByEmailQueryHandlerInterface` as a duplicate guard before
   hashing, saving, and publishing, throwing `DuplicateEmailException` when an
   email is already registered.
3. Transform the command into a `User`, hash the password, save the user, and
   publish the registration event.
4. After successful persistence, return the created user in
   `RegisterUserCommandResponse`; do not fail the command based on a post-save
   read after write-side effects have already completed.

Single-user REST create requests keep `UniqueEmail` validation, so known
duplicate emails continue to return the existing validation error before the
public registration endpoint reaches command dispatch. GraphQL create requests
use the command handler guard as the single duplicate-email enforcement point
for that mutation. Duplicate registration still fails instead of returning
existing account data.

## Dependency Boundaries

- Application query handler depends on Domain repository interface.
- Processor/resolver depend on the Application command factory, command bus,
  and command-response guard.
- The registration command handler depends on the email query handler for
  duplicate guarding and post-save reload.
- The query handler and registration command handler both use `EmailNormalizer`
  so direct reads and registration writes use the same email form.
- Domain layer is unchanged.
- The cached user repository skips writing new negative email lookups and deletes
  stale negative email-cache entries before falling back to the inner repository.

## Testing Strategy

- Command test: constructor only.
- Command handler tests: duplicate-guard failure, normalized create/save/event
  success, save failure without publishing, and post-save reload failure.
- Query handler tests: found and not-found cases.
- Email normalizer test: trims and lowercases ASCII and multibyte input.
- Processor/resolver tests: command creation, dispatch, and response guarding.
- Resolver tests: validation/transform plus command-bus dispatch.

## Documentation

Update docs to state that registration reads are handled by the Application
query handler and registration API entry points dispatch CQRS commands directly.
