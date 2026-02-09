---
stepsCompleted:
  [
    init,
    executive-summary,
    success-criteria,
    scope,
    journeys,
    domain-requirements,
    functional-requirements,
    non-functional-requirements,
  ]
inputDocuments:
  [
    CLAUDE.md,
    config/packages/security.yaml,
    config/packages/rate_limiter.yaml,
    config/api_platform/resources/User.yaml,
  ]
workflowType: 'prd'
project_name: 'VilnaCRM User Service — Auth Sign-in + 2FA'
author: 'Valerii'
date: '2026-02-05'
revision: '5 — TEA Party Mode R3 Multi-Model Adversarial Review'
---

# Product Requirements Document — Auth Sign-in + 2FA

**Author:** Valerii
**Date:** 2026-02-05
**Revision:** 4 — TEA Party Mode R2 Deep Security Pass (addresses R1 13 critical + R2 4 critical gaps)

## Executive Summary

The VilnaCRM User Service requires a cohesive sign-in flow with optional TOTP-based two-factor authentication, session cookies for web clients, JWT issuance for stateless clients, and refresh token rotation with grace window. A critical gap exists: the current service has no authentication enforcement on protected API endpoints (`security: false` on the Symfony firewall) and no global rate limiting beyond password reset. This PRD defines the full scope including security hardening to bring the service to audit-ready status.

**Differentiator:** Unified cookie + JWT auth with optional 2FA (including recovery codes), built on hexagonal architecture with zero coupling between auth logic and domain entities.

**Target users:** Web clients (cookie-based), mobile/third-party clients (JWT-based), users opting into TOTP 2FA.

## Success Criteria

| ID    | Criterion                             | Measurement                                                                                  | Target        |
| ----- | ------------------------------------- | -------------------------------------------------------------------------------------------- | ------------- |
| SC-01 | Sign-in latency (no 2FA)              | P95 response time measured by APM                                                            | < 300ms       |
| SC-02 | Sign-in latency (with 2FA completion) | P95 response time across both requests                                                       | < 500ms total |
| SC-03 | Token refresh latency                 | P95 response time                                                                            | < 100ms       |
| SC-04 | Auth gate rejection accuracy          | Percentage of unauthenticated requests to protected endpoints returning 401                  | 100%          |
| SC-05 | 2FA enforcement                       | Percentage of 2FA-enabled accounts that never receive tokens without code confirmation       | 100%          |
| SC-06 | Rate limit effectiveness              | Percentage of abusive requests (exceeding threshold) that receive 429                        | 100%          |
| SC-07 | Test coverage                         | Line coverage for new auth flows (unit + integration + Behat)                                | >= 90%        |
| SC-08 | Mutation score                        | Infection MSI for new auth code                                                              | >= 80%        |
| SC-09 | CI pass                               | `make ci` completes with zero failures                                                       | Pass          |
| SC-10 | Audit logging coverage                | Percentage of auth events (sign-in, 2FA, logout, token rotation) with structured log entries | 100%          |

## Product Scope

### MVP (This PR)

- Sign-in endpoint with email/password
- 2FA pending session and code confirmation flow
- 2FA recovery codes (8 single-use codes generated on 2FA enable)
- 2FA disable flow (requires current TOTP code or recovery code)
- Refresh-only token exchange with rotation and grace window
- Logout (current session) and sign-out-everywhere endpoints
- Authentication gate enforcing bearer/cookie auth on all protected endpoints
- TOTP 2FA setup and confirmation for authenticated users
- Multi-tier rate limiting (global, per-endpoint, per-account) — all endpoints covered
- Security headers on all responses
- Symfony firewall enabled with OAuth2 authenticator
- Password grant deprecation (disabled after sign-in endpoint ships)
- GraphQL ownership enforcement parity with REST
- GraphQL query depth and complexity limits
- Bcrypt cost upgrade to >= 12
- Structured audit logging for all auth events
- Password change invalidates all other sessions
- 2FA enablement invalidates all other sessions
- JWT claims validation (iss, aud, nbf, sid) and reduced access token TTL (15 min)
- Constant-time credential validation (timing-safe email enumeration prevention)
- `__Host-` cookie prefix for subdomain attack prevention (`Path=/`, no `Domain` attribute)
- Account lockout after repeated failed sign-in attempts

