---
stepsCompleted: []
workflowType: 'greenfield-fullstack'
inputDocuments:
  - 'oauth-social-signin-prd.md'
  - 'oauth-social-signin-architecture.md'
version: 3
date: 2026-03-05
authors: [Bob (Scrum Master), Winston (Architect)]
---

# Epics and Stories: OAuth Social Sign-In / Sign-Up

## NFR Coverage Map

| NFR                                 | Covered by Story               |
| ----------------------------------- | ------------------------------ |
| NFR-01 Rate limiting                | ST-4.3                         |
| NFR-02 State expiry                 | ST-1.4                         |
| NFR-03 No token persistence/logging | ST-3.3, ST-5.3                 |
| NFR-04 Local 2FA authority          | ST-3.3                         |
| NFR-05 Hashed random OAuth password | ST-3.1                         |
| NFR-06 Email verification           | ST-2.1, ST-2.2, ST-2.4, ST-2.5 |
| NFR-07 HTTPS callback               | ST-1.1                         |
| NFR-08 Quality thresholds           | DoD (all stories)              |
| NFR-09 Observability + redaction    | ST-3.4, ST-5.3                 |
| NFR-10 Provider HTTP resilience     | ST-2.3                         |
| NFR-11 Concurrency safety           | ST-1.4, ST-5.2                 |
| NFR-12 Per-provider metrics         | ST-3.4                         |

---

## Epic 1: Security Foundations and Persistence

### ST-1.1 - OAuth Client Configuration Baseline

**Type**: Chore

**Description**:
Add GitHub/Google OAuth client dependencies and secure environment configuration for callback URLs under `/api/auth/social/*/callback`.

**Acceptance Criteria**:

- AC-01: OAuth client dependencies are present in `composer.json` and lock file.
- AC-02: Redirect URI config points to `/api/auth/social/{provider}/callback`.
- AC-03: Dev/test/prod config explicitly documents HTTPS requirement and dev override behavior.
- AC-04: Deptrac and existing tests remain green.

---

### ST-1.2 - SocialIdentity Domain Model and Indexes

**Type**: Feature

**Description**:
Create `SocialIdentity` model and persistence mapping with uniqueness constraints required for race-safe linking.

**Acceptance Criteria**:

- AC-01: `SocialIdentity` includes `id`, `provider`, `providerId`, `userId`, `createdAt`, `lastUsedAt`.
- AC-02: `OAuthProvider` is a string-backed value object (not a closed PHP enum). Allowed values (`github`, `google`, `facebook`, `twitter`) are enforced by `OAuthProviderRegistry`, not by the value object itself.
- AC-03: MongoDB mapping defines unique `(provider, provider_id)` and unique `(user_id, provider)` indexes. The `provider` field is stored as a plain string — no enum-type constraint in the schema.
- AC-04: Unit tests validate provider acceptance for all four supported providers and rejection of an unknown provider string via the registry.

---

### ST-1.3 - OAuthUserProfile Value Object

**Type**: Feature

**Description**:
Create immutable provider profile value object.

**Acceptance Criteria**:

- AC-01: `OAuthUserProfile` is a readonly value object with `email`, `name`, `providerId`, `emailVerified`.
- AC-02: No framework dependency in domain.
- AC-03: Psalm and PHPInsights thresholds are satisfied.

---

### ST-1.4 - Redis OAuth State Repository (One-Time + Bound)

**Type**: Feature

**Description**:
Implement Redis state payload storage and atomic consume behavior with provider and flow-binding verification.

**Acceptance Criteria**:

- AC-01: Stored payload includes `provider`, `codeVerifier`, `flowBindingHash`, `redirectUri`, and creation timestamp.
- AC-02: TTL defaults to 600 seconds, configurable via env.
- AC-03: `validateAndConsume(state, provider, flowBinding)` atomically consumes state and returns payload.
- AC-04: Validation fails for expired, missing, already-consumed, provider-mismatch, and flow-mismatch states.
- AC-05: Unit tests cover successful validation, replay, provider mismatch, and expired state.

---

## Epic 2: Provider Adapters and External Reliability

### ST-2.1 - GitHub OAuth Adapter

**Type**: Feature

**Description**:
Implement GitHub adapter with PKCE support and verified-email enforcement.

