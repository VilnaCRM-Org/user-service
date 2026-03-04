---
stepsCompleted: []
workflowType: 'greenfield-fullstack'
inputDocuments: []
version: 3
date: 2026-03-05
authors: [Mary (Analyst), John (PM), Winston (Architect)]
---

# PRD: OAuth Social Sign-In / Sign-Up (GitHub & Google)

## 1. Overview

### 1.1 Problem Statement

The current authentication system requires email + password credentials for every user. This creates friction at sign-up and sign-in. Users expect "Sign in with GitHub" and "Sign in with Google" as baseline capabilities.

### 1.2 Proposed Solution

Implement OAuth 2.0 Authorization Code flow for GitHub, Google, Facebook, and Twitter/X as alternative authentication methods, with defense-in-depth controls (state, PKCE where provider supports it, one-time callback consumption, strict error contracts, local 2FA authority).

All providers require a verified email for account resolution and creation. Providers that do not supply a verified email are rejected with explicit error codes. Provider capability differences (PKCE support, email guarantee, extra profile call) are handled through a capability interface rather than provider-specific branching in core logic.

### 1.3 Goals

- Allow users to authenticate via GitHub, Google, Facebook, or Twitter/X without creating a local password first.
- Maintain security parity with password sign-in: local 2FA is enforced regardless of provider.
- Prevent login CSRF, replay, provider mix-up, and account-takeover-by-autolink risks.
- Keep the `OAuth` bounded context isolated from `User` internals.
- Reject any provider response that does not supply a verified email; never create accounts without a trusted email.

### 1.4 Non-Goals (Explicit Deferrals)

- Account linking management endpoint (link/unlink from user settings) - future epic.
- Automatic linking of existing local users by email during social callback - deferred for security.
- Additional providers (Apple, Microsoft, LinkedIn) - future epic. Facebook and Twitter/X are now in scope for this epic.
- Email-optional OAuth sign-in (users whose provider account has no verified email) - future epic; track drop-off via metrics before prioritising.
- Post-OAuth email collection and verification flow - future epic.
- Parsing provider-side `amr` claims to skip local 2FA - explicitly rejected.
- Mobile/native app OAuth flows - out of scope.

---

## 2. User Stories (High-Level)

| ID    | As a...       | I want to...                                                              | So that...                                                        |
| ----- | ------------- | ------------------------------------------------------------------------- | ----------------------------------------------------------------- |
| US-01 | New user      | Click "Sign in with GitHub" and get an account                            | I do not need to create a password first                          |
| US-02 | New user      | Click "Sign in with Google" and get an account                            | Sign-up is fast                                                   |
| US-03 | Linked user   | Sign in via my linked GitHub/Google/Facebook/Twitter identity             | I can log in without entering password                            |
| US-04 | 2FA user      | Still be prompted for TOTP after OAuth                                    | Security is consistent regardless of auth method                  |
| US-05 | Security team | Reject unsafe linking and replay/mix-up attacks                           | Account takeover and login CSRF risks are controlled              |
| US-06 | New user      | Click "Sign in with Facebook" and get an account using my verified email  | I can use my existing Facebook identity                           |
| US-07 | New user      | Click "Sign in with Twitter/X" and get an account using my verified email | I can use my existing Twitter/X identity                          |
| US-08 | Any user      | See a clear message when my social account has no verified email          | I understand why sign-in failed and how to fix it on the provider |

---

## 3. Functional Requirements

### 3.1 OAuth Initiation

**FR-01** - The system MUST expose `GET /api/auth/social/{provider}` where `{provider}` is one of `github`, `google`, `facebook`, or `twitter`. The supported provider list is enforced by an explicit allowlist registry; free-form provider strings are rejected.

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

**FR-09** - The system MUST fetch provider profile. Each provider adapter implements a capability interface declaring whether PKCE is supported, whether email is always present, and whether an extra profile API call is required:

- GitHub: primary verified email + login (PKCE supported; email always verified)
- Google: verified email + name + provider id (PKCE supported; email always verified)
- Facebook: email + name + provider id via Graph API `/me?fields=id,name,email` call (PKCE supported; email NOT guaranteed — must check presence and verification)
- Twitter/X: email + name + provider id via v2 Users API with `users.read` scope (PKCE supported; email NOT guaranteed — must check presence)

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
`unsupported_provider`, `missing_oauth_parameters`, `invalid_state`, `state_expired`, `provider_mismatch`, `provider_email_unavailable`, `unverified_provider_email`, `provider_unavailable`, `social_identity_not_linked`.

Error code semantics:

- `provider_email_unavailable` (HTTP 422): The provider did not return any email address in the profile response. The user must add a verified email to their provider account before sign-in can succeed.
- `unverified_provider_email` (HTTP 422): The provider returned an email address but it is not marked as verified per provider semantics. Distinct from absence — the email exists but is not trusted.

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

**NFR-12 - Per-Provider Metrics**: The system MUST emit structured metrics for each provider, including:

- `oauth.auth_started` per provider (initiation endpoint hit)
- `oauth.callback_success` per provider
- `oauth.callback_failure` per provider and error code
- `oauth.email_unavailable` per provider (tracks `provider_email_unavailable` rejections)
- `oauth.email_unverified` per provider (tracks `unverified_provider_email` rejections)

These metrics enable data-driven decisions on whether to open a future email-optional OAuth epic.

**NFR-10 - Provider HTTP Resilience**: Outbound provider calls MUST enforce explicit connect/read timeouts and bounded retries for transient failures.

**NFR-11 - Concurrency Safety**: Double callback submission MUST be safely handled (first succeeds, subsequent attempts fail as consumed/invalid state).

---

## 5. Scope Summary

| Area                                           | In Scope | Deferred |
| ---------------------------------------------- | -------- | -------- |
| GitHub OAuth sign-in/sign-up                   | yes      |          |
| Google OAuth sign-in/sign-up                   | yes      |          |
| Facebook OAuth sign-in/sign-up                 | yes      |          |
| Twitter/X OAuth sign-in/sign-up                | yes      |          |
| PKCE + state + flow binding                    | yes      |          |
| Provider capability model (PKCE, email, extra) | yes      |          |
| Local 2FA enforcement post-OAuth               | yes      |          |
| SocialIdentity persistence                     | yes      |          |
| Domain events (created, signed-in)             | yes      |          |
| Per-provider observability metrics             | yes      |          |
| Auto-link existing account by email            |          | yes      |
| Provider link/unlink management                |          | yes      |
| Email-optional OAuth (no-email provider users) |          | yes      |
| Post-OAuth email collection flow               |          | yes      |
| Additional providers (Apple, Microsoft, etc.)  |          | yes      |
| Mobile/native OAuth flow variants              |          | yes      |

---

## 6. UX Copy Requirements

The following client-facing messages MUST be used (or equivalent approved copy) for error states:

| Scenario                           | Error Code                   | Required Copy                                                                                                                                                     |
| ---------------------------------- | ---------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Provider returned no email         | `provider_email_unavailable` | "Sign in with [Provider] requires a verified email address on your [Provider] account. Please add and verify an email in your [Provider] settings and try again." |
| Provider returned unverified email | `unverified_provider_email`  | "Your [Provider] account's email address is not verified. Please verify your email on [Provider] and try again."                                                  |
| Unsupported provider               | `unsupported_provider`       | "This sign-in provider is not supported."                                                                                                                         |
| Existing account not linked        | `social_identity_not_linked` | "An account with this email already exists. Please sign in with your password, then link your [Provider] account in settings."                                    |

These messages must be stable across providers. Frontend implementations MUST substitute `[Provider]` with the display name (GitHub, Google, Facebook, Twitter/X).

---

## 7. Success Metrics

- OAuth sign-in (including 2FA gate decision) completes in < 3s p95 for all four providers.
- Zero provider tokens persisted to datastore/logs.
- 100% replay/mix-up negative tests pass.
- Existing auth behavior remains regression-free.
- OAuth-specific test coverage >= 95% (unit + integration for new code).
- `provider_email_unavailable` and `unverified_provider_email` rejections are observable via per-provider metrics.