### Growth (Future)

- Immediate JWT revocation via `jti` denylist (Redis-backed) — eliminates 15-min revocation window
- Email notifications on security-sensitive actions (2FA enable/disable, password change, recovery code used)
- WebAuthn/FIDO2 as a 2FA method
- Device fingerprinting and trusted device management
- Admin-managed 2FA for other users
- Suspicious login detection and notification (enabled by IP tracking on sessions)
- Adaptive rate limiting based on threat intelligence
- 2FA encryption key rotation strategy
- Token fingerprinting for bearer tokens (JWT `fgp` claim + `__Secure-Fgp` cookie) — prevents sidejacking
- CAPTCHA/proof-of-work challenge for sign-in after 3 consecutive failures per email
- Password breach database validation via Symfony `NotCompromisedPassword` constraint
- Email notifications on security-sensitive actions (2FA, password change, recovery code used)

### Vision (Future)

- Passwordless authentication (magic links, passkeys)
- Adaptive authentication based on risk scoring
- SSO federation via SAML/OIDC

## User Journeys

### UJ-01: Standard Login (No 2FA)

1. User submits email + password to `POST /api/signin`.
2. System validates credentials against bcrypt hash.
3. System issues access token (JWT), refresh token, and sets session cookie (`__Host-auth_token`, `HttpOnly`, `Secure`, `SameSite=Lax`, `Path=/`, no `Domain` attribute).
4. User accesses protected endpoints using bearer token or session cookie.

### UJ-02: Login with 2FA

1. User submits email + password to `POST /api/signin`.
2. System validates credentials, detects 2FA enabled.
3. System returns `pending_session_id` (no tokens, no cookie).
4. User submits `pending_session_id` + TOTP code to `POST /api/signin/2fa`.
5. System validates code against stored secret, issues tokens + cookie.

### UJ-03: Token Refresh

1. Client detects access token expiration.
2. Client submits refresh token to `POST /api/token`.
3. System validates refresh token, issues new access + refresh tokens.
4. Old refresh token marked as rotated (grace window: 60s default).

### UJ-04: 2FA Setup

1. Authenticated user requests `POST /api/users/2fa/setup`.
2. System generates TOTP secret, returns `otpauth_uri` and `secret`.
3. User scans QR code with Google Authenticator.
4. User submits confirmation code to `POST /api/users/2fa/confirm`.
5. System verifies code, flips `twoFactorEnabled` to true.
6. System generates 8 single-use recovery codes and returns them.
7. System revokes all existing sessions except the current one (prevents pre-2FA compromised sessions from persisting).

### UJ-05: Unauthorized Access Attempt

1. Unauthenticated client requests `GET /api/users`.
2. Auth gate intercepts, finds no valid bearer token or session cookie.
3. System returns 401 RFC 7807 problem+json.

### UJ-06: Rate-Limited Abuse Attempt

1. Attacker sends 20 registration requests in 10 seconds from same IP.
2. After 5th request, rate limiter rejects with 429 + `Retry-After` header.

### UJ-07: Logout

1. Authenticated user submits `POST /api/signout`.
2. System revokes current AuthSession and all associated refresh tokens.
3. Session cookie is cleared via `Set-Cookie` with `Max-Age=0`.
4. Existing JWT access token becomes invalid within 15 minutes (token TTL expiry). Refresh tokens are immediately revoked.

### UJ-08: Sign Out Everywhere

1. Authenticated user submits `POST /api/signout/all`.
2. System revokes ALL AuthSessions and refresh tokens for the user.
3. All devices/clients receive 401 on next request.

### UJ-09: 2FA Disable