**Acceptance Criteria**:

- AC-01: Adapter builds auth URL with `state`, `code_challenge`, `code_challenge_method=S256`.
- AC-02: Token exchange uses `code_verifier`.
- AC-03: Profile lookup selects primary + verified email only.
- AC-04: Unverified/missing verified email raises `UnverifiedProviderEmailException`.
- AC-05: Adapter maps upstream failures to `OAuthProviderException`.
- AC-06: `supportsPkce()` returns `true`; `emailAlwaysVerified()` returns `true`; `requiresExtraProfileCall()` returns `false`.
- AC-07: Unit tests cover happy path and all failure paths including capability method contracts.

---

### ST-2.2 - Google OAuth Adapter

**Type**: Feature

**Description**:
Implement Google adapter with PKCE and verified-email enforcement.

**Acceptance Criteria**:

- AC-01: Adapter builds auth URL with scopes `openid email profile` and PKCE params.
- AC-02: Token exchange uses `code_verifier`.
- AC-03: Profile extraction includes `email`, `name`, `id`, `email_verified`.
- AC-04: Unverified/absent email verification raises `UnverifiedProviderEmailException`.
- AC-05: Upstream failures map to `OAuthProviderException`.
- AC-06: `supportsPkce()` returns `true`; `emailAlwaysVerified()` returns `true`; `requiresExtraProfileCall()` returns `false`.
- AC-07: Unit tests cover success and failures including capability method contracts.

---

### ST-2.3 - Provider HTTP Resilience Controls

**Type**: Feature

**Description**:
Apply explicit timeout/retry policy for provider HTTP calls.

**Acceptance Criteria**:

- AC-01: Connect timeout, total timeout, and max retries are configurable env vars.
- AC-02: Retries are bounded and used only for transient failures.
- AC-03: Non-transient provider errors fail fast.
- AC-04: Unit tests verify retry policy behavior and exception mapping.

---

### ST-2.4 - Facebook OAuth Adapter

**Type**: Feature

**Description**:
Implement Facebook adapter using Graph API with PKCE, verified-email enforcement, and token validation. Facebook requires a separate Graph API call (`/me?fields=id,name,email`) to retrieve profile data; email presence is not guaranteed.

**Acceptance Criteria**:

- AC-01: Adapter builds auth URL with `state`, `code_challenge`, `code_challenge_method=S256`, and scopes `email public_profile`.
- AC-02: Token exchange uses `code_verifier`.
- AC-03: Profile is fetched via Graph API `/me?fields=id,name,email` using the access token.
- AC-04: If the `email` field is absent in the Graph API response, adapter raises `OAuthEmailUnavailableException` (maps to `provider_email_unavailable`, HTTP 422).
- AC-05: Facebook does not return an explicit `email_verified` field — the presence of the email field in the Graph API response is treated as implicit verification. If the field is absent, AC-04 applies.
- AC-06: Upstream HTTP failures map to `OAuthProviderException`.
- AC-07: `supportsPkce()` returns `true`; `emailAlwaysVerified()` returns `false`; `requiresExtraProfileCall()` returns `true`.
- AC-08: Unit tests cover: happy path (email present), email absent, token exchange failure, Graph API failure, capability method contracts.

---

### ST-2.5 - Twitter/X OAuth Adapter

**Type**: Feature

**Description**:
Implement Twitter/X adapter using API v2 with OAuth 2.0 PKCE. Profile data including email requires `users.read` scope and a Users API call. Email is not guaranteed — Twitter/X users may not have a verified email associated with their account.

**Acceptance Criteria**:

- AC-01: Adapter builds auth URL with `state`, `code_challenge`, `code_challenge_method=S256`, and scopes `tweet.read users.read offline.access`.
- AC-02: Token exchange uses `code_verifier` against Twitter v2 token endpoint.
- AC-03: Profile is fetched via Twitter v2 Users API (`/2/users/me?user.fields=id,name,username,email`).
- AC-04: If the `email` field is absent in the Users API response, adapter raises `OAuthEmailUnavailableException` (maps to `provider_email_unavailable`, HTTP 422).
- AC-05: Twitter v2 does not guarantee email even with `users.read` scope — AC-04 is the mandatory guard.
- AC-06: Upstream HTTP failures map to `OAuthProviderException`.
- AC-07: `supportsPkce()` returns `true`; `emailAlwaysVerified()` returns `false`; `requiresExtraProfileCall()` returns `true`.
- AC-08: Unit tests cover: happy path (email present), email absent, token exchange failure, Users API failure, capability method contracts.
- AC-09: Implementation notes: Verify that the chosen `league/oauth2-twitter` package supports Twitter API v2 and PKCE. If the package is unmaintained, implement the adapter directly extending `league/oauth2-client` base classes.

