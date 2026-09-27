---
stepsCompleted: [document-discovery, validation]
bmalphCommand: implementation-readiness
project_name: 'VilnaCRM User Service'
date: '2026-05-10'
---

# Implementation Readiness - Register User CQRS Refactor

## Readiness Result

Ready for implementation.

## Alignment Checks

- Research, PRD, and architecture agree on the same boundary: the command has no
  response state, the handler owns duplicate guarding and write-side creation,
  and API entry points guard the handler response DTO.
- The expanded duplicate-email correctness scope includes normalized-email
  persistence, MongoDB unique partial indexing, repository legacy fallback,
  cache invalidation behavior, OAuth auto-link ambiguity, password reset
  neutrality, authenticated 2FA lookup safety, batch registration duplicate
  handling, Docker Mongo image configuration, and a guarded backfill command.
- Stories are ordered so the query handler and command state removal happen
  before processor/resolver dispatcher refactors.
- Existing public API behavior is explicitly preserved.
- Database/schema and infrastructure work is required and in scope: add
  `normalizedEmail`, create the unique partial index, update repository
  contracts/decorators, add the backfill command, and document release
  operations before production rollout.

## Known Warnings

- The issue text references
  `src/User/Application/GraphQL/Resolver/RegisterUserResolver.php`, but the
  current code path is `src/User/Application/Resolver/RegisterUserMutationResolver.php`.
- The lookup/create race is mitigated for new writes by the MongoDB
  `normalizedEmail` unique partial index. Production duplicate resolution for
  legacy data remains an operator-controlled release step and must not be
  guessed by automation.

## Verification Plan

1. Run focused unit tests for command, query, processor, resolver, and handler.
2. Run focused integration tests for real MongoDB index behavior, backfill
   success/dry-run/duplicate abort, REST case-insensitive duplicate rejection,
   OAuth ambiguity rejection, password reset neutrality, and 2FA mutation
   safety.
3. Run formatter/static checks needed by the repository if focused tests pass.
4. Generate and attach a backfill dry-run JSON report before production rollout.
5. Run `make ci` before marking the PR ready when time and environment permit.
6. Run `make ai-review-loop` and the BMAD review gate with this spec bundle
   before moving the PR to ready.