1. Authenticated user with 2FA enabled submits `POST /api/users/2fa/disable` with a valid TOTP code.
2. System verifies the code, sets `twoFactorEnabled` to false, clears `twoFactorSecret`.
3. Recovery codes are invalidated.
4. Next sign-in proceeds without 2FA step.

### UJ-10: 2FA Recovery (Lost Device)

1. User signs in with email + password, receives `pending_session_id`.
2. User submits `pending_session_id` + recovery code to `POST /api/signin/2fa`.
3. System validates recovery code (single-use), issues tokens + cookie.
4. Recovery code is marked as used.
5. User is warned to regenerate recovery codes if running low.

### UJ-11: Password Change Invalidates Sessions

1. Authenticated user changes password via `PATCH /api/users/{id}` with `newPassword`.
2. System hashes new password, revokes all sessions EXCEPT the current one.
3. All other devices/clients receive 401 on next request.

## Domain Requirements

### API Security (OWASP API Security Top 10)

- DR-01: Broken Object Level Authorization — Write/delete operations enforce ownership (token subject == resource ID) on both REST and GraphQL.
- DR-02: Broken Authentication — Credentials validated with bcrypt (cost >= 12) using constant-time comparison, tokens signed with RS256 (algorithm pinned), JWT claims validated (iss, aud, nbf), refresh tokens rotated. Password grant disabled.
- DR-03: Unrestricted Resource Consumption — Multi-tier rate limiting on ALL endpoints, GraphQL query depth and complexity limits.
- DR-04: Broken Function Level Authorization — Batch operations require elevated `ROLE_SERVICE` scope.
- DR-05: Security Misconfiguration — Security headers enforced, GraphQL introspection disabled in production, test environment not exposed.

### Data Protection

- DR-06: Refresh tokens stored as hashed values (SHA-256), never plaintext.
- DR-07: 2FA secrets encrypted at application level before persistence.
- DR-08: Session cookies use `__Host-` prefix, `HttpOnly`, `Secure`, `SameSite=Lax`, `Path=/`, and no `Domain` attribute.
- DR-09: Recovery codes stored as hashed values (SHA-256), never plaintext.
- DR-10: Confirmation tokens are at least 32 characters long.

### Audit Trail

- DR-11: All authentication events (sign-in, 2FA, logout, token rotation, theft detection) emit structured log entries with IP, user-agent, user ID, event type, and result.
- DR-12: Refresh token theft detection (grace window violation) emits a high-severity alert.

## Functional Requirements

| ID    | Requirement                                                                                                                                   | Source       | Priority |
| ----- | --------------------------------------------------------------------------------------------------------------------------------------------- | ------------ | -------- |
| FR-01 | Users can sign in with email and password, receiving an access token, refresh token, and session cookie                                       | UJ-01        | P0       |
| FR-02 | Users with 2FA enabled receive a `pending_session_id` instead of tokens on sign-in                                                            | UJ-02        | P0       |
| FR-03 | Users can complete 2FA by submitting pending session ID and TOTP code (or recovery code), receiving tokens and cookie                         | UJ-02, UJ-10 | P0       |
| FR-04 | Clients can exchange a valid refresh token for new access and refresh tokens                                                                  | UJ-03        | P0       |
| FR-05 | A rotated refresh token can be reused once within the grace window (configurable, default 60s)                                                | UJ-03        | P0       |
| FR-06 | Reuse of a rotated token after grace window (or second reuse within grace) revokes the session and returns 401                                | UJ-03        | P0       |
| FR-07 | Authenticated users can generate a TOTP secret and otpauth URI for 2FA setup                                                                  | UJ-04        | P0       |
| FR-08 | Authenticated users can confirm 2FA setup by submitting a valid TOTP code; system generates recovery codes                                    | UJ-04        | P0       |
| FR-09 | All protected endpoints reject unauthenticated requests with 401 problem+json                                                                 | UJ-05        | P0       |
| FR-10 | Sign-in, 2FA completion, token refresh, OAuth, health, registration, password reset, and email confirmation endpoints are publicly accessible | UJ-01, UJ-05 | P0       |
| FR-11 | Batch user creation requires `ROLE_SERVICE` scope                                                                                             | DR-04        | P0       |
| FR-12 | Write/delete operations on user resources enforce ownership on both REST and GraphQL                                                          | DR-01        | P0       |
| FR-13 | Authenticated users can revoke their current session (logout), invalidating refresh tokens and session cookie                                 | UJ-07        | P0       |
| FR-14 | Authenticated users can revoke ALL their sessions (sign out everywhere)                                                                       | UJ-08        | P0       |
| FR-15 | Authenticated users can disable 2FA by confirming with a valid TOTP code or recovery code                                                     | UJ-09        | P0       |
| FR-16 | When 2FA is confirmed, the system generates 8 single-use recovery codes returned to the user                                                  | UJ-04        | P0       |
| FR-17 | Recovery codes can be used in place of TOTP codes during 2FA sign-in completion                                                               | UJ-10        | P0       |
| FR-18 | Authenticated users can regenerate recovery codes, invalidating all previous codes                                                            | UJ-10        | P1       |
| FR-19 | Password change revokes all sessions except the current one                                                                                   | UJ-11        | P0       |
| FR-20 | 2FA enablement (confirmation) revokes all sessions except the current one                                                                     | UJ-04        | P0       |

