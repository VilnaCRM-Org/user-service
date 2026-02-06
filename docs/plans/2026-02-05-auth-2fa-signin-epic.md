---
stepsCompleted: [validate-prerequisites, requirements-inventory, coverage-map, epic-breakdown, story-details, security-review, tea-party-challenge, tea-party-challenge-r2, tea-party-challenge-r3]
inputDocuments: [docs/plans/2026-02-05-auth-2fa-signin-prd.md, docs/plans/2026-02-05-auth-2fa-signin-architecture.md]
workflowType: 'epics'
project_name: 'VilnaCRM User Service — Auth Sign-in + 2FA'
author: 'Valerii'
date: '2026-02-05'
revision: '5 — TEA Party Mode R3 Multi-Model Adversarial Review'
---

# Auth Sign-in + 2FA — Epic Breakdown

## Overview

This document provides the complete epic and story breakdown for the Auth Sign-in + 2FA feature, decomposing the requirements from the PRD and Architecture into implementable stories with BDD acceptance criteria.

**Epic Owner:** User Service
**Date:** 2026-02-05
**Revision:** 4 — TEA Party Mode R2 Deep Security Pass (addresses R1 13 critical + R2 4 critical gaps)

## Requirements Inventory

### Functional Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-01 | Sign in with email/password, receive access token + refresh token + session cookie | P0 |
| FR-02 | 2FA-enabled users receive `pending_session_id` instead of tokens on sign-in | P0 |
| FR-03 | Complete 2FA with pending session ID + TOTP code (or recovery code), receive tokens and cookie | P0 |
| FR-04 | Exchange valid refresh token for new access + refresh tokens | P0 |
| FR-05 | Rotated refresh token reusable once within grace window (default 60s) | P0 |
| FR-06 | Reuse after grace window (or second reuse within grace) revokes session and returns 401 | P0 |
| FR-07 | Authenticated users can generate TOTP secret and otpauth URI | P0 |
| FR-08 | Authenticated users can confirm 2FA setup with valid TOTP code; system generates recovery codes | P0 |
| FR-09 | All protected endpoints reject unauthenticated requests with 401 | P0 |
| FR-10 | Public endpoints (signin, registration, OAuth, health, password reset, email confirm) bypass auth | P0 |
| FR-11 | Batch user creation requires `ROLE_SERVICE` scope | P0 |
| FR-12 | Write/delete operations enforce ownership on both REST and GraphQL | P0 |
| FR-13 | Authenticated users can revoke current session (logout) | P0 |
| FR-14 | Authenticated users can revoke ALL sessions (sign out everywhere) | P0 |
| FR-15 | Authenticated users can disable 2FA with valid TOTP/recovery code | P0 |
| FR-16 | 8 single-use recovery codes generated on 2FA enable | P0 |
| FR-17 | Recovery codes accepted in place of TOTP during 2FA sign-in | P0 |
| FR-18 | Authenticated users can regenerate recovery codes | P1 |
| FR-19 | Password change revokes all sessions except current | P0 |
| FR-20 | 2FA enablement revokes all sessions except current | P0 |

### Non-Functional Requirements

