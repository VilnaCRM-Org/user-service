---
stepsCompleted: [init, requirements]
bmalphCommand: create-prd
project_name: 'VilnaCRM User Service'
date: '2026-05-10'
---

# PRD - Register User CQRS Refactor

## Objective

Remove mutable command-response side effects from the register-user command
object while keeping REST and GraphQL registration responses unchanged. The
implementation also closes the related case-insensitive duplicate-email gap
across registration, batch registration, OAuth auto-linking, password reset,
and 2FA user lookup by introducing canonical email persistence plus a safe
legacy backfill path.

## Functional Requirements

1. `RegisterUserCommand` must contain only immutable registration input:
   `email`, `initials`, and `password`.
2. `RegisterUserCommand` must not expose `getResponse()` / `setResponse()` or
   carry mutable response state.
3. `RegisterUserCommandResponse` must remain a command response DTO wrapping the
   created `UserInterface` for API processors and GraphQL resolvers.
4. `RegisterUserCommandHandler::__invoke()` must return
   `RegisterUserCommandResponse`, create and save only new users, and publish
   `UserRegisteredEvent` only after successful persistence.
5. Existing-user lookup must be performed through a query handler, not through
   command response mutation.
6. `RegisterUserProcessor` must:
   - rely on existing public validation to reject known duplicate email
     attempts;
   - dispatch `RegisterUserCommand` through the shared command dispatcher;
   - return the user from the guarded `RegisterUserCommandResponse`.
7. `RegisterUserMutationResolver` must validate GraphQL input before dispatch,
   use the shared command dispatcher, and return the user from the guarded
   `RegisterUserCommandResponse`.
8. Tests must verify immutable command state, handler side effects, duplicate
   rejection, and command-response guarding.
9. Documentation must state that the command object does not carry response
   state while the handler may return a response DTO for API return data.
10. `User` persistence must store a canonical `normalizedEmail` value and the
    MongoDB mapping must enforce a unique partial index for non-empty
    normalized email values.
11. Repository lookup must preserve existing exact-email behavior while adding
    case-insensitive lookup for current normalized records and legacy records
    that do not yet have `normalizedEmail`.
12. The shared email query handler must treat multiple case-insensitive matches
    as duplicate ambiguity and raise `DuplicateEmailException` before callers
    create links, tokens, secrets, or sessions.
13. REST registration must reject a case-variant duplicate email with the
    existing validation error and without exposing existing account data.
14. OAuth social auto-linking must reject ambiguous duplicate local emails with
    an RFC 7807 conflict response and must not create a social identity,
    sign-in event, or auth cookie.
15. Password reset requests must keep their neutral 204 response when duplicate
    ambiguity is detected and must not create a reset token or send email.
16. 2FA setup and related authenticated 2FA lookup paths must reject ambiguous
    duplicate emails without persisting secrets, recovery codes, or enabled
    state.
17. A backfill command must populate `normalizedEmail` for legacy users, abort
    before mutation when duplicates are detected, support dry-run and JSON
    report output, and be documented with rollout, rollback, duplicate
    remediation, and operator checklist guidance.

## Non-Functional Requirements

- Preserve existing public REST and GraphQL behavior.
- Keep the original CQRS refactor focused while explicitly including the
  normalized-email persistence, repository, OAuth, password-reset, 2FA, batch
  registration, cache, Docker Mongo image, and backfill work needed to preserve
  duplicate-email correctness across affected runtime paths.
- Preserve backwards compatibility for existing MongoDB user documents that do
  not yet have `normalizedEmail`.
- Provide automated evidence for real MongoDB index creation/enforcement,
  legacy lookup fallback, backfill success/dry-run/duplicate-abort behavior,
  REST duplicate rejection, OAuth ambiguity rejection, password reset
  neutrality, and 2FA mutation safety.
- Provide operational release evidence through a documented deployment order,
  backfill dry run, report artifact, duplicate-resolution workflow,
  monitoring, and rollback plan.
- Maintain static analysis and architecture boundaries.
- Use repository validation commands through `make`.

## Acceptance Criteria

- No `getResponse()` or `setResponse()` exists on `RegisterUserCommand`.
- `RegisterUserCommandResponse` exists as an immutable DTO wrapping the created
  `UserInterface`.
- `RegisterUserCommandHandler::__invoke()` returns
  `RegisterUserCommandResponse` on success.
- Processor and resolver return `UserInterface`/`User` through the shared
  dispatcher and `CommandResponseTypeGuard`.
- Public duplicate-email registration still returns the existing validation
  error without exposing account data.
- The handler duplicate guard rejects existing emails before hashing, saving, or
  publishing.
- New-user API paths dispatch once and return the user from the command response.
- MongoDB schema creation produces a unique partial `normalizedEmail` index.
- Saving a case-variant duplicate through the repository raises
  `DuplicateEmailException`.
- Legacy users without `normalizedEmail` remain discoverable until the backfill
  is complete.
- Backfill dry-run/report, success, and duplicate-abort behavior are covered by
  automated tests.
- REST, OAuth, password reset, and 2FA ambiguous-email behavior is covered by
  integration tests.
- Focused unit and integration tests pass.

## Traceability

- Issue task: remove command response methods -> FR 1 and FR 2.
- Chosen design: retain a response DTO for API return data -> FR 3 and FR 4.
- Issue task: separate query handler -> FR 5.
- Issue task: update processor/resolver -> FR 6 and FR 7.
- Issue task: update tests/docs -> FR 8 and FR 9.
- Case-insensitive duplicate correctness across affected callers -> FR 10
  through FR 16.
- Safe normalized-email release and operations path -> FR 17.
