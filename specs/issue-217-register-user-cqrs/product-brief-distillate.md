---
stepsCompleted: [distillate]
bmalphCommand: create-brief
project_name: 'VilnaCRM User Service'
date: '2026-05-10'
---

# Product Brief Distillate

Refactor register-user CQRS boundaries without changing external behavior.

The command handler should only create and publish for new users. Public API
validation keeps rejecting known duplicate emails for REST create requests, while
GraphQL create requests rely on API orchestration as their single duplicate
enforcement point. API orchestration still queries by email before dispatch as
the CQRS replacement for command response mutation and queries after dispatch to
return newly-created users. The command itself becomes an immutable write request
with no response state.