| ID | Requirement | Category |
|----|-------------|----------|
| NFR-04 | Firewall enabled with OAuth2 validation on all `/api/` and `/graphql` | Auth |
| NFR-05 | Access token 15-min TTL (reduced from 1h to limit revocation window) | Auth |
| NFR-06 | Refresh token 1m TTL | Auth |
| NFR-07 | TOTP +/- 1 time window tolerance | Auth |
| NFR-08 | Global rate limit: 100/min anon, 300/min auth | Rate Limiting |
| NFR-09 | Registration: 5/min per IP | Rate Limiting |
| NFR-10 | Token exchange: 10/min per client_id | Rate Limiting |
| NFR-11 | Sign-in: 10/min per IP, 5/min per email | Rate Limiting |
| NFR-12 | 2FA verification: 5 attempts/min per pending session | Rate Limiting |
| NFR-13 | Resend confirmation: 3/min per IP + 3/min per target user | Rate Limiting |
| NFR-14 | Rate limit rejections include `Retry-After` + RFC 7807 | Rate Limiting |
| NFR-15 | Refresh tokens stored as SHA-256 hashes | Data Protection |
| NFR-16 | 2FA secrets encrypted before persistence | Data Protection |
| NFR-19-23 | Security headers (HSTS, XFO, XCTO, CSP, Referrer-Policy) | Headers |
| NFR-24 | GraphQL introspection disabled in production | Headers |
| NFR-25 | RFC 7807 error responses for all failures | Reliability |
| NFR-26 | Grace window survives Redis restart | Reliability |
| NFR-27-30 | Quality thresholds (PHPInsights, Deptrac, Psalm, coverage) | Quality |
| NFR-31 | Password change revokes all other sessions | Auth |
| NFR-32 | Bcrypt cost >= 12 with migrate_from | Auth |
| NFR-33 | Structured audit logging for all auth events | Observability |
| NFR-34 | Theft detection logs at CRITICAL level | Observability |
| NFR-35 | GraphQL max query depth: 20 | Security |
| NFR-36 | GraphQL max query complexity: 500 | Security |
| NFR-37 | Confirmation token length >= 32 characters | Data Protection |
| NFR-38 | JWT algorithm pinned to RS256 | Auth |
| NFR-39 | Request body size limit: 64KB | Availability |
| NFR-40 | CORS credentials: true with explicit origin | Auth |
| NFR-41 | Password grant disabled | Auth |
| NFR-42 | Recovery codes stored as SHA-256 hashes, 8 per user, single-use | Data Protection |
| NFR-43-49 | Extended rate limiting (collection, 2FA, update, delete, etc.) | Rate Limiting |
| NFR-50 | JWT includes claims: sub, iss, aud, exp, iat, nbf, jti, sid, roles | Auth |
| NFR-51 | JWT validation verifies iss (single string), aud, nbf, exp | Auth |
| NFR-52 | 2FA enablement revokes all sessions except current | Auth |
| NFR-53 | Constant-time credential validation (timing-safe) | Auth |
| NFR-54 | Cookie uses `__Host-` prefix | Data Protection |
| NFR-55 | Account lockout: 20 attempts / 1h / 15-min lock | Auth |
| NFR-56 | 401 responses include `WWW-Authenticate: Bearer` | Compliance |
| NFR-57 | 2FA secrets encrypted with AES-256-GCM | Data Protection |
| NFR-58 | Atomic MongoDB operations for refresh token rotation | Reliability |
| NFR-59 | GraphQL batching must not bypass rate limiting | Security |
| NFR-60 | Bearer token sidejack risk documented (accepted for MVP) | Auth |
| NFR-61 | JWT private key permissions 600 (owner only) | Security |
| NFR-62 | Auth operations excluded from GraphQL auto-exposure | Security |
| NFR-64 | Implicit OAuth grant disabled in ALL environments | Security |
| NFR-65 | CORS `credentials: true` + explicit origin in ALL envs | Auth |
| NFR-66 | `Permissions-Policy` header on all responses | Headers |
| NFR-67 | (Growth) Password breach database check | Auth |
| NFR-68 | Recovery code exhaustion warning when remaining <= 2 | UX/Security |

## FR Coverage Map

| FR | Epic 1 | Epic 2 | Epic 3 | Epic 4 | Epic 5 | Epic 6 |
|----|--------|--------|--------|--------|--------|--------|
| FR-01 | x | | | | | |
| FR-02 | x | | | | | |
| FR-03 | | x | | | | |
| FR-04 | | | x | | | |
| FR-05 | | | x | | | |
| FR-06 | | | x | | | |
| FR-07 | | x | | | | |
| FR-08 | | x | | | | |
| FR-09 | | | | x | | |
| FR-10 | | | | x | | |
| FR-11 | | | | x | | |
| FR-12 | | | | x | | |
| FR-13 | | | | | | x |
| FR-14 | | | | | | x |
| FR-15 | | x | | | | |
| FR-16 | | x | | | | |
| FR-17 | | x | | | | |
| FR-18 | | x | | | | |
| FR-19 | | | | x | | |
| FR-20 | | x | | | | |

## NFR Coverage Map

| NFR | Epic 1 | Epic 2 | Epic 3 | Epic 4 | Epic 5 | Epic 6 |
|-----|--------|--------|--------|--------|--------|--------|
| NFR-04 | | | | x | | |
| NFR-05 | x | | | | | |
| NFR-06 | | | x | | | |
| NFR-07 | | x | | | | |
| NFR-08 | | | | | x | |
| NFR-09 | | | | | x | |
| NFR-10 | | | | | x | |
| NFR-11 | | | | | x | |
| NFR-12 | | | | | x | |
| NFR-13 | | | | | x | |
| NFR-14 | | | | | x | |
| NFR-15 | x | | x | | | |
| NFR-16 | | x | | | | |
| NFR-19-23 | | | | | x | |
| NFR-24 | | | | | x | |
| NFR-25 | All | All | All | All | All | All |
| NFR-26 | | | x | | | |
| NFR-27-30 | All | All | All | All | All | All |
| NFR-31 | | | | x | | |
| NFR-32 | | | | | x | |
| NFR-33-34 | | | | | | x |
| NFR-35-36 | | | | | x | |
| NFR-37 | | | | | x | |
| NFR-38 | | | | x | | |
| NFR-39 | | | | | x | |
| NFR-40 | | | | x | | |
| NFR-41 | | | | x | | |
| NFR-42 | | x | | | | |
| NFR-43-49 | | | | | x | |
| NFR-50 | x | | x | x | | |
| NFR-51 | | | | x | | |
| NFR-52 | | x | | | | |
| NFR-53 | x | | | | | |
| NFR-54 | x | | | x | | |
| NFR-55 | | | | | x | |
| NFR-56 | | | | x | | |
| NFR-57 | | x | | | | |
| NFR-58 | | | x | | | |
| NFR-59 | | | | | x | |
| NFR-60 | | | | x | | |
| NFR-61 | | | | x | | |
| NFR-62 | | | | | x | |
| NFR-64 | | | | x | | |
| NFR-65 | | | | x | | |
| NFR-66 | | | | | x | |
| NFR-68 | | x | | | | |

