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
  `UserRepositoryInterface::findByEmail()`.
- Tests cover found and missing users.

### Story 1.2: Remove command response state

Acceptance criteria:

- `RegisterUserCommand` has no response property or response methods.
- `RegisterUserCommandResponse` is deleted.
- Tests no longer import or instantiate the deleted response class.

## Epic 2: Move API Orchestration To Processor/Resolver

### Story 2.1: Refactor REST registration processor

Acceptance criteria:

- Existing user is returned without dispatching the command.
- New user dispatches once and returns the post-dispatch lookup result.

### Story 2.2: Refactor GraphQL registration resolver

Acceptance criteria:

- Resolver validation behavior is preserved.
- Existing user is returned without dispatch.
- New user dispatches once and returns the post-dispatch lookup result.

## Epic 3: Simplify Command Handler And Validate

### Story 3.1: Simplify command handler

Acceptance criteria:

- Handler performs only new-user creation.
- Handler still hashes passwords, saves users, and publishes registration
  events.
- Handler tests verify write-side effects rather than response mutation.

### Story 3.2: Update docs and run focused checks

Acceptance criteria:

- Architecture docs describe command/query split for registration.
- Focused unit tests pass through `make`.
