---
stepsCompleted: [init, brief]
bmalphCommand: create-brief
project_name: 'VilnaCRM User Service'
date: '2026-05-10'
---

# Product Brief - Register User CQRS Refactor

## Problem

The registration command previously doubled as an output container. This made
API return data depend on command mutation side effects instead of an explicit
command response contract.

## Outcome

Registration remains behaviorally identical for REST and GraphQL clients, while
the application code follows the intended CQRS split:

- command: request to create a user when needed
- command handler: duplicate guard, write-side creation, event publication, and
  response DTO return
- query handler: duplicate-email lookup for the handler
- processor/resolver: command dispatch and response DTO guarding
- persistence: canonical `normalizedEmail` storage with a unique partial index
  so case-variant duplicates fail consistently
- operations: safe legacy backfill with dry-run, report output, duplicate
  remediation, and rollback guidance

## Users

- Backend developers maintaining User Service registration and authentication.
- API consumers relying on existing register-user REST and GraphQL responses.
- Operators deploying MongoDB schema/index changes and running the legacy
  normalized-email backfill.
- Reviewers enforcing DDD/CQRS consistency.

## In Scope

- Remove `getResponse()` / `setResponse()` and response state from
  `RegisterUserCommand`.
- Keep `RegisterUserCommandResponse` as the immutable DTO returned by the
  handler and guarded by API entry points.
- Add a registration lookup query handler for exact and case-insensitive email
  lookup.
- Update REST processor and GraphQL mutation resolver dispatch flow.
- Add normalized-email persistence, unique partial index, repository/cache
  behavior, and guarded backfill command support required for duplicate-email
  correctness.
- Cover affected REST, OAuth, password-reset, 2FA, batch, repository, and
  backfill paths with focused automated tests.
- Update unit tests, integration tests, architecture documentation, and
  operational runbooks.

## Out of Scope

- Changing REST or GraphQL registration schemas.
- Refactoring all command response patterns in the service.
- Broad transaction semantics beyond normalized-email duplicate-key failure
  handling.
- Automated production duplicate resolution without human data-owner approval.
- Implementing issue #230 or other handler-return refactors.

## Success Measures

- `RegisterUserCommandHandler::__invoke()` returns
  `RegisterUserCommandResponse` and does not mutate the command.
- `RegisterUserCommand` has no response state.
- Existing register APIs keep their current public duplicate-email validation
  behavior and return newly-created users on successful registration.
- Duplicate guards do not trigger hashing, saving, or events.
- Real MongoDB integration tests prove the unique partial index, backfill
  behavior, and affected auth/registration ambiguity paths.
- Operational docs give deploy, dry-run/report, duplicate-resolution,
  monitoring, and rollback evidence.
- Focused unit and integration tests pass locally.
