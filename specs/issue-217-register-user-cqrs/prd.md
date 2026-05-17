---
stepsCompleted: [init, requirements]
bmalphCommand: create-prd
project_name: 'VilnaCRM User Service'
date: '2026-05-10'
---

# PRD - Register User CQRS Refactor

## Objective

Remove response side effects from the register-user command path while keeping
REST and GraphQL registration responses unchanged.

## Functional Requirements

1. `RegisterUserCommand` must contain only immutable registration input:
   `email`, `initials`, and `password`.
2. `RegisterUserCommandResponse` must be removed from production code and tests.
3. `RegisterUserCommandHandler::__invoke()` must return `void`, create and save
   only new users, and publish `UserRegisteredEvent` only for new users.
4. Existing-user lookup must be performed through a query handler, not through
   command response mutation.
5. `RegisterUserProcessor` must:
   - short-circuit duplicate email attempts without dispatching a command;
   - return the existing user for duplicate registrations;
   - dispatch `RegisterUserCommand` when missing;
   - return the persisted user after dispatch.
6. `RegisterUserMutationResolver` must follow the same lookup/dispatch/return
   orchestration as the REST processor.
7. Tests must verify state changes and collaborator calls rather than command
   response values.
8. Documentation must state that write commands do not carry response payloads
   in the register-user flow.

## Non-Functional Requirements

- Preserve existing public REST and GraphQL behavior.
- Keep changes focused to registration and direct documentation/tests.
- Maintain static analysis and architecture boundaries.
- Use repository validation commands through `make`.

## Acceptance Criteria

- No production or test references to `RegisterUserCommandResponse` remain.
- No `getResponse()` or `setResponse()` exists on `RegisterUserCommand`.
- Processor and resolver return `UserInterface`/`User` through query lookup.
- Duplicate-email path avoids command dispatch and returns the existing user
  record through read-side lookup.
- New-user path dispatches once and performs a post-dispatch lookup.
- Focused unit tests pass.

## Traceability

- Issue task: remove command response class -> FR 2.
- Issue task: remove command response methods -> FR 1.
- Issue task: handler returns void -> FR 3.
- Issue task: separate query handler -> FR 4.
- Issue task: update processor/resolver -> FR 5 and FR 6.
- Issue task: update tests/docs -> FR 7 and FR 8.