## Non-Functional Requirements

### Performance

| ID     | Requirement                                                                    | Measurement           |
| ------ | ------------------------------------------------------------------------------ | --------------------- |
| NFR-01 | Sign-in (no 2FA) responds in under 300ms for 95th percentile under normal load | APM P95 latency       |
| NFR-02 | Token refresh responds in under 100ms for 95th percentile                      | APM P95 latency       |
| NFR-03 | Auth gate adds less than 5ms overhead per request                              | APM middleware timing |

### Security — Authentication and Authorization

| ID     | Requirement                                                                                                                                | Measurement                                                                      |
| ------ | ------------------------------------------------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------- |
| NFR-04 | Symfony security firewall enabled with OAuth2 bearer token validation on all `/api/` and `/api/graphql` routes                             | Integration test: unauthenticated request to each protected endpoint returns 401 |
| NFR-05 | Access tokens expire after 15 minutes (configurable via `OAUTH_ACCESS_TOKEN_TTL`); reduced from 1h to limit JWT revocation window          | Token decode verification                                                        |
| NFR-06 | Refresh tokens expire after 1 month (configurable via `OAUTH_REFRESH_TOKEN_TTL`)                                                           | Database TTL field validation                                                    |
| NFR-07 | TOTP verification allows +/- 1 time window for clock skew tolerance                                                                        | Unit test with time-shifted codes                                                |
| NFR-31 | Password change revokes all sessions except the current one                                                                                | Integration test                                                                 |
| NFR-32 | Password hashing uses bcrypt with cost >= 12 (existing passwords re-hashed on next login via `migrate_from`)                               | Config validation                                                                |
| NFR-38 | JWT validation pins the expected algorithm (RS256) and rejects tokens signed with any other algorithm                                      | Unit test with HS256-signed token                                                |
| NFR-40 | CORS configured with `Access-Control-Allow-Credentials: true` and explicit origin (not wildcard) for cookie-based auth                     | Integration test                                                                 |
| NFR-41 | OAuth password grant type disabled after sign-in endpoint ships; clients must use `POST /api/signin`                                       | Integration test: password grant returns error                                   |
| NFR-50 | JWT access tokens include claims: `sub`, `iss`, `aud`, `exp`, `iat`, `nbf`, `jti`, `sid` (session ID), `roles`                             | Unit test: decode JWT and verify all claims present                              |
| NFR-51 | JWT validation verifies `iss` (single string, not array), `aud`, `nbf`, `exp` claims; rejects tokens with mismatched values                | Unit test with forged claims                                                     |
| NFR-52 | 2FA enablement (confirmation) revokes all sessions except the current one                                                                  | Integration test                                                                 |
| NFR-53 | Sign-in response time must not vary based on whether the email exists; handler performs bcrypt hash for non-existent users (constant-time) | Timing analysis test                                                             |
| NFR-54 | Session cookie uses `__Host-` prefix (`__Host-auth_token`) with `Path=/` and no `Domain` attribute for subdomain attack prevention         | Behat test for cookie name + path + domain attributes                            |
| NFR-55 | Account locked for 15 minutes after 20 failed sign-in attempts within 1 hour for the same email                                            | Integration test: 21st attempt returns 423 Locked                                |
| NFR-56 | All 401 responses include `WWW-Authenticate: Bearer` header per RFC 7235                                                                   | Behat test for header presence                                                   |
| NFR-57 | 2FA secrets encrypted with AES-256-GCM; encryption key from `TWO_FACTOR_ENCRYPTION_KEY` env var                                            | Config validation + database inspection                                          |
| NFR-58 | Refresh token rotation uses atomic MongoDB operations (`findOneAndUpdate`) to prevent race conditions                                      | Unit test with concurrent rotation                                               |

