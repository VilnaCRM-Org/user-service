---
stepsCompleted: []
workflowType: 'greenfield-fullstack'
inputDocuments: []
version: 2
date: 2026-03-03
authors: [Mary (Analyst), John (PM), Winston (Architect)]
---

# PRD: OAuth Social Sign-In / Sign-Up (GitHub & Google)

## 1. Overview

### 1.1 Problem Statement

The current authentication system requires email + password credentials for every user. This creates friction at sign-up and sign-in. Users expect "Sign in with GitHub" and "Sign in with Google" as baseline capabilities.

### 1.2 Proposed Solution

Implement OAuth 2.0 Authorization Code flow for GitHub and Google as alternative authentication methods, with defense-in-depth controls (state, PKCE, one-time callback consumption, strict error contracts, local 2FA authority).

### 1.3 Goals

- Allow users to authenticate via GitHub or Google without creating a local password first.
- Maintain security parity with password sign-in: local 2FA is enforced regardless of provider.
- Prevent login CSRF, replay, provider mix-up, and account-takeover-by-autolink risks.
- Keep the `OAuth` bounded context isolated from `User` internals.

### 1.4 Non-Goals (Explicit Deferrals)

- Account linking management endpoint (link/unlink from user settings) - future epic.
- Automatic linking of existing local users by email during social callback - deferred for security.
- Additional providers (Apple, Microsoft, LinkedIn) - future epic.
- Parsing provider-side `amr` claims to skip local 2FA - explicitly rejected.
- Mobile/native app OAuth flows - out of scope.

---

## 2. User Stories (High-Level)

| ID    | As a...       | I want to...                                          | So that...                                             |
|-------|---------------|-------------------------------------------------------|--------------------------------------------------------|
| US-01 | New user      | Click "Sign in with GitHub" and get an account       | I do not need to create a password first               |
| US-02 | New user      | Click "Sign in with Google" and get an account       | Sign-up is fast                                        |
| US-03 | Linked user   | Sign in via my linked GitHub/Google identity          | I can log in without entering password                 |
| US-04 | 2FA user      | Still be prompted for TOTP after OAuth                | Security is consistent regardless of auth method       |
| US-05 | Security team | Reject unsafe linking and replay/mix-up attacks       | Account takeover and login CSRF risks are controlled   |

---

## 3. Functional Requirements

### 3.1 OAuth Initiation

**FR-01** - The system MUST expose `GET /api/auth/social/{provider}` where `{provider}` is `github` or `google`.

**FR-02** - On request, the system MUST generate:
- cryptographically random `state`
- PKCE `code_verifier` and `code_challenge` (`S256`)
- flow-binding token (stored in secure cookie)

and persist state payload in Redis (TTL 10 minutes): `state`, `provider`, `code_verifier`, `flow_binding_hash`, `redirect_uri`.

**FR-03** - The provider redirect URI MUST be `GET /api/auth/social/{provider}/callback` on the same service host.

**FR-04** - Unsupported providers MUST return HTTP 400 as RFC 7807 problem response with machine code `unsupported_provider`.

**FR-05** - Initiation responses MUST include `Cache-Control: no-store`.

### 3.2 OAuth Callback

**FR-06** - Callback requires `code`, `state`, and flow-binding cookie; missing values MUST return HTTP 400 problem response with code `missing_oauth_parameters`.

**FR-07** - State validation MUST be one-time and atomic and MUST verify all of:
- state exists and not expired
- not already consumed
- route provider matches stored provider
- cookie binding hash matches stored binding hash

Failures MUST return HTTP 422 (`invalid_state` or `state_expired`) or HTTP 400 (`provider_mismatch`) as problem responses.

**FR-08** - The system MUST exchange `code` server-side using stored PKCE `code_verifier`.

**FR-09** - The system MUST fetch provider profile:
- GitHub: primary verified email + login
- Google: verified email + name + provider id

**FR-10** - User resolution MUST follow this order:
1. `SocialIdentity(provider, providerId)` exists -> resolve linked `User`
2. No `SocialIdentity`, email matches existing `User` -> reject with HTTP 409 (`social_identity_not_linked`), no auto-link
3. Neither exists -> create new `User` (`confirmed=true`, random unusable password hash), create `SocialIdentity`

