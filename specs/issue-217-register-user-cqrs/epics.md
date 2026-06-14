---
stepsCompleted: [init, epics-stories]
bmalphCommand: create-epics-stories
project_name: 'VilnaCRM User Service'
date: '2026-05-10'
---

# Epics and Stories - Register User CQRS Refactor

## Epic 1: Separate Register-User Reads From Writes

### Story 1.1: Add email lookup query handler

Acceptance criteria:

- `FindUserByEmailQueryHandlerInterface` exists under `User/Application/Query`.
- `FindUserByEmailQueryHandler` delegates to
  `UserRepositoryInterface::findByEmail()` and
  `UserRepositoryInterface::findByEmailCaseInsensitive()`.
- Tests cover found and missing users.

### Story 1.2: Remove command response state from the command

Acceptance criteria:

- `RegisterUserCommand` has no response property or response methods.
- `RegisterUserCommandResponse` remains an immutable command response DTO for
  the created user.
- Tests cover the immutable command and returned response DTO behavior.

## Epic 2: Move API Orchestration To Processor/Resolver

### Story 2.1: Refactor REST registration processor

Acceptance criteria:

- Existing public duplicate-email validation behavior is preserved.
- Processor delegates command creation, dispatch, and response guarding through
  `RegisterUserCommandDispatcher`.
- New user dispatches once and returns the user from the command response.

### Story 2.2: Refactor GraphQL registration resolver

Acceptance criteria:

- Resolver validation behavior is preserved.
- Invalid input is validated before command dispatch.
- Resolver delegates command creation, dispatch, and response guarding through
  `RegisterUserCommandDispatcher`.
- New user dispatches once and returns the user from the command response.

## Epic 3: Simplify Command Handler And Validate

### Story 3.1: Simplify command handler

Acceptance criteria:

- Handler performs only new-user creation.
- Handler rejects duplicate emails through `FindUserByEmailQueryHandler`.
- Handler still hashes passwords, saves users, and publishes registration
  events.
- Handler returns `RegisterUserCommandResponse` for successful registration.
- Handler tests verify write-side effects and response DTO content.

### Story 3.2: Update docs and run focused checks

Acceptance criteria:

- Architecture docs describe command/query split for registration.
- Architecture docs describe normalized-email persistence, affected lookup
  callers, and safe backfill operations.
- Focused unit and integration tests pass through `make`.

## Epic 4: Enforce Case-Insensitive Email Integrity

### Story 4.1: Persist canonical normalized email values

Acceptance criteria:

- `User` exposes primitive `normalizedEmail` state without framework imports or
  validation logic.
- `MongoDBUserRepository` populates `normalizedEmail` before single and batch
  saves.
- `config/doctrine/User/User.mongodb.xml` declares a unique partial index for
  non-empty `normalizedEmail`.
- Integration tests verify real MongoDB index creation and duplicate-key
  rejection.

### Story 4.2: Preserve legacy lookup behavior during rollout

Acceptance criteria:

- Case-insensitive repository lookup finds current normalized records and
  legacy records without `normalizedEmail`.
- Multiple case-insensitive matches raise `DuplicateEmailException` through the
  shared query handler.
- Cache behavior does not persist stale negative email lookups after writes.
- Unit and integration tests cover exact, current, legacy, ambiguous, and
  whitespace/case lookup paths.

### Story 4.3: Cover affected auth and registration callers

Acceptance criteria:

- REST registration rejects a case-variant duplicate email with the existing
  validation error.
- OAuth social auto-link rejects ambiguous provider email matches with
  `409 duplicate_email` and no social identity, sign-in event, or auth cookie.
- Password reset returns a neutral 204 for ambiguous duplicate email data and
  does not create a token or email.
- 2FA setup rejects ambiguous duplicate email data without persisting a secret
  or enabling 2FA.

## Epic 5: Release Normalized-Email Backfill Safely

### Story 5.1: Add safe backfill command controls

Acceptance criteria:

- Backfill command updates missing or empty `normalizedEmail` values in batches.
- Command aborts before mutation when candidate or current-record duplicates
  are detected.
- Command supports `--dry-run` and `--report-file` for repeatable operator
  evidence.
- Unit and integration tests cover success, dry-run no-op, duplicate abort, and
  JSON report output.

### Story 5.2: Document rollout, rollback, and duplicate remediation

Acceptance criteria:

- Operational docs describe deployment order, dry-run command, report artifact,
  duplicate-resolution process, monitoring checks, rollback, and post-release
  verification.
- Human checklist template exists for production duplicate decisions and
  backfill execution evidence.