### Security — GraphQL and API Protection (TEA R3)

| ID     | Requirement                                                                                                                               | Measurement                                                                         |
| ------ | ----------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| NFR-59 | GraphQL batching must not bypass rate limiting — reject batch requests (JSON arrays) at `/api/graphql` or count each operation separately | Integration test: batch of 10 sign-in mutations receives 400 or triggers rate limit |
| NFR-62 | Auth operations (sign-in, 2FA, token, sign-out) must not auto-expose via GraphQL mutations — `graphql: false` on all auth resources       | Integration test: GraphQL introspection shows no sign-in/2FA mutations              |
| NFR-64 | Implicit OAuth grant disabled in ALL environments including test                                                                          | Config validation: `OAUTH_ENABLE_IMPLICIT_GRANT=0` in `.env.test`                   |
| NFR-65 | CORS `allow_credentials: true` with explicit origin (no wildcard `*`) in ALL environments including dev                                   | Config validation + integration test                                                |
| NFR-66 | `Permissions-Policy` header (`camera=(), microphone=(), geolocation=(), payment=()`) on all responses                                     | Behat test for header presence                                                      |

### Security — Key Management and Token Binding (TEA R3)

| ID     | Requirement                                                                                        | Measurement                                               |
| ------ | -------------------------------------------------------------------------------------------------- | --------------------------------------------------------- |
| NFR-60 | Bearer token sidejack risk documented as accepted for MVP; token fingerprinting deferred to Growth | Architecture ADR-13 documents accepted risk               |
| NFR-61 | JWT private key permissions 600 (owner read/write only); not world-readable                        | CI check: `stat -c %a config/jwt/private.pem` returns 600 |
| NFR-67 | (Growth) Password breach database check via Symfony `NotCompromisedPassword` constraint            | Integration test with known-breached password             |
| NFR-68 | Recovery code exhaustion warning: response includes `recovery_codes_remaining` when count <= 2     | Behat test: use 7th code, verify warning in response      |

### Security — Rate Limiting

| ID     | Requirement                                                                                                         | Measurement                                                                    |
| ------ | ------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------ |
| NFR-08 | Global API: 100 requests/minute per IP (anonymous), 300 requests/minute per IP (authenticated) using sliding window | Load test: 101st anonymous request within 1 minute receives 429                |
| NFR-09 | Registration (`POST /api/users`): 5 requests/minute per IP using token bucket                                       | Load test: 6th registration from same IP within 1 minute receives 429          |
| NFR-10 | OAuth token (`POST /token`): 10 requests/minute per client_id using sliding window                                  | Load test: 11th token request from same client_id within 1 minute receives 429 |
| NFR-11 | Sign-in (`POST /api/signin`): 10 requests/minute per IP, 5 requests/minute per email using sliding window           | Load test verification                                                         |
| NFR-12 | 2FA verification (`POST /api/signin/2fa`): 5 attempts/minute per pending session                                    | Load test verification                                                         |
| NFR-13 | Resend confirmation email: 3 requests/minute per IP + 3 requests/minute per target user ID using token bucket       | Load test verification                                                         |
| NFR-14 | All rate limit rejections include `Retry-After` header and RFC 7807 body with status 429                            | Behat test for response format                                                 |
| NFR-43 | `GET /api/users` collection: 30 requests/minute per IP (authenticated)                                              | Load test verification                                                         |
| NFR-44 | `POST /api/users/2fa/setup`: 5 requests/minute per user                                                             | Behat test                                                                     |
| NFR-45 | `POST /api/users/2fa/confirm`: 5 requests/minute per user                                                           | Behat test                                                                     |
| NFR-46 | `PATCH /api/users/confirm` (email confirmation): 10 requests/minute per IP                                          | Load test verification                                                         |
| NFR-47 | `PATCH/PUT /api/users/{id}`: 10 requests/minute per user                                                            | Behat test                                                                     |
| NFR-48 | `DELETE /api/users/{id}`: 3 requests/minute per user                                                                | Behat test                                                                     |
| NFR-49 | `POST /api/users/{id}/resend-confirmation-email`: also 3 requests/minute per target user ID                         | Behat test                                                                     |

