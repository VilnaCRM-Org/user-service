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

## Users

- Backend developers maintaining User Service registration and authentication.
- API consumers relying on existing register-user REST and GraphQL responses.
- Reviewers enforcing DDD/CQRS consistency.

## In Scope

- Remove `getResponse()` / `setResponse()` and response state from
  `RegisterUserCommand`.
- Keep `RegisterUserCommandResponse` as the immutable DTO returned by the
  handler and guarded by API entry points.
- Add a registration lookup query handler for exact and case-insensitive email
  lookup.
- Update REST processor and GraphQL mutation resolver dispatch flow.
- Update unit tests and architecture documentation.

## Out of Scope

- Changing REST or GraphQL schemas.
- Refactoring all command response patterns in the service.
- Changing repository unique indexes or broad transaction semantics beyond the
  targeted duplicate-key failure handling needed by this refactor.
- Implementing issue #230 or other handler-return refactors.

## Success Measures

- `RegisterUserCommandHandler::__invoke()` returns
  `RegisterUserCommandResponse` and does not mutate the command.
- `RegisterUserCommand` has no response state.
- Existing register APIs keep their current public duplicate-email validation
  behavior and return newly-created users on successful registration.
- Duplicate guards do not trigger hashing, saving, or events.
- Focused unit tests pass locally.
