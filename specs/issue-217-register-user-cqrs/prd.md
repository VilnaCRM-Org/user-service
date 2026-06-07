---
stepsCompleted: [init, requirements]
bmalphCommand: create-prd
project_name: 'VilnaCRM User Service'
date: '2026-05-10'
---

# PRD - Register User CQRS Refactor

## Objective

Remove mutable command-response side effects from the register-user command
object while keeping REST and GraphQL registration responses unchanged.

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

## Non-Functional Requirements

- Preserve existing public REST and GraphQL behavior.
- Keep changes focused to registration and direct documentation/tests.
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
- Focused unit tests pass.

## Traceability

- Issue task: remove command response methods -> FR 1 and FR 2.
- Chosen design: retain a response DTO for API return data -> FR 3 and FR 4.
- Issue task: separate query handler -> FR 5.
- Issue task: update processor/resolver -> FR 6 and FR 7.
- Issue task: update tests/docs -> FR 8 and FR 9.
