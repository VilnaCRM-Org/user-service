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
- Exact email lookup alone does not protect all callers from case-variant
  duplicate accounts. Registration, batch registration, OAuth social
  auto-linking, password reset, sign-in, user resolution, and 2FA management all
  depend on email lookup semantics.
- Existing MongoDB users may not have canonical `normalizedEmail` state, so the
  rollout must support legacy fallback until a guarded backfill has run.

## Constraints

- Commands should remain immutable data carriers.
- Command handlers should perform write-side work and may return immutable
  `CommandResponseInterface` DTOs when API callers need created-resource data.
- API Platform processors and GraphQL resolvers must still return a `User`
  object on successful registration so current REST and GraphQL responses do not
  change.
- Existing-user registration should not publish `UserRegisteredEvent`.
- Ambiguous duplicate email data must fail closed for auth/linking flows and
  must not create tokens, social identities, 2FA secrets, or events.
- Legacy duplicate resolution is a human data-owner decision; automation may
  report conflicts but must not merge or delete accounts.
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
- `src/User/Infrastructure/Repository/MongoDBUserRepository.php`
- `src/User/Infrastructure/Repository/CachedUserRepository.php`
- `src/User/Infrastructure/Command/BackfillUserNormalizedEmailsCommand.php`
- `config/doctrine/User/User.mongodb.xml`
- `src/OAuth/Application/Resolver/OAuthUserResolver.php`
- `src/User/Application/CommandHandler/RequestPasswordResetCommandHandler.php`
- `src/User/Application/CommandHandler/SetupTwoFactorCommandHandler.php`
- `tests/Unit/User/Application/Command/RegisterUserCommandTest.php`
- `tests/Unit/User/Application/CommandHandler/RegisterUserCommandHandlerTest.php`
- `tests/Unit/User/Application/Processor/RegisterUserProcessorTest.php`
- `tests/Unit/User/Application/Resolver/RegisterUserMutationResolverTest.php`
- `tests/Integration/User/Infrastructure/Repository/*NormalizedEmail*`
- `tests/Integration/Auth/*Ambiguity*`
- `docs/design-and-architecture.md`
- `docs/operational.md`

## Risks

- A handler-returned response DTO keeps the created user available to REST and
  GraphQL without storing response state on `RegisterUserCommand`.
- There is a small race window between validation, the pre-check, and the create
  command. Known public duplicates remain validation errors; duplicate-key
  failures inside an accepted registration flow should be translated into
  duplicate-email exceptions.
- Adding `normalizedEmail` and a unique partial index protects new writes but
  creates release risk if production already contains case-variant duplicate
  users. The backfill must run in dry-run/report mode first and abort before
  mutation when duplicates exist.
- OAuth auto-linking must not pick one local user when the provider email
  matches multiple local accounts.
- Password reset and 2FA paths must fail without account enumeration or state
  mutation when duplicate ambiguity is detected.
- Tests that assert mutable command responses must be rewritten.

## Recommendation

Add a small `FindUserByEmailQueryHandlerInterface` and
`FindUserByEmailQueryHandler` under `User/Application/Query`. Inject it into the
command handler for duplicate guarding. Remove response state from
`RegisterUserCommand`, keep `RegisterUserCommandResponse` as the immutable
handler return DTO, and have processors/resolvers dispatch through a shared
dispatcher that guards the response type.

Add canonical `normalizedEmail` persistence with a unique partial MongoDB index,
legacy case-insensitive fallback lookup, duplicate-key translation, and a
dry-run/reporting backfill command. Verify the expanded behavior with real
MongoDB integration tests and document the deployment order, duplicate
remediation process, monitoring, and rollback steps before production rollout.
