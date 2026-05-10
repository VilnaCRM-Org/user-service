---
stepsCompleted: [init, architecture]
bmalphCommand: create-architecture
project_name: 'VilnaCRM User Service'
date: '2026-05-10'
---

# Architecture - Register User CQRS Refactor

## Decision

Use an Application-layer query handler for email lookup and keep command
handling write-only.

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

### Command Handler

`RegisterUserCommandHandler` should:

- transform the command into a `User`;
- hash the password;
- save the user;
- publish the registration event.

It should not query for existing users or set any response data.

### Processor and Resolver

`RegisterUserProcessor` and `RegisterUserMutationResolver` should orchestrate:

1. Query by email.
2. Return existing user when found.
3. Dispatch the command when missing.
4. Query by email again and return the persisted user.

The post-dispatch query keeps the API return value on the query side while
avoiding new response DTOs or write-side return values.

## Dependency Boundaries

- Application query handler depends on Domain repository interface.
- Processor/resolver depend on Application command factory, command bus, and
  query handler interface.
- Domain layer is unchanged.
- Infrastructure repository implementations are unchanged.

## Testing Strategy

- Command test: constructor only.
- Command handler tests: verify create/save/event path; no existing-user return
  assertion remains because existing-user lookup moved out.
- Query handler tests: found and not-found cases.
- Processor tests: existing-user short circuit and new-user dispatch plus
  post-dispatch lookup.
- Resolver tests: validation/transform plus existing-user and new-user flows.

## Documentation

Update `docs/design-and-architecture.md` CQRS section to state that command
handlers perform write-side work and the register-user API return object is
resolved by query handlers in processors/resolvers.