### Security — Data Protection

| ID     | Requirement                                                                           | Measurement                                          |
| ------ | ------------------------------------------------------------------------------------- | ---------------------------------------------------- |
| NFR-15 | Refresh tokens stored as SHA-256 hashes, never plaintext                              | Database inspection: no plaintext tokens             |
| NFR-16 | 2FA secrets encrypted at application level before MongoDB persistence                 | Database inspection: secrets not readable            |
| NFR-17 | MongoDB connections use TLS in production (`?tls=true`)                               | Production connection string validation              |
| NFR-18 | All external traffic uses TLS 1.2+ with HSTS (`max-age=31536000; includeSubDomains`)  | SSL Labs scan grade A+                               |
| NFR-37 | Confirmation tokens are at least 32 characters (matching password reset token length) | Config validation: `CONFIRMATION_TOKEN_LENGTH >= 32` |
| NFR-42 | Recovery codes stored as SHA-256 hashes, 8 codes generated per user, single-use       | Database inspection + unit test                      |

### Security — Headers and GraphQL

| ID     | Requirement                                                                            | Measurement                                                 |
| ------ | -------------------------------------------------------------------------------------- | ----------------------------------------------------------- |
| NFR-19 | `Strict-Transport-Security: max-age=31536000; includeSubDomains` on all responses      | Behat test for header presence                              |
| NFR-20 | `X-Content-Type-Options: nosniff` on all responses                                     | Behat test                                                  |
| NFR-21 | `X-Frame-Options: DENY` on all responses                                               | Behat test                                                  |
| NFR-22 | `Referrer-Policy: strict-origin-when-cross-origin` on all responses                    | Behat test                                                  |
| NFR-23 | `Content-Security-Policy: default-src 'none'; frame-ancestors 'none'` on API responses | Behat test                                                  |
| NFR-24 | GraphQL introspection disabled in production environment                               | Integration test: introspection query returns error in prod |
| NFR-35 | GraphQL max query depth: 20                                                            | Integration test: deeply nested query returns error         |
| NFR-36 | GraphQL max query complexity: 500                                                      | Integration test: complex query returns error               |
| NFR-39 | Request body size limited to 64KB at proxy level (Caddy)                               | Load test: oversized request returns 413                    |

### Observability

| ID     | Requirement                                                                                                                | Measurement                                           |
| ------ | -------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------- |
| NFR-33 | All authentication events emit structured JSON log entries with IP, user-agent, user ID (if known), event type, and result | Log inspection in integration tests                   |
| NFR-34 | Refresh token theft detection (grace window violation) emits a high-severity alert                                         | Integration test: verify log level on theft detection |

### Reliability

| ID     | Requirement                                                                                          | Measurement                         |
| ------ | ---------------------------------------------------------------------------------------------------- | ----------------------------------- |
| NFR-25 | All error responses use RFC 7807 problem+json format with `type`, `title`, `status`, `detail` fields | Schema validation in Behat          |
| NFR-26 | Refresh token grace window survives Redis restart (fallback: reject and force re-login)              | Integration test with Redis restart |

