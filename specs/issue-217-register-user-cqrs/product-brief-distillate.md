---
stepsCompleted: [distillate]
bmalphCommand: create-brief
project_name: 'VilnaCRM User Service'
date: '2026-05-10'
---

# Product Brief Distillate

Refactor register-user CQRS boundaries without changing external registration
behavior, while adding the normalized-email persistence and release controls
needed to make duplicate-email checks consistent across affected runtime paths.

The command handler should reject duplicates before hashing, saving, or
publishing, then return an immutable response DTO for successful new users.
Public API validation keeps rejecting known duplicate emails for REST create
requests, while GraphQL create requests rely on the handler duplicate guard. API
entry points dispatch through the shared dispatcher and guard the command
response type. The command itself becomes an immutable write request with no
response state.

The expanded implementation stores `normalizedEmail`, enforces a MongoDB unique
partial index, preserves legacy lookup fallback until backfill completion, and
adds a dry-run/reporting backfill command. Automated evidence must cover real
MongoDB index/backfill behavior plus REST, OAuth, password-reset, and 2FA
duplicate-ambiguity paths.
