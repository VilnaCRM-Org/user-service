---
stepsCompleted: [research]
bmalphCommand: analyst
project_name: 'VilnaCRM User Service'
date: '2026-05-10'
---

# Research - Issue 217 Register User CQRS Refactor

## Task

Issue #217 requires removing response mutation from the register-user command
flow while preserving current REST and GraphQL registration behavior.

## Current State

- `RegisterUserCommand` carries request data and a mutable
  `RegisterUserCommandResponse`.
- `RegisterUserCommandHandler` checks `UserRepositoryInterface::findByEmail()`,
  returns an existing user by writing a response onto the command, otherwise
  creates, saves, and publishes a registration event.
- `RegisterUserProcessor` and `RegisterUserMutationResolver` dispatch the
  command and return `$command->getResponse()->createdUser`.
- Existing query style is represented by `GetUserQueryHandler` and
  `GetUserQueryHandlerInterface`, which live under `User/Application/Query`.
- The repository already exposes `findByEmail(string $email): ?UserInterface`.

## Constraints

- Commands should remain immutable data carriers.
- Command handlers should perform write-side work and return `void`.
- API Platform processors and GraphQL resolvers must still return a `User`
  object so current REST and GraphQL responses do not change.
- Existing-user registration should not publish `UserRegisteredEvent`.
- Code must respect repository guidance: use make commands for validation,
  keep Domain pure, keep class types in matching directories.

## Relevant Files

- `src/User/Application/Command/RegisterUserCommand.php`
- `src/User/Application/DTO/RegisterUserCommandResponse.php`
- `src/User/Application/CommandHandler/RegisterUserCommandHandler.php`
- `src/User/Application/Processor/RegisterUserProcessor.php`
- `src/User/Application/Resolver/RegisterUserMutationResolver.php`
- `src/User/Application/Query/GetUserQueryHandler.php`
- `src/User/Domain/Repository/UserRepositoryInterface.php`
- `tests/Unit/User/Application/Command/RegisterUserCommandTest.php`
- `tests/Unit/User/Application/CommandHandler/RegisterUserCommandHandlerTest.php`
- `tests/Unit/User/Application/Processor/RegisterUserProcessorTest.php`
- `tests/Unit/User/Application/Resolver/RegisterUserMutationResolverTest.php`
- `docs/design-and-architecture.md`

## Risks

- If the processor queries only before dispatch, a newly-created user must still
  be returned after dispatch. The safest flow is query before dispatch, dispatch
  only when missing, then query after dispatch.
- There is a small race window between the pre-check and create command.
  Duplicate-key failures should surface as duplicate-email errors without
  returning the stored user record.
- Tests that assert command responses must be rewritten or removed.

## Recommendation

Add a small `FindUserByEmailQueryHandlerInterface` and
`FindUserByEmailQueryHandler` under `User/Application/Query`. Inject it into the
processor and GraphQL resolver. Remove `RegisterUserCommandResponse`, remove
response state from `RegisterUserCommand`, and simplify the command handler to
create users only when invoked.
