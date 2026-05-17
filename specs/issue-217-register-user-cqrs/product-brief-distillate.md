---
stepsCompleted: [distillate]
bmalphCommand: create-brief
project_name: 'VilnaCRM User Service'
date: '2026-05-10'
---

# Product Brief Distillate

Refactor register-user CQRS boundaries without changing external behavior.

The command handler should only create and publish for new users. API
orchestration should query by email before dispatch to reject duplicate signup
attempts without exposing stored account data, and query after dispatch to return
newly-created users. The command itself becomes an immutable write request with
no response state.