---

## Epic 3: OAuth Application Logic

### ST-3.1 - OAuthUserResolver (No Auto-Link)

**Type**: Feature

**Description**:
Resolve users securely without implicit email auto-linking.

**Acceptance Criteria**:

- AC-01: Resolution order is strict:
  1. existing social identity -> return user
  2. existing local user by email without identity -> throw `SocialIdentityNotLinkedException`
  3. no user -> create user + identity
- AC-02: New OAuth user is marked `confirmed=true`.
- AC-03: New OAuth user password is generated randomly and persisted only as hash.
- AC-04: No raw/empty password value is persisted.
- AC-05: Unit tests cover all paths.

---

### ST-3.2 - InitiateOAuth Command and Handler

**Type**: Feature

**Description**:
Create initiation command flow that generates state, PKCE payload, and flow binding.

**Acceptance Criteria**:

- AC-01: Command accepts provider string; handler validates against enum.
- AC-02: Handler generates cryptographically secure `state` and PKCE verifier/challenge.
- AC-03: Handler stores state payload in Redis with configured TTL.
- AC-04: Handler returns authorization URL and flow-binding cookie payload to controller.
- AC-05: Unit tests cover valid flow and invalid provider.

---

### ST-3.3 - HandleOAuthCallback Command and Handler

**Type**: Feature

**Description**:
Handle callback with strict validation and existing 2FA/session behavior.

**Acceptance Criteria**:

- AC-01: Requires provider, code, state, flow-binding token.
- AC-02: Validates and consumes state atomically.
- AC-03: Rejects provider mismatch and flow mismatch.
- AC-04: Exchanges code using PKCE verifier.
- AC-05: Does not persist/log provider tokens.
- AC-06: Applies resolver outcome, then 2FA/session issuance path.
- AC-07: Publishes sign-in event for successful sign-ins.
- AC-08: Unit tests cover 2FA/no-2FA and all failure paths.

---

### ST-3.4 - OAuth Domain Events and Observability

**Type**: Feature

**Description**:
Define events and logging semantics for OAuth flow.

**Acceptance Criteria**:

- AC-01: `OAuthUserCreatedEvent` and `OAuthUserSignedInEvent` exist and follow domain-event conventions.
- AC-02: Event payloads avoid unnecessary PII (no raw provider tokens; email only if explicitly needed).
- AC-03: Warning/error logs include provider and correlation metadata.
- AC-04: Logs redact `code`, `state`, `code_verifier`, tokens, cookies.
- AC-05: Per-provider structured metrics are emitted for: `oauth.auth_started`, `oauth.callback_success`, `oauth.callback_failure` (tagged by error code), `oauth.email_unavailable`, `oauth.email_unverified`. Metrics include `provider` as a dimension on all events.

---

### ST-3.5 - MongoDB SocialIdentity Repository

**Type**: Feature

**Description**:
Implement persistence repository with race-safe behavior.

**Acceptance Criteria**:

- AC-01: Repository implements interface contract.
- AC-02: `findByProviderAndProviderId`, `findByUserId`, and `save` are implemented.
- AC-03: Duplicate-key races are handled deterministically (idempotent outcome).
- AC-04: Integration tests verify reads/writes and duplicate-key behavior.

---

## Epic 4: HTTP Endpoints and Security Contract

### ST-4.1 - OAuth Initiation Endpoint

**Type**: Feature

**Description**:
Add `GET /api/auth/social/{provider}` endpoint.

**Acceptance Criteria**:

- AC-01: Route dispatches initiation command.
- AC-02: Success returns HTTP 302 redirect to provider.
- AC-03: Response sets secure flow-binding cookie (`HttpOnly`, `Secure`, `SameSite=Lax`).
- AC-04: Response includes `Cache-Control: no-store`.
- AC-05: Unsupported provider returns RFC 7807 with `error_code=unsupported_provider`.