**FR-11** - After successful user resolution, post-auth behavior MUST match password flow:
- 2FA enabled -> create `PendingTwoFactor`, return `{ twoFactorEnabled: true, sessionId: "..." }`
- no 2FA -> issue session via `SessionIssuer`, return `{ twoFactorEnabled: false, sessionId: "..." }` with auth cookies

**FR-12** - Existing `CompleteTwoFactorCommandHandler` flow remains unchanged.

**FR-13** - Events:
- publish `OAuthUserCreatedEvent` only for new user creation
- publish `OAuthUserSignedInEvent` on every successful OAuth sign-in

### 3.3 SocialIdentity Persistence

**FR-14** - `SocialIdentity` MUST store: `provider`, `providerId`, `userId`, `createdAt`, `lastUsedAt`.

**FR-15** - `(provider, providerId)` MUST be unique.

**FR-16** - `(userId, provider)` MUST be unique to prevent duplicate provider links.

**FR-17** - `lastUsedAt` MUST be updated on every successful OAuth sign-in.

### 3.4 Error Contract

**FR-18** - All endpoint errors MUST be RFC 7807 (`application/problem+json`) with stable machine `error_code`.

**FR-19** - OAuth errors MUST use only documented codes:
`unsupported_provider`, `missing_oauth_parameters`, `invalid_state`, `state_expired`, `provider_mismatch`, `unverified_provider_email`, `provider_unavailable`, `social_identity_not_linked`.

**FR-20** - Callback responses MUST include `Cache-Control: no-store` and `Pragma: no-cache`.

---

## 4. Non-Functional Requirements

**NFR-01 - Rate Limiting**: Apply separate token-bucket policies to:
- `GET /api/auth/social/{provider}` (initiation)
- `GET /api/auth/social/{provider}/callback` (callback)

**NFR-02 - State Expiry**: OAuth state payloads expire after 10 minutes.

**NFR-03 - No Token Persistence**: Provider access tokens MUST NOT be persisted and MUST NOT be logged.

**NFR-04 - Local 2FA Enforcement**: Provider assurance signals are ignored. Local 2FA is authoritative.

**NFR-05 - OAuth Password Strategy**: New OAuth users MUST receive a random high-entropy secret hashed through the standard password hasher. Empty/raw sentinel values are forbidden.

**NFR-06 - Email Verification**: Unverified provider emails MUST be rejected with HTTP 422 (`unverified_provider_email`).

**NFR-07 - HTTPS Callback**: Provider callbacks MUST use HTTPS (dev-only override allowed).

**NFR-08 - Quality Thresholds**: PHPInsights >= 94/100/100/100, Psalm level 0, Deptrac 0 violations, full test pass.

**NFR-09 - Observability + Redaction**: OAuth logs MUST be structured, include correlation IDs and provider context, and MUST redact `code`, `state`, `code_verifier`, access tokens, and raw cookies.

**NFR-10 - Provider HTTP Resilience**: Outbound provider calls MUST enforce explicit connect/read timeouts and bounded retries for transient failures.

**NFR-11 - Concurrency Safety**: Double callback submission MUST be safely handled (first succeeds, subsequent attempts fail as consumed/invalid state).

---

## 5. Scope Summary

| Area                                   | In Scope | Deferred |
|----------------------------------------|----------|----------|
| GitHub OAuth sign-in/sign-up           | yes      |          |
| Google OAuth sign-in/sign-up           | yes      |          |
| PKCE + state + flow binding            | yes      |          |
| Local 2FA enforcement post-OAuth       | yes      |          |
| SocialIdentity persistence             | yes      |          |
| Domain events (created, signed-in)     | yes      |          |
| Auto-link existing account by email    |          | yes      |
| Provider link/unlink management        |          | yes      |
| Additional providers                   |          | yes      |
| Mobile/native OAuth flow variants      |          | yes      |

---

## 6. Success Metrics

- OAuth sign-in (including 2FA gate decision) completes in < 3s p95.
- Zero provider tokens persisted to datastore/logs.
- 100% replay/mix-up negative tests pass.
- Existing auth behavior remains regression-free.
- OAuth-specific test coverage >= 95% (unit + integration for new code).
