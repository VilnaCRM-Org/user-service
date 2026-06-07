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

## Pre-Refactor State

- `RegisterUserCommand` carries request data and a mutable
  `RegisterUserCommandResponse`.
- `RegisterUserCommandHandler` checks `UserRepositoryInterface::findByEmail()`,
  writes the lookup result onto the command response when a match is found,
  otherwise creates, saves, and publishes a registration event.
- `RegisterUserProcessor` and `RegisterUserMutationResolver` dispatch the
  command and return `$command->getResponse()->createdUser`.
- Existing query style is represented by `GetUserQueryHandler` and
  `GetUserQueryHandlerInterface`, which live under `User/Application/Query`.
- The repository already exposes `findByEmail(string $email): ?UserInterface`.

## Constraints

- Commands should remain immutable data carriers.
- Command handlers should perform write-side work and may return immutable
  `CommandResponseInterface` DTOs when API callers need created-resource data.
- API Platform processors and GraphQL resolvers must still return a `User`
  object on successful registration so current REST and GraphQL responses do not
  change.
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

- A handler-returned response DTO keeps the created user available to REST and
  GraphQL without storing response state on `RegisterUserCommand`.
- There is a small race window between validation, the pre-check, and the create
  command. Known public duplicates remain validation errors; duplicate-key
  failures inside an accepted registration flow should be translated into
  duplicate-email exceptions.
- Tests that assert mutable command responses must be rewritten.

## Recommendation

Add a small `FindUserByEmailQueryHandlerInterface` and
`FindUserByEmailQueryHandler` under `User/Application/Query`. Inject it into the
command handler for duplicate guarding. Remove response state from
`RegisterUserCommand`, keep `RegisterUserCommandResponse` as the immutable
handler return DTO, and have processors/resolvers dispatch through a shared
dispatcher that guards the response type.
