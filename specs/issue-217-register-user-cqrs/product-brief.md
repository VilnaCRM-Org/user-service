---
stepsCompleted: [init, brief]
bmalphCommand: create-brief
project_name: 'VilnaCRM User Service'
date: '2026-05-10'
---

# Product Brief - Register User CQRS Refactor

## Problem

The registration command currently doubles as an output container. This makes
the write side responsible for read-model return data and leaves processors and
resolvers dependent on command mutation side effects.

## Outcome

Registration remains behaviorally identical for REST and GraphQL clients, while
the application code follows the intended CQRS split:

- command: request to create a user when needed
- command handler: write-side creation and event publication
- query handler: lookup of the user to return to API Platform and GraphQL
- processor/resolver: orchestration of lookup, dispatch, and response object

## Users

- Backend developers maintaining User Service registration and authentication.
- API consumers relying on existing register-user REST and GraphQL responses.
- Reviewers enforcing DDD/CQRS consistency.

## In Scope

- Remove `RegisterUserCommandResponse`.
- Remove `getResponse()` / `setResponse()` and response state from
  `RegisterUserCommand`.
- Add a registration lookup query handler for `findByEmail`.
- Update REST processor and GraphQL mutation resolver orchestration.
- Update unit tests and architecture documentation.

## Out of Scope

- Changing REST or GraphQL schemas.
- Refactoring all command response patterns in the service.
- Changing repository persistence, unique indexes, or transaction semantics.
- Implementing issue #230 or other handler-return refactors.

## Success Measures

- `RegisterUserCommandHandler::__invoke()` returns `void` and does not mutate
  the command.
- `RegisterUserCommandResponse` no longer exists.
- Existing register APIs still return newly-created users.
- Duplicate-user registration does not return stored account data or trigger
  hashing, saving, or events.
- Focused unit tests pass locally.