## Epic List

1. **Epic 1: Sign-In Flow** — Core sign-in with email/password and 2FA detection
2. **Epic 2: 2FA Management** — 2FA setup, confirmation, recovery codes, disable, and sign-in completion
3. **Epic 3: Token Refresh and Rotation** — Refresh-only token exchange with rotation and grace window
4. **Epic 4: Authentication Gate** — Firewall, access control, ownership enforcement (REST + GraphQL), password grant deprecation, password-change session invalidation
5. **Epic 5: Security Hardening** — Rate limiting (all tiers), security headers, introspection control, GraphQL limits, bcrypt upgrade, confirmation token hardening, request body size
6. **Epic 6: Session Lifecycle and Observability** — Logout, sign-out-everywhere, audit logging

## Epic 1: Sign-In Flow

Core sign-in with email/password, session creation, and 2FA detection.

### Story 1.1: Sign-in without 2FA

As a user without 2FA enabled,
I want to sign in with email and password,
So that I receive a session cookie and tokens for API access.

**Acceptance Criteria:**

**Given** valid credentials for a user without 2FA enabled
**When** I POST to `/api/signin` with `{ email, password }`
**Then** the response status is 200
**And** the body contains `{ 2fa_enabled: false, access_token, refresh_token }`
**And** a `Set-Cookie` header is present with `HttpOnly`, `Secure`, `SameSite=Lax`
**And** the cookie contains a signed JWT

**Given** invalid credentials
**When** I POST to `/api/signin` with wrong password
**Then** the response status is 401
**And** the body is RFC 7807 problem+json

**Given** non-existent email
**When** I POST to `/api/signin`
**Then** the response status is 401
**And** the response does not distinguish between wrong email and wrong password
**And** response time does not differ from wrong-password case (constant-time)

**Given** 20 failed sign-in attempts for the same email within 1 hour
**When** I POST to `/api/signin` with that email
**Then** the response status is 423 Locked with `Retry-After` header

[Source: PRD FR-01, NFR-50, NFR-53, NFR-55, Architecture ADR-01, ADR-10]

### Story 1.2: Sign-in with 2FA detection

As a user with 2FA enabled,
I want sign-in to return a pending session instead of tokens,
So that my account requires a second factor before granting access.

**Acceptance Criteria:**

**Given** valid credentials for a user with `twoFactorEnabled = true`
**When** I POST to `/api/signin` with `{ email, password }`
**Then** the response status is 200
**And** the body contains `{ 2fa_enabled: true, pending_session_id }`
**And** no `access_token`, `refresh_token`, or `Set-Cookie` is present

**Given** a pending session is created
**When** 5 minutes elapse without 2FA completion
**Then** the pending session expires and cannot be used

[Source: PRD FR-02, Architecture PendingTwoFactor entity]

### Story 1.3: Domain entities and persistence for sign-in

As a developer,
I want AuthSession, AuthRefreshToken, PendingTwoFactor, and RecoveryCode entities with MongoDB mappings,
So that sign-in state is persisted correctly.

**Acceptance Criteria:**

**Given** the AuthSession entity definition
**When** a session is created via the repository
**Then** it is persisted in MongoDB with id, userId, ipAddress, userAgent, createdAt, expiresAt, revokedAt, rememberMe fields

**Given** the AuthRefreshToken entity definition
**When** a token is saved
**Then** the tokenHash field contains a SHA-256 hash, never plaintext
**And** the graceUsed field defaults to false

**Given** the RecoveryCode entity definition
**When** codes are saved
**Then** the codeHash field contains a SHA-256 hash, never plaintext

**Given** the PendingTwoFactor entity definition
**When** the MongoDB mapping is inspected
**Then** a TTL index exists on `expiresAt` for automatic cleanup

**Given** Doctrine ODM XML mappings exist for all four entities
**When** `make deptrac` is run
**Then** 0 violations are reported

[Source: Architecture Data Model, ADR-05]

## Epic 2: 2FA Management

TOTP 2FA setup, confirmation, recovery codes, disable, and sign-in completion.

### Story 2.1: Complete 2FA sign-in (TOTP)

