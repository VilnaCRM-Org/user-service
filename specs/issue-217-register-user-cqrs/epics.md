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
- Focused unit tests pass through `make`.