### Quality

| ID     | Requirement                                                                       | Measurement                |
| ------ | --------------------------------------------------------------------------------- | -------------------------- |
| NFR-27 | PHPInsights: Complexity >= 94%, Quality = 100%, Architecture = 100%, Style = 100% | `make phpinsights`         |
| NFR-28 | Deptrac: 0 violations                                                             | `make deptrac`             |
| NFR-29 | Psalm: 0 errors                                                                   | `make psalm`               |
| NFR-30 | Test coverage >= 90% for new auth code                                            | `make tests-with-coverage` |

## Spec Decisions and Deviations

| Decision                                                             | Rationale                                                                                              |
| -------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| `/api/token` is refresh-only (no password grant)                     | Separates sign-in from token exchange; cleaner CQRS                                                    |
| Session cookie contains a signed JWT (same format as bearer token)   | Works with `stateless: true` firewall — no server-side PHP sessions needed                             |
| Session cookie uses `SameSite=Lax` (not `Strict`)                    | Allows legitimate top-level navigation from external links                                             |
| Grace window default 60s (configurable via env var)                  | Balances crash recovery vs. replay attack window                                                       |
| Sliding window for global rate limit (not token bucket)              | More predictable behavior under sustained load                                                         |
| Token bucket for registration rate limit                             | Allows short bursts while enforcing long-term limits                                                   |
| Password grant disabled after sign-in ships                          | Prevents 2FA bypass via legacy grant type                                                              |
| Bcrypt cost >= 12 with `migrate_from` for existing hashes            | Re-hashes transparently on next login; no mass migration needed                                        |
| 8 recovery codes per 2FA enable                                      | Industry standard (GitHub uses 16, Google uses 10); balance UX vs. security                            |
| Recovery codes accepted at `/api/signin/2fa` (same endpoint as TOTP) | Simpler client implementation; server detects code format                                              |
| Access token TTL reduced from 1h to 15min                            | Limits JWT revocation window; immediate revocation via jti denylist deferred to Growth                 |
| JWT includes `sid` (session ID) claim                                | Required for logout to identify which session to revoke; minimal JWT size impact                       |
| `__Host-` cookie prefix                                              | Browser-enforced: Secure flag, `Path=/`, no Domain attribute, prevents subdomain cookie attacks        |
| Account lockout (20 attempts/1h) vs rate limiting only               | Rate limiting alone allows indefinite low-rate brute force; lockout adds cumulative protection         |
| 2FA enablement revokes sessions (same as password change)            | Prevents pre-2FA compromised sessions from persisting after user enables 2FA                           |
| Constant-time credential validation                                  | Prevents email enumeration via response time analysis; bcrypt hash against dummy on non-existent users |

## Risks