As a user with a pending 2FA session,
I want to submit my TOTP code,
So that I receive tokens and a session cookie.

**Acceptance Criteria:**

**Given** a valid `pending_session_id` and correct TOTP code
**When** I POST to `/api/signin/2fa` with `{ pending_session_id, two_factor_code }`
**Then** the response status is 200
**And** the body contains `{ 2fa_enabled: true, access_token, refresh_token }`
**And** a `Set-Cookie` header is present

**Given** an expired or invalid `pending_session_id`
**When** I POST to `/api/signin/2fa`
**Then** the response status is 401

**Given** a valid `pending_session_id` but wrong TOTP code
**When** I POST to `/api/signin/2fa`
**Then** the response status is 401
**And** the pending session remains valid for retry (within expiry)

[Source: PRD FR-03, Architecture POST /api/signin/2fa]

### Story 2.2: 2FA setup for current user

As an authenticated user,
I want to generate a TOTP secret and QR code URI,
So that I can configure Google Authenticator.

**Acceptance Criteria:**

**Given** I am authenticated with a valid bearer token
**When** I POST to `/api/users/2fa/setup`
**Then** the response status is 200
**And** the body contains `{ otpauth_uri, secret }`
**And** `twoFactorEnabled` remains `false` until confirmed

**Given** I am not authenticated
**When** I POST to `/api/users/2fa/setup`
**Then** the response status is 401

[Source: PRD FR-07, Architecture POST /api/users/2fa/setup]

### Story 2.3: 2FA confirmation with recovery code generation

As an authenticated user who has set up 2FA,
I want to confirm my TOTP setup by submitting a valid code,
So that 2FA is enabled on my account and I receive recovery codes.

**Acceptance Criteria:**

**Given** I have a pending TOTP secret from `/api/users/2fa/setup`
**When** I POST to `/api/users/2fa/confirm` with a valid TOTP code
**Then** the response status is 200
**And** `twoFactorEnabled` becomes `true`
**And** the response contains `{ recovery_codes: ["xxxx-xxxx", ...] }` with 8 codes
**And** recovery codes are stored as SHA-256 hashes in the database

**Given** I submit an invalid TOTP code
**When** I POST to `/api/users/2fa/confirm`
**Then** the response status is 401
**And** `twoFactorEnabled` remains `false`

**Given** I have 3 active sessions across different devices
**When** I successfully confirm 2FA from device 1
**Then** devices 2 and 3's sessions are revoked
**And** devices 2 and 3 receive 401 on next request (within 15 min)

[Source: PRD FR-08, FR-16, FR-20, NFR-52, Architecture POST /api/users/2fa/confirm]

### Story 2.4: 2FA disable

As an authenticated user with 2FA enabled,
I want to disable 2FA by confirming with a valid code,
So that my account no longer requires a second factor.

**Acceptance Criteria:**

**Given** I am authenticated and have 2FA enabled
**When** I POST to `/api/users/2fa/disable` with a valid TOTP code
**Then** the response status is 204
**And** `twoFactorEnabled` becomes `false`
**And** `twoFactorSecret` is cleared
**And** all recovery codes are invalidated

**Given** I submit an invalid code
**When** I POST to `/api/users/2fa/disable`
**Then** the response status is 401
**And** 2FA remains enabled

**Given** I do not have 2FA enabled
**When** I POST to `/api/users/2fa/disable`
**Then** the response status is 403

[Source: PRD FR-15, Architecture POST /api/users/2fa/disable]

### Story 2.5: Complete 2FA sign-in with recovery code

As a user who lost their TOTP device,
I want to use a recovery code to complete sign-in,
So that I can access my account and reconfigure 2FA.

**Acceptance Criteria:**

**Given** a valid `pending_session_id` and a valid recovery code (format: `xxxx-xxxx`)
**When** I POST to `/api/signin/2fa` with `{ pending_session_id, two_factor_code: "abcd-efgh" }`
**Then** the response status is 200
**And** the body contains `{ 2fa_enabled: true, access_token, refresh_token }`
**And** the recovery code is marked as used and cannot be reused

**Given** a recovery code that has already been used
**When** I POST to `/api/signin/2fa`
**Then** the response status is 401

[Source: PRD FR-17, Architecture POST /api/signin/2fa]

### Story 2.6: Regenerate recovery codes

As an authenticated user with 2FA enabled,
I want to regenerate my recovery codes,
So that I have fresh codes after using some.

**Acceptance Criteria:**

**Given** I am authenticated and have 2FA enabled
**When** I POST to `/api/users/2fa/recovery-codes`
**Then** the response status is 200
**And** the response contains `{ recovery_codes: ["xxxx-xxxx", ...] }` with 8 new codes
**And** all previous recovery codes are invalidated

**Given** I do not have 2FA enabled
**When** I POST to `/api/users/2fa/recovery-codes`
**Then** the response status is 403

