# PRD — Security #320: Broken Object-Level Authorization / PII Enumeration

## Problem

The User API resource (`config/api_platform/resources/User.yaml`) exposes read
operations with no object-level authorization:

- REST `GET /api/users/{id}` (item `Get`) — only inherits the firewall rule
  `^/api/ -> ROLE_USER`.
- REST `GET /api/users` (`GetCollection`, up to 100/page) — uses the default
  Doctrine ODM provider that returns every user record.
- GraphQL `user(id:)` (`Query`) and `users` (`QueryCollection`) — only require
  `ROLE_USER`.

The write operations (`Patch`/`Put`/`Delete` and GraphQL `update`/`delete`)
already enforce
`is_granted('ROLE_USER') and object.getId() == user.getId().__toString()`, but
the read operations do not. The serialized `output` group
(`config/serialization/User.yaml`) exposes `id`, `email`, `initials`, and
`confirmed`.

**Impact (CWE-639, Broken Object Level Authorization):** any authenticated
`ROLE_USER` can read an arbitrary user by id and page through the entire user
base, harvesting every user's email and confirmation status. This is a
cross-tenant PII disclosure and account-enumeration primitive. No password hash
is exposed, which bounds severity to MEDIUM.

## Functional Requirements

- **FR-1 (Item read scoped to owner):** The REST item `Get` and the GraphQL
  `Query` operation MUST return a user record only when the requested object
  belongs to the authenticated caller. A `ROLE_USER` requesting another user's
  id MUST be denied (403), consistent with the existing write operations.
- **FR-2 (Collection scoped to owner):** The REST `GetCollection` and GraphQL
  `QueryCollection` operation MUST return only the authenticated caller's own
  record. They MUST NOT return other users' records, so paging cannot enumerate
  the user base.
- **FR-3 (Graceful empty collection):** When there is no authenticated
  `AuthorizationUserDto` caller, or the caller's own record no longer exists,
  the collection MUST return an empty list rather than leaking records or
  erroring.

## Non-Functional Requirements

- **NFR-Security:** Read access to user PII is restricted to the resource owner,
  matching the authorization model already enforced on write operations. No new
  PII is exposed; the enumeration and cross-tenant-read primitives are removed.
- **NFR-Compatibility:** The owner can still read their own record via item and
  collection endpoints; pagination, cache headers, and ETag behavior on the
  collection are preserved. No public/PUBLIC_ACCESS route changes. Request and
  response shapes are unchanged for legitimate self-access.
- **NFR-Maintainability:** The fix reuses existing patterns — the same security
  expression as the write operations and a single API Platform state provider in
  the Application layer (`src/User/Application/Provider/`). It respects
  hexagonal/DDD boundaries (Domain stays framework-free; no Deptrac, PHPInsights,
  or other threshold config changed) and adds focused unit tests.

## Out of Scope

- Adding an admin/elevated role that can read all users (no such role exists for
  end users today; can be introduced later behind an explicit role if
  cross-user reads become a product requirement).
- Changes to write-operation authorization (already correct).
- Field-level serialization changes to `config/serialization/User.yaml`.
- OAuth `ROLE_SERVICE` flows and batch endpoints.
