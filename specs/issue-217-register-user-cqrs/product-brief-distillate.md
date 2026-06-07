---
stepsCompleted: [distillate]
bmalphCommand: create-brief
project_name: 'VilnaCRM User Service'
date: '2026-05-10'
---

# Product Brief Distillate

Refactor register-user CQRS boundaries without changing external behavior.

The command handler should reject duplicates before hashing, saving, or
publishing, then return an immutable response DTO for successful new users.
Public API validation keeps rejecting known duplicate emails for REST create
requests, while GraphQL create requests rely on the handler duplicate guard. API
entry points dispatch through the shared dispatcher and guard the command
response type. The command itself becomes an immutable write request with no
response state.
