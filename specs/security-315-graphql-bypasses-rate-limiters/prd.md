# PRD — Security #315: GraphQL endpoint bypasses per-endpoint rate limiters

## Problem

The VilnaCRM user-service enforces tight per-endpoint authentication rate limits
(sign-in by IP and by email, refresh-token, 2FA verification, password-reset
confirmation) through `ApiRateLimitListener`. That listener selects which limiters
to consume by matching the **REST URL path** of the incoming request
(`ApiRateLimitRequestResolver` / `ApiRateLimitAuthTargetResolver`):

- `signin_ip` (10/min) + `signin_email` (5/min) → `POST /api/signin`
- `refresh_token` → `POST /api/token`
- `twofa_verification_ip` + `twofa_verification_user` → `POST /api/signin/2fa`
- `password_reset_confirm` → `POST /api/reset-password/confirm`
- `signout` / `signout_all` → `POST /api/signout`, `POST /api/signout/all`

API Platform also exposes the very same operations as GraphQL mutations
(`signIn`, `refreshToken`, `completeTwoFactor`, `confirmPasswordReset`, `signOut`,
`signOutAll`) served at a single endpoint, `POST /api/graphql`, which is
`PUBLIC_ACCESS`. Because `/api/graphql` matches none of the REST path checks,
`resolveEndpointLimiters` returns no endpoint limiters for GraphQL traffic and
only the loose `global_api_anonymous` limiter (~100 req/min/IP) applies.

**Impact (CWE-799, HIGH):** An attacker performs credential stuffing /
password spraying against the `signIn` GraphQL mutation, 2FA code brute-forcing
against `completeTwoFactor`, refresh-token abuse, or password-reset token guessing
against `confirmPasswordReset`, fully bypassing the dedicated per-IP and
per-identity throttles. The GraphQL path becomes a parallel, under-protected door
to the same sensitive auth actions.

## Functional requirements

- **FR-1**: Rate-limit resolution MUST treat sensitive GraphQL mutations as
  equivalent to their REST counterparts. A `POST /api/graphql` request whose
  query contains `signIn` MUST consume the `signin_ip` limiter (keyed by client IP)
  and, when an email is present, the `signin_email` limiter (keyed by lowercased
  email) — identical names/keys to `POST /api/signin`.
- **FR-2**: A GraphQL `refreshToken` mutation MUST consume the `refresh_token`
  limiter keyed by client IP, matching `POST /api/token`.
- **FR-3**: A GraphQL `completeTwoFactor` mutation MUST consume the
  `twofa_verification_ip` limiter keyed by client IP and, when a resolvable
  `pendingSessionId` maps to a user, the `twofa_verification_user` limiter keyed by
  that user id, matching `POST /api/signin/2fa`.
- **FR-4**: A GraphQL `confirmPasswordReset` mutation MUST consume the
  `password_reset_confirm` limiter keyed by client IP, matching
  `POST /api/reset-password/confirm`.
- **FR-5**: GraphQL `signOut` / `signOutAll` mutations MUST consume the
  `signout` / `signout_all` limiters keyed by the authenticated JWT subject,
  matching their REST endpoints; when the request is unauthenticated, no per-user
  limiter is emitted (the global authenticated limiter and `ROLE_USER` security
  still apply).
- **FR-6**: Non-sensitive GraphQL operations, non-POST GraphQL requests, malformed
  JSON bodies, and requests without a `query` field MUST NOT emit endpoint limiters
  (no behavioral regression, no false 429s).

## Non-functional requirements

- **NFR-Security**: The fix MUST close the bypass for every per-endpoint auth
  limiter reachable via GraphQL, reusing the exact limiter names and key formats so
  REST and GraphQL share a single throttle budget per identity/IP. The IP-keyed
  limiter MUST always apply for a recognised sensitive mutation even when identity
  arguments are absent, guaranteeing a hard security floor. Batch GraphQL requests
  remain rejected by the existing `GraphQLBatchRejectListener` (defense in depth).
- **NFR-Compatibility**: No public API contract, GraphQL schema, REST route,
  limiter configuration (`rate_limiter.yaml`), or environment variable changes.
  Existing REST throttling behavior is unchanged. No new env defaults introduced.
- **NFR-Maintainability**: The new logic lives in a single-responsibility
  `ApiRateLimitGraphQlResolver` in the existing `Resolver/RateLimit/` directory,
  consistent with the surrounding hexagonal/DDD layering (Application layer, no new
  directory, no `*Service` suffix). It reuses the established
  `ApiRateLimitClientIdentityResolver`, the serializer, and the pending-2FA
  repository port. Deptrac stays at 0 violations; PHPInsights complexity/quality/
  style thresholds are preserved.

## Out of scope

- Changing limiter rates, intervals, or policies (config untouched).
- Per-field GraphQL cost analysis / query-depth limiting beyond existing
  batch rejection.
- Throttling `requestPasswordReset` (already throttled in the command-handler
  decorator for both REST and GraphQL) and authenticated 2FA setup/confirm/disable
  management mutations, which are additionally guarded by `ROLE_USER` security.
- GraphQL query parsing via a full AST; a targeted field-name match plus standard
  `variables.input` extraction is sufficient and avoids new dependencies.