---

### ST-4.2 - OAuth Callback Endpoint

**Type**: Feature

**Description**:
Add `GET /api/auth/social/{provider}/callback` endpoint.

**Acceptance Criteria**:

- AC-01: Route extracts `code`, `state`, and flow-binding cookie.
- AC-02: Missing parameters return RFC 7807 (`missing_oauth_parameters`, 400).
- AC-03: Invalid/expired/replayed state returns RFC 7807 (`invalid_state` or `state_expired`, 422).
- AC-04: Provider mismatch returns RFC 7807 (`provider_mismatch`, 400).
- AC-05: Existing unlinked local account returns RFC 7807 (`social_identity_not_linked`, 409).
- AC-06: Provider returned unverified email returns RFC 7807 (`unverified_provider_email`, 422).
- AC-07: Provider returned no email returns RFC 7807 (`provider_email_unavailable`, 422). Distinct from AC-06: this is absence, not unverified presence.
- AC-08: Provider outage/failure returns RFC 7807 (`provider_unavailable`, 503).
- AC-09: Success payload matches auth shape used by password flow.
- AC-10: Callback response includes `Cache-Control: no-store` and `Pragma: no-cache`.

---

### ST-4.3 - OAuth Rate Limiting Policies

**Type**: Feature

**Description**:
Add dedicated rate-limit policies for initiation and callback routes.

**Acceptance Criteria**:

- AC-01: Separate policies exist for initiation and callback.
- AC-02: Limits and intervals are environment configurable.
- AC-03: Limit exceeded responses are mapped to consistent problem responses.
- AC-04: Functional tests validate limiter behavior.

---

## Epic 5: Testing, Security Validation, and Performance

### ST-5.1 - Unit Test Suite for Adapters and Handlers

**Type**: Test

**Acceptance Criteria**:

- AC-01: All four provider adapters (GitHub, Google, Facebook, Twitter/X) cover: success, unverified email, absent email (`provider_email_unavailable`), token exchange errors, and retry behavior.
- AC-02: Capability methods (`supportsPkce()`, `emailAlwaysVerified()`, `requiresExtraProfileCall()`) are tested for each adapter.
- AC-03: Handlers cover all successful and failure branches.
- AC-04: OAuth-specific unit coverage >= 95%.

---

### ST-5.2 - Integration Tests for End-to-End OAuth Flow

**Type**: Test

**Acceptance Criteria**:

- AC-01: New user social sign-up path succeeds and emits creation event (at least one provider tested end-to-end).
- AC-02: Returning linked social user path succeeds.
- AC-03: Existing unlinked local user path returns 409 `social_identity_not_linked`.
- AC-04: 2FA branch and non-2FA branch both validated.
- AC-05: Replay callback attempt fails after first consume.
- AC-06: Provider mismatch and flow mismatch paths fail correctly.
- AC-07: Facebook/Twitter/X no-email path returns `provider_email_unavailable` (422).
- AC-08: `provider_email_unavailable` and `unverified_provider_email` are distinct in test assertions — not collapsed into a single case.

---

### ST-5.3 - Security Regression Tests

**Type**: Test

**Acceptance Criteria**:

- AC-01: Sensitive values are absent/redacted in logs.
- AC-02: No provider token persistence in DB/cache beyond state payload lifetime.
- AC-03: Error responses always conform to RFC 7807 contract.

---

### ST-5.4 - Smoke and Load Tests for OAuth Endpoints

**Type**: Test

**Acceptance Criteria**:

- AC-01: Initiation endpoint meets p95 redirect latency target.
- AC-02: Callback endpoint included in representative load scenarios with mocked providers.
- AC-03: Existing load suites remain green.

---

## Definition of Done (All Stories)

- [ ] All acceptance criteria pass
- [ ] PHPInsights >= 94/100/100/100 on changed files
- [ ] Psalm level 0, 0 errors
- [ ] Deptrac 0 violations
- [ ] All tests pass (unit, integration, E2E where relevant)
- [ ] `make phpcsfixer` reports no violations
- [ ] OAuth error contract validated against RFC 7807
- [ ] Security regression checklist executed (replay, mismatch, redaction, no token persistence)