| Risk                                                             | Mitigation                                                                            |
| ---------------------------------------------------------------- | ------------------------------------------------------------------------------------- |
| Refresh rotation edge cases causing unexpected 401s              | Grace window + comprehensive Behat scenarios for crash/retry patterns                 |
| Allowlist mistakes exposing protected endpoints                  | Integration test that enumerates all routes and verifies auth requirement             |
| 2FA code verification drift due to clock skew                    | Allow +/- 1 time window in TOTP validation                                            |
| Rate limiter false positives for shared IPs (NAT/corporate)      | Configurable limits via env vars; authenticated users get higher limits               |
| Redis failure breaking rate limiting                             | Fail-open with logging alert (rate limiting is defense-in-depth, not sole protection) |
| Password grant used by existing clients                          | Announce deprecation; disable in same release as sign-in endpoint                     |
| User locks themselves out of 2FA                                 | Recovery codes provided on setup; regeneration endpoint available                     |
| Bcrypt cost increase slows sign-in                               | Cost 12 adds ~250ms; within SC-01 budget of 300ms P95                                 |
| Enabling firewall breaks all existing tests                      | Dedicated test infrastructure story (Story 4.0) before firewall story                 |
| Access control patterns don't match actual routes                | Route enumeration integration test verifies every route                               |
| GraphQL mutations bypass REST-level ownership checks             | GraphQL-specific security expressions on all write mutations                          |
| JWT remains valid after session revocation                       | 15-min TTL limits window; immediate revocation (jti denylist) is Growth item          |
| Timing-based email enumeration                                   | Constant-time validation: always hash even for non-existent users                     |
| Concurrent refresh token rotation creates orphan tokens          | Atomic MongoDB operations (findOneAndUpdate) with preconditions                       |
| Account lockout false positives (attacker locks victim)          | 15-min lockout duration is short; rate limiting still primary defense                 |
| 2FA encryption key compromise                                    | Key in env var (not code); rotation strategy is Growth item                           |
| GraphQL batching bypasses rate limiting (OWASP API2:2023)        | Reject batch requests at GraphQL endpoint; auth operations excluded from GraphQL      |
| Bearer token sidejacking (stolen JWT replayed from other device) | 15-min TTL limits window; token fingerprinting is Growth item                         |
| Distributed credential stuffing across rotating IPs              | Per-email rate limit + account lockout bounds total attempts; CAPTCHA is Growth item  |
| JWT private key compromise via file permissions                  | Enforce 600 permissions; migrate to env var/secret in Growth                          |
| User exhausts all recovery codes without regenerating            | Warning when remaining <= 2; email notification is Growth item                        |

## Closed Questions

| Question                                          | Decision                                          | Rationale                                                                                                               |
| ------------------------------------------------- | ------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------- |
| Grace window duration                             | 60 seconds                                        | Configurable via `REFRESH_TOKEN_GRACE_WINDOW_SECONDS`                                                                   |
| Short TTL for non-remembered sessions             | 30 minutes                                        | Standard for banking/sensitive apps; configurable via `SESSION_TTL_SHORT`                                               |
| Long TTL for remembered sessions                  | 30 days                                           | Matches refresh token TTL; configurable via `SESSION_TTL_LONG`                                                          |
| `ROLE_SERVICE` vs. specific OAuth scope for batch | `ROLE_SERVICE`                                    | Simpler; service-to-service tokens get this role                                                                        |
| GraphQL max query depth                           | 20                                                | API Platform default; sufficient for current schema depth                                                               |
| GraphQL max query complexity                      | 500                                               | API Platform default; prevents DoS while allowing normal queries                                                        |
| Recovery code count                               | 8                                                 | Industry standard range (8-16); balance usability vs. security                                                          |
| Recovery code format                              | 8 alphanumeric characters, grouped as `xxxx-xxxx` | Easy to read/type; ~47 bits entropy per code                                                                            |
| Access token TTL                                  | 15 minutes                                        | Balances UX (less frequent refresh) vs. security (shorter revocation window); configurable via `OAUTH_ACCESS_TOKEN_TTL` |
| JWT revocation strategy (MVP)                     | Reduced TTL (15 min) — accept revocation window   | jti denylist adds Redis hard dependency on every request; not justified for MVP                                         |
| JWT issuer claim value                            | `vilnacrm-user-service`                           | Prevents token confusion between microservices                                                                          |
| JWT audience claim value                          | `vilnacrm-api`                                    | Prevents cross-service token acceptance                                                                                 |
| Account lockout threshold                         | 20 attempts / 1 hour / 15-min lockout             | High enough to avoid false positives for legitimate users who forget passwords                                          |
| 2FA encryption algorithm                          | AES-256-GCM                                       | Authenticated encryption (integrity + confidentiality); standard recommendation                                         |

## References

- Architecture: `docs/plans/2026-02-05-auth-2fa-signin-architecture.md`
- Epic: `docs/plans/2026-02-05-auth-2fa-signin-epic.md`
- Stories: `docs/plans/2026-02-05-auth-2fa-signin-stories.md`
- BMAD Method: [bmad-code-org/BMAD-METHOD](https://github.com/bmad-code-org/BMAD-METHOD)