[Source: PRD FR-18, Architecture POST /api/users/2fa/recovery-codes]

## Epic 3: Token Refresh and Rotation

Refresh-only token exchange with rotation and grace window.

### Story 3.1: Refresh JWT using refresh token

As an authenticated client,
I want to exchange a refresh token for a new JWT,
So that I can maintain my session without re-authenticating.

**Acceptance Criteria:**

**Given** a valid, non-expired, non-rotated refresh token
**When** I POST to `/api/token` with `{ refresh_token }`
**Then** the response status is 200
**And** the body contains new `{ access_token, refresh_token }`
**And** the old refresh token is marked as rotated

**Given** an invalid or expired refresh token
**When** I POST to `/api/token`
**Then** the response status is 401

[Source: PRD FR-04, Architecture ADR-05]

### Story 3.2: Refresh token rotation grace window

As a client that may crash during token rotation,
I want a short grace window to reuse a rotated token,
So that crashes do not log me out.

**Acceptance Criteria:**

**Given** a rotated refresh token within the grace window (60s default)
**When** I POST to `/api/token` with the rotated token
**Then** the response status is 200
**And** new tokens are issued
**And** the `graceUsed` flag is set to true on the old token

**Given** a rotated refresh token after the grace window
**When** I POST to `/api/token` with the rotated token
**Then** the response status is 401
**And** the entire session is revoked (theft detection)
**And** a CRITICAL-level audit log is emitted

**Given** a rotated token used twice within the grace window (graceUsed already true)
**When** I POST to `/api/token` with the same rotated token a second time
**Then** the response status is 401
**And** the entire session is revoked
**And** a CRITICAL-level audit log is emitted

**Given** the `REFRESH_TOKEN_GRACE_WINDOW_SECONDS` env var is set to 30
**When** grace window logic is evaluated
**Then** the window is 30 seconds (not the 60s default)

[Source: PRD FR-05, FR-06, Architecture ADR-05]

## Epic 4: Authentication Gate

Firewall enablement, access control, ownership enforcement, password grant deprecation, and password-change session invalidation.

### Story 4.0: Test infrastructure for authenticated requests

As a developer,
I want test helpers that inject valid auth tokens into Behat and integration tests,
So that existing tests continue to pass after the firewall is enabled.

**Acceptance Criteria:**

**Given** the test suite has auth helper utilities
**When** a Behat context needs an authenticated request
**Then** it can obtain a valid bearer token via a helper method

**Given** all existing Behat and integration tests
**When** the test suite runs with the auth helpers in place (but firewall still disabled)
**Then** all tests pass (no regression)

[Source: TEA Challenge M-08]

### Story 4.1: Enable Symfony security firewall

As the system,
I want the Symfony firewall enabled with OAuth2 authenticator,
So that all requests are authenticated before reaching controllers.

**Acceptance Criteria:**

**Given** the `api` firewall is configured with `security: true`, `stateless: true`, `oauth2: true`
**When** a request hits any `/api/` route without a valid bearer token or session cookie
**Then** the response status is 401

**Given** the `oauth` firewall covers `^/(token|authorize|\.well-known)`
**When** a request hits these routes without auth
**Then** the request proceeds (these endpoints handle their own auth)

**Given** existing tests use the auth test infrastructure from Story 4.0
**When** all tests run with the firewall enabled
**Then** all tests pass (no regression)

**Given** a JWT with `iss` not equal to `vilnacrm-user-service`
**When** the DualAuthenticator validates the token
**Then** the response status is 401 with `WWW-Authenticate: Bearer` header

**Given** a JWT with `aud` not equal to `vilnacrm-api`
**When** the DualAuthenticator validates the token
**Then** the response status is 401 with `WWW-Authenticate: Bearer` header

[Source: PRD FR-09, NFR-50, NFR-51, NFR-54, NFR-56, Architecture ADR-01, ADR-03]

### Story 4.2: Access control with public allowlist

As the system,
I want explicit access control rules with a public allowlist,
So that only designated endpoints are accessible without authentication.

**Acceptance Criteria:**

**Given** the public allowlist includes signin, registration, email confirmation, password reset (`/api/reset-password`), token, docs, and health
**When** I request any of these endpoints without auth
**Then** the request proceeds normally

**Given** I request `GET /api/users` without auth
**When** the firewall evaluates the request
**Then** the response status is 401

**Given** I request `POST /api/users/batch` with a `ROLE_USER` token (not `ROLE_SERVICE`)
**When** the access control evaluates the request
**Then** the response status is 403

**Given** an integration test that enumerates all registered routes
**When** each route is tested without auth
**Then** it is either in the public allowlist or returns 401

[Source: PRD FR-10, FR-11, Architecture ADR-03]

### Story 4.3: Ownership enforcement on user resources (REST + GraphQL)

