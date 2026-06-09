# PRD — Security #317: Schemathesis cleanup listener can delete users in production

## Problem

`SchemathesisCleanupListener` is a `kernel.terminate` listener built solely for the
Schemathesis CI fuzzing harness: after a successful create on `/api/users` or
`/api/users/batch` carrying the header `X-Schemathesis-Test: cleanup-users`, it decodes the
raw request body, extracts every `email`, looks each up via
`UserRepositoryInterface::findByEmail()`, and `deleteBatch()`-deletes the matched accounts
(plus fires `UserDeleted` events and invalidates cache).

The listener was registered in the **base** `config/services.yaml` with **no environment
guard** (no `when@` block, no env-specific file, no runtime `APP_ENV` check anywhere in the
`SchemathesisCleanupListener` / `SchemathesisCleanupResolver` / `SchemathesisEmailResolver`
chain). It was therefore loaded and active in **production**.

Because batch registration is idempotent (`BatchUserRegistrationFactory::registerUser`
returns an existing user instead of erroring, and `RegisterUserBatchProcessor` still returns
`201 CREATED`), a caller holding a `ROLE_SERVICE` token could POST `/api/users/batch` with the
cleanup header and a body listing **already-existing victim emails**. The response is `201`,
the matcher fires, and the listener permanently deletes those victim accounts. This turns an
authorized "create users" endpoint into an arbitrary "delete users by email" primitive that is
invisible from the operation contract. (CWE-285 — Improper Authorization. HIGH.)

## Functional Requirements

- **FR-1**: The Schemathesis cleanup listener MUST NOT be registered as a service or event
  listener in the `dev`, `test`, `load_test`, or `prod` environments. It MAY only be registered
  in the dedicated `schemathesis` environment used by the CI fuzzing harness.
- **FR-2**: Even if the listener were instantiated and dispatched in a non-`schemathesis`
  environment, its `__invoke` MUST no-op (perform no `findByEmail`, `deleteBatch`, event
  publish, or cache invalidation) — i.e. it MUST fail closed at runtime, not rely solely on a
  request header.
- **FR-3**: In the `schemathesis` environment the listener MUST retain its existing cleanup
  behavior unchanged (delete the users named in the request body after a successful create on a
  handled path with the cleanup header).

## Non-Functional Requirements

- **NFR-Security**: Destructive user-deletion machinery MUST be unreachable from any
  production-facing request path. The fix applies defense in depth: (1) the listener is excluded
  from base autowiring and only declared in `config/services_schemathesis.yaml`; (2) a hard
  runtime `appEnv === 'schemathesis'` guard. Neither layer alone is relied upon. No new secrets,
  routes, or auth scopes are introduced.
- **NFR-Compatibility**: No public API contract, route, request/response shape, security policy,
  or database schema changes. CI Schemathesis runs in the `schemathesis` environment, so cleanup
  continues to work there. Constructor signature change is internal-only (DI-wired service).
- **NFR-Maintainability**: Changes follow existing repository conventions — the `$appEnv:
  '%kernel.environment%'` injection idiom already used by
  `TwoFactorEncryptionKeyConfigurationListener` / `OAuthEncryptionKeyConfigurationListener`, and
  the env-specific `services_<env>.yaml` loading convention of `MicroKernelTrait`. No new
  directories, no `*Service` suffixes, no Deptrac/threshold config edits, hexagonal boundaries
  preserved (guard lives in the Infrastructure listener).

## Out of Scope

- Changing batch-registration idempotency (existing 201-on-existing behavior of
  `BatchUserRegistrationFactory` / `RegisterUserBatchProcessor`) — that is intended API behavior
  and altering it would change the public contract broadly. The deletion primitive is fully
  neutralized by ensuring the listener cannot run outside `schemathesis`.
- Replacing the header-based trigger with an authenticated cleanup route — unnecessary once the
  listener is confined to the test environment.
- Broader audit of other test-only listeners.
