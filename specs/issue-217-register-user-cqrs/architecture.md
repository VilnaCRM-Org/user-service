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
command bus, and return created-user data through a command response DTO. Add
canonical email persistence so every affected lookup path can enforce
case-insensitive duplicate-email behavior consistently.

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

The handler wraps `UserRepositoryInterface::findByEmail()` and
`UserRepositoryInterface::findByEmailCaseInsensitive()` and returns
`?UserInterface`. Returning null is required because "not found" is a normal
registration decision, unlike `GetUserQueryHandler::handle($id)` where missing
users are exceptional.

Email normalization lives in `EmailNormalizer` so query lookup and write-side
registration share the same trim/lowercase behavior.

Multiple case-insensitive matches are treated as duplicate ambiguity. The query
handler raises `DuplicateEmailException` so callers cannot choose an arbitrary
account when legacy data contains duplicate email variants.

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

OAuth social auto-linking uses the same query handler. Ambiguous local email
matches return an RFC 7807 `409 duplicate_email` response through the OAuth
exception listener, with no social identity, sign-in event, or auth cookie.

Password reset catches duplicate ambiguity and returns the existing neutral
204 response without token creation or email sending. Authenticated 2FA lookup
paths reject ambiguous email matches before persisting a TOTP secret, recovery
codes, or enabled state.

### Persistence and Backfill

`User` stores `normalizedEmail` as an Infrastructure-persisted field mapped in
`config/doctrine/User/User.mongodb.xml`. The field remains framework-free in
Domain code and is populated by the repository before persistence.

The MongoDB mapping declares a unique partial index on non-empty
`normalizedEmail` values. This preserves legacy documents without the field
until they are backfilled, while enforcing canonical uniqueness for new and
updated users.

`MongoDBUserRepository` converts duplicate-key failures on `normalizedEmail`
into `DuplicateEmailException`, including batch saves. It also supports legacy
case-insensitive fallback queries for documents missing `normalizedEmail`.

`BackfillUserNormalizedEmailsCommand` scans legacy users, detects duplicate
normalized emails across candidates and current normalized records, aborts
before mutation when duplicates exist, supports `--dry-run`, and can write a
JSON report through `--report-file`.

## Dependency Boundaries

- Application query handler depends on Domain repository interface.
- Processor/resolver depend on the Application command factory, command bus,
  and command-response guard.
- The registration command handler depends on the email query handler for
  duplicate guarding.
- The query handler and registration command handler both use `EmailNormalizer`
  so direct reads and registration writes use the same email form.
- Domain adds primitive `normalizedEmail` state only. It remains free of
  Symfony, Doctrine, API Platform, validation logic, and infrastructure imports.
- The cached user repository skips writing new negative email lookups and deletes
  stale negative email-cache entries before falling back to the inner repository.
- OAuth, password reset, user resolve, sign-in, 2FA, and batch registration use
  the shared query/repository behavior instead of each path inventing duplicate
  lookup rules.

## Testing Strategy

- Command test: constructor only.
- Command handler tests: duplicate-guard failure, normalized create/save/event
  success, save failure without publishing, and response DTO content.
- Query handler tests: found and not-found cases.
- Repository integration tests: real MongoDB unique partial index, duplicate-key
  translation, legacy lookup fallback, backfill success, dry-run no-op, and
  duplicate-abort behavior.
- REST integration test: case-insensitive duplicate registration returns the
  existing validation error.
- OAuth integration test: ambiguous auto-link returns `409 duplicate_email` and
  does not link or sign in a user.
- Password-reset and 2FA integration tests: ambiguous email data does not create
  tokens, send mail, or mutate 2FA state.
- Email normalizer test: trims and lowercases ASCII and multibyte input.
- Processor/resolver tests: command creation, dispatch, and response guarding.
- Resolver tests: validation/transform plus command-bus dispatch.

## Documentation

Update docs to state that registration reads are handled by the Application
query handler and registration API entry points dispatch CQRS commands directly.
Update operational docs with deployment order, dry-run/report command examples,
duplicate remediation, monitoring, rollback, and a human release checklist for
the normalized-email backfill.