As the system,
I want write/delete operations on user resources to enforce ownership on both REST and GraphQL,
So that users can only modify their own data.

**Acceptance Criteria:**

**REST:**

**Given** I am authenticated as user A
**When** I PATCH `/api/users/{user_B_id}`
**Then** the response status is 403

**Given** I am authenticated as user A
**When** I PATCH `/api/users/{user_A_id}` with valid data
**Then** the response status is 200

**Given** I am authenticated as user A
**When** I DELETE `/api/users/{user_B_id}`
**Then** the response status is 403

**GraphQL:**

**Given** I am authenticated as user A
**When** I execute `mutation { updateUser(input: { id: "/api/users/{user_B_id}", ... }) }`
**Then** the response contains an authorization error

**Given** I am authenticated as user A
**When** I execute `mutation { deleteUser(input: { id: "/api/users/{user_B_id}" }) }`
**Then** the response contains an authorization error

**Given** I am authenticated as user A
**When** I execute `mutation { resendEmailTo(input: { id: "/api/users/{user_B_id}" }) }`
**Then** the response contains an authorization error

[Source: PRD FR-12, Architecture ADR-03 ownership expressions]

### Story 4.4: Disable OAuth password grant

As the system,
I want the OAuth password grant disabled,
So that 2FA cannot be bypassed via the legacy grant type.

**Acceptance Criteria:**

**Given** the password grant is disabled in `league_oauth2_server.yaml`
**When** a client sends `POST /token` with `grant_type=password`
**Then** the response is an OAuth error indicating unsupported grant type

**Given** the password grant is disabled
**When** existing OAuth flows (client_credentials, authorization_code, refresh_token) are used
**Then** they continue to work normally

[Source: PRD NFR-41, Architecture ADR-07]

### Story 4.5: Password change invalidates other sessions

As the system,
I want password changes to revoke all sessions except the current one,
So that compromised sessions are terminated when the user changes their password.

**Acceptance Criteria:**

**Given** user A is authenticated on device 1 and device 2
**When** user A changes their password via `PATCH /api/users/{id}` with `newPassword` from device 1
**Then** device 1's session remains valid
**And** device 2's session is revoked
**And** device 2 receives 401 on the next request

**Given** a user changes their password
**When** the session revocation is complete
**Then** an audit log entry is emitted with reason "password_change"

[Source: PRD FR-19, NFR-31, Architecture ADR-08]

## Epic 5: Security Hardening

Rate limiting, security headers, GraphQL limits, bcrypt upgrade, confirmation token hardening, and request body size.

### Story 5.1: Multi-tier rate limiting (global + existing endpoints)

As the system,
I want endpoint-specific rate limiting for global traffic and existing endpoints,
So that abuse is prevented at each sensitivity level.

**Acceptance Criteria:**

**Given** an anonymous client making requests to `/api/`
**When** the client exceeds 100 requests in 1 minute
**Then** subsequent requests receive 429 with `Retry-After` header and RFC 7807 body

**Given** an authenticated client
**When** the client exceeds 300 requests in 1 minute
**Then** subsequent requests receive 429

**Given** a client registering users
**When** the client sends more than 5 `POST /api/users` in 1 minute from the same IP
**Then** the 6th request receives 429

**Given** a client fetching the user collection
**When** the client sends more than 30 `GET /api/users` in 1 minute
**Then** the 31st request receives 429

**Given** a client exchanging tokens
**When** the client sends more than 10 `POST /token` in 1 minute with the same client_id
**Then** the 11th request receives 429

**Given** a client confirming email
**When** the client sends more than 10 `PATCH /api/users/confirm` in 1 minute from same IP
**Then** the 11th request receives 429

**Given** a client updating/deleting users
**When** the client exceeds the per-user rate limit (10/min update, 3/min delete)
**Then** subsequent requests receive 429

[Source: PRD NFR-08 through NFR-14, NFR-43 through NFR-49, Architecture ADR-02]

### Story 5.2: Sign-in, 2FA, and auth-specific rate limiting

As the system,
I want sign-in, 2FA, and auth-specific endpoints rate-limited per IP and per account,
So that credential stuffing, brute-force, and abuse are mitigated.

**Acceptance Criteria:**

**Given** a client attempting sign-in
**When** more than 10 `POST /api/signin` requests are sent from the same IP in 1 minute
**Then** subsequent requests receive 429

**Given** a client attempting sign-in for the same email
**When** more than 5 `POST /api/signin` requests target the same email in 1 minute
**Then** subsequent requests receive 429

**Given** a client verifying 2FA codes
**When** more than 5 `POST /api/signin/2fa` requests use the same pending_session_id in 1 minute
**Then** subsequent requests receive 429

**Given** an authenticated user setting up 2FA
**When** more than 5 `POST /api/users/2fa/setup` requests are sent in 1 minute
**Then** subsequent requests receive 429

**Given** an authenticated user confirming 2FA
**When** more than 5 `POST /api/users/2fa/confirm` requests are sent in 1 minute
**Then** subsequent requests receive 429

**Given** an authenticated user disabling 2FA
**When** more than 3 `POST /api/users/2fa/disable` requests are sent in 1 minute
**Then** subsequent requests receive 429

**Given** a client resending confirmation emails
**When** more than 3 requests target the same user ID in 1 minute
**Then** subsequent requests receive 429

**Given** 20 failed sign-in attempts for the same email within 1 hour
**When** the 21st attempt is made
**Then** the response status is 423 Locked with `Retry-After: 900`
**And** an `AccountLockedOut` audit log is emitted

[Source: PRD NFR-11, NFR-12, NFR-44, NFR-45, NFR-49, NFR-55, Architecture ADR-02, ADR-10]

### Story 5.3: Security headers

As the system,
I want security headers on all API responses,
So that the service passes security audits.

**Acceptance Criteria:**

**Given** any API response in production
**When** the response headers are inspected
**Then** `Strict-Transport-Security: max-age=31536000; includeSubDomains` is present
**And** `X-Content-Type-Options: nosniff` is present
**And** `X-Frame-Options: DENY` is present
**And** `Referrer-Policy: strict-origin-when-cross-origin` is present
**And** `Content-Security-Policy: default-src 'none'; frame-ancestors 'none'` is present
**And** no `Server` header is present

[Source: PRD NFR-19 through NFR-23, Architecture ADR-04]

### Story 5.4: GraphQL hardening (introspection, depth, complexity)

As the system,
I want GraphQL introspection disabled in production and query limits enforced,
So that the API schema is not leaked and DoS via complex queries is prevented.

**Acceptance Criteria:**

**Given** the production environment
**When** a GraphQL introspection query is sent (`{ __schema { types { name } } }`)
**Then** the response contains an error, not the schema

**Given** the development environment
**When** a GraphQL introspection query is sent
**Then** the schema is returned normally

**Given** a GraphQL query with depth > 20
**When** the query is executed
**Then** the response contains a depth-exceeded error

**Given** a GraphQL query with complexity > 500
**When** the query is executed
**Then** the response contains a complexity-exceeded error

[Source: PRD NFR-24, NFR-35, NFR-36, Architecture ADR-06]

### Story 5.5: Bcrypt cost upgrade

As the system,
I want password hashing upgraded to bcrypt cost >= 12,
So that brute-force attacks on stolen hashes are computationally infeasible.

**Acceptance Criteria:**

**Given** the security.yaml has `cost: 12` with `migrate_from: [{ algorithm: auto, cost: 4 }]`
**When** a user signs in with a password hashed at cost 4
**Then** the password is verified successfully
**And** the hash is transparently upgraded to cost 12

**Given** a new user registers
**When** their password is hashed
**Then** the hash uses cost 12

[Source: PRD NFR-32, Architecture ADR-09]

### Story 5.6: Confirmation token hardening

As the system,
I want confirmation tokens to be at least 32 characters,
So that brute-force guessing of confirmation tokens is infeasible.

**Acceptance Criteria:**

**Given** the `CONFIRMATION_TOKEN_LENGTH` env var is set to 32
**When** a new confirmation token is generated
**Then** the token is 32 characters long

[Source: PRD NFR-37, TEA Challenge C-13]

### Story 5.7: Request body size limit

As the system,
I want request body size limited to 64KB at the proxy level,
So that memory exhaustion attacks are prevented.

**Acceptance Criteria:**

**Given** the Caddyfile has `request_body { max_size 64KB }`
**When** a request with body > 64KB is sent
**Then** the response status is 413

[Source: PRD NFR-39, Architecture ADR-04]

## Epic 6: Session Lifecycle and Observability

Logout, sign-out-everywhere, and audit logging.

### Story 6.1: Logout (current session)

As an authenticated user,
I want to log out from my current session,
So that my tokens are revoked and my session cookie is cleared.

**Acceptance Criteria:**

**Given** I am authenticated
**When** I POST to `/api/signout`
**Then** the response status is 204
**And** the `Set-Cookie` header clears the auth cookie (`Max-Age=0`)
**And** my current AuthSession is revoked
**And** all refresh tokens for this session are revoked
**And** subsequent requests with my old token receive 401

[Source: PRD FR-13, Architecture POST /api/signout]

### Story 6.2: Sign out everywhere

As an authenticated user,
I want to revoke all my sessions,
So that all devices/clients are logged out.

**Acceptance Criteria:**

**Given** I have 3 active sessions across different devices
**When** I POST to `/api/signout/all`
**Then** the response status is 204
**And** all 3 AuthSessions are revoked
**And** all associated refresh tokens are revoked
**And** all devices receive 401 on their next request

[Source: PRD FR-14, Architecture POST /api/signout/all]

### Story 6.3: Audit logging for auth events

As the system,
I want all authentication events logged with structured JSON,
So that security incidents can be investigated.

**Acceptance Criteria:**

**Given** a successful sign-in
**When** the event subscriber processes the `UserSignedIn` event
**Then** a structured log entry is written with userId, ip, userAgent, twoFactorUsed

**Given** a failed sign-in
**When** the event subscriber processes the `SignInFailed` event
**Then** a WARNING-level log entry is written with attemptedEmail, ip, reason

**Given** a refresh token theft detection (grace window violation)
**When** the event subscriber processes the `RefreshTokenTheftDetected` event
**Then** a CRITICAL-level log entry is written with sessionId, userId, ip

**Given** a recovery code is used
**When** the event subscriber processes the `RecoveryCodeUsed` event
**Then** a WARNING-level log entry is written with userId, remainingCodes

[Source: PRD NFR-33, NFR-34, Architecture ADR-08]

## Scope

**In scope:**

- Sign-in and 2FA endpoints (REST only for MVP; GraphQL mutations deferred)
- 2FA recovery codes and disable flow
- Refresh-only token exchange with rotation and grace window
- Logout (current session) and sign-out-everywhere
- Authentication gate with firewall + access control
- Ownership enforcement on REST and GraphQL
- Session cookies (JWT-based) and refresh token rotation
- TOTP setup, confirmation, and disable for current user
- Multi-tier rate limiting (16 tiers — ALL endpoints)
- Security headers (Caddy) and request body size limit
- GraphQL introspection control, depth and complexity limits
- Password grant deprecation
- Bcrypt cost upgrade with migration
- Confirmation token length hardening
- Password-change session invalidation
- 2FA-enablement session invalidation
- JWT claims validation (iss, aud, nbf, sid) and 15-min access token TTL
- Constant-time credential validation
- `__Host-` cookie prefix
- Account lockout after cumulative failures
- `WWW-Authenticate` header on 401 responses
- AES-256-GCM encryption for 2FA secrets
- Atomic refresh token rotation
- Structured audit logging for all auth events

**Out of scope:**

- OAuth flow changes or new grant types (beyond disabling password grant)
- Admin 2FA management for other users
- UI changes
- WebAuthn/FIDO2
- GraphQL mutations for sign-in/2FA (future epic)
- Suspicious login detection / device fingerprinting (Growth)

## Milestones

1. Specs approved (PRD + Architecture + Epic + Stories) — this document
2. Domain entities + persistence (Story 1.3)
3. Test infrastructure for auth (Story 4.0)
4. Firewall + access control (Stories 4.1, 4.2)
5. Sign-in flow (Stories 1.1, 1.2)
6. 2FA flow (Stories 2.1-2.6)
7. Token rotation (Stories 3.1, 3.2)
8. Ownership enforcement REST + GraphQL (Story 4.3)
9. Password grant + password-change invalidation (Stories 4.4, 4.5)
10. Logout + sign-out-everywhere (Stories 6.1, 6.2)
11. Security hardening (Stories 5.1-5.7)
12. Audit logging (Story 6.3)
13. Full CI green (`make ci`)

## Risks

| Risk | Mitigation |
|------|------------|
| Refresh rotation edge cases | Grace window + `graceUsed` boolean + comprehensive Behat scenarios |
| Allowlist mistakes in auth gate | Route enumeration integration test |
| 2FA clock skew | +/- 1 time window tolerance |
| Rate limit false positives (shared IPs) | Configurable limits; higher limits for authenticated users |
| Redis failure breaking rate limiting | Fail-open with logging alert |
| Enabling firewall breaks existing tests | Story 4.0 (test infrastructure) before Story 4.1 (firewall) |
| Password grant bypass of 2FA | Disabled in same release (Story 4.4) |
| User lockout from 2FA | Recovery codes (Story 2.5, 2.6) |
| Bcrypt cost increase slows sign-in | Cost 12 within SC-01 P95 budget |
| Access control patterns mismatch | Verified against actual routes in ADR-03 |
| GraphQL mutations bypass ownership | Story 4.3 covers both REST and GraphQL |

## Definition of Done

- All stories accepted with documented BDD acceptance criteria
- 100% unit/integration/Behat coverage for new flows
- `make ci` passes: PHPInsights 94/100/100/100, Deptrac 0, Psalm 0
- Security checklist in Architecture doc fully checked
- Audit logging verified for all auth event types
- Documentation synced via `documentation-sync` skill

## References

- PRD: `docs/plans/2026-02-05-auth-2fa-signin-prd.md`
- Architecture: `docs/plans/2026-02-05-auth-2fa-signin-architecture.md`
- Stories: `docs/plans/2026-02-05-auth-2fa-signin-stories.md`
