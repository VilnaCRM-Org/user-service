---
stepsCompleted: [init, story-details, task-breakdown, dev-notes]
inputDocuments:
  [
    docs/plans/2026-02-05-auth-2fa-signin-prd.md,
    docs/plans/2026-02-05-auth-2fa-signin-architecture.md,
    docs/plans/2026-02-05-auth-2fa-signin-epic.md,
  ]
workflowType: 'stories'
project_name: 'VilnaCRM User Service — Auth Sign-in + 2FA'
author: 'Valerii'
date: '2026-02-05'
revision: '5 — TEA Party Mode R3 Multi-Model Adversarial Review'
---

# Auth Sign-in + 2FA — Implementation Stories

**Revision:** 5 — TEA Party Mode R3 Multi-Model Adversarial Review (addresses R1 13 + R2 4 + R3 3 critical gaps)

---

# Story 1.3: Domain entities and persistence for sign-in

Status: ready-for-dev

## Story

As a developer,
I want AuthSession, AuthRefreshToken, PendingTwoFactor, and RecoveryCode entities with MongoDB mappings,
so that sign-in state is persisted correctly.

## Acceptance Criteria

1. AuthSession entity persists with id, userId, ipAddress, userAgent, createdAt, expiresAt, revokedAt, rememberMe (AC: FR-09, ADR-01)
2. AuthRefreshToken stores tokenHash as SHA-256 hash, never plaintext; graceUsed defaults to false (AC: NFR-15, ADR-05)
3. PendingTwoFactor stores pending session with TTL (default 5 min); MongoDB TTL index on expiresAt (AC: FR-02)
4. RecoveryCode stores codeHash as SHA-256 hash, never plaintext; usedAt nullable (AC: NFR-42)
5. User entity gains `twoFactorEnabled` (bool) and `twoFactorSecret` (nullable string, encrypted) fields (AC: NFR-16)
6. Doctrine ODM XML mappings exist for all new/modified entities (AC: Architecture Data Model)
7. `make deptrac` reports 0 violations (AC: NFR-28)
8. Repository interfaces defined in Domain layer; implementations in Infrastructure

## Tasks / Subtasks

- [ ] Task 1: Create AuthSession entity (AC: #1)
  - [ ] Create `src/User/Domain/Entity/AuthSession.php` with ULID id, userId, ipAddress, userAgent, createdAt, expiresAt, revokedAt, rememberMe
  - [ ] Create `config/doctrine/AuthSession.mongodb.xml` mapping
  - [ ] Create `AuthSessionRepositoryInterface` in `User/Domain/Repository/`
  - [ ] Create `MongoDBAuthSessionRepository` in `User/Infrastructure/Repository/`
- [ ] Task 2: Create AuthRefreshToken entity (AC: #2)
  - [ ] Create `src/User/Domain/Entity/AuthRefreshToken.php` with graceUsed boolean field
  - [ ] Create `config/doctrine/AuthRefreshToken.mongodb.xml` mapping
  - [ ] Create `AuthRefreshTokenRepositoryInterface` in `User/Domain/Repository/`
  - [ ] Create `MongoDBAuthRefreshTokenRepository` in `User/Infrastructure/Repository/`
  - [ ] Ensure tokenHash field uses SHA-256, not plaintext
- [ ] Task 3: Create PendingTwoFactor entity (AC: #3)
  - [ ] Create `src/User/Domain/Entity/PendingTwoFactor.php`
  - [ ] Create `config/doctrine/PendingTwoFactor.mongodb.xml` mapping with TTL index on `expiresAt`
  - [ ] Create `PendingTwoFactorRepositoryInterface` in `User/Domain/Repository/`
  - [ ] Create `MongoDBPendingTwoFactorRepository` in `User/Infrastructure/Repository/`
- [ ] Task 4: Create RecoveryCode entity (AC: #4)
  - [ ] Create `src/User/Domain/Entity/RecoveryCode.php` with id, userId, codeHash, usedAt
  - [ ] Create `config/doctrine/RecoveryCode.mongodb.xml` mapping
  - [ ] Create `RecoveryCodeRepositoryInterface` in `User/Domain/Repository/`
  - [ ] Create `MongoDBRecoveryCodeRepository` in `User/Infrastructure/Repository/`
- [ ] Task 5: Modify User entity for 2FA (AC: #5)
  - [ ] Add `twoFactorEnabled` and `twoFactorSecret` fields
  - [ ] Update `config/doctrine/User.mongodb.xml`
- [ ] Task 6: Verify architecture (AC: #6, #7)
  - [ ] Run `make deptrac` — 0 violations
  - [ ] Run `make psalm` — 0 errors
- [ ] Task 7: Write unit tests for entities
  - [ ] AuthSession: creation, revocation, expiry check, ipAddress/userAgent storage
  - [ ] AuthRefreshToken: hash storage, rotation, grace check, graceUsed flag
  - [ ] PendingTwoFactor: creation, expiry check
  - [ ] RecoveryCode: hash storage, usage marking

## Dev Notes

- Follow existing entity patterns (see `src/User/Domain/Entity/User.php` for reference)
- Use ULID for all primary keys (existing `DomainUlidType` in `Shared/Infrastructure/DoctrineType`)
- XML mappings in `config/doctrine/` — keep entities annotation-free per project convention
- Repository interfaces in Domain, implementations in Infrastructure — matches existing `UserRepositoryInterface` / `MongoDBUserRepository` pattern
- 2FA secret encryption: use a `TwoFactorSecretEncryptor` service in Infrastructure, injected via interface
- PendingTwoFactor MongoDB TTL index: `<index keys="expiresAt:1" options="expireAfterSeconds:0" />`
- Recovery code format: 8 alphanumeric characters, grouped as `xxxx-xxxx` (~47 bits entropy per code)

### References

- [Source: Architecture Data Model section]
- [Source: PRD NFR-15, NFR-16, NFR-42]
- [Source: Architecture ADR-05]

---

# Story 1.1: Sign-in without 2FA

Status: ready-for-dev

## Story

As a user without 2FA enabled,
I want to sign in with email and password,
so that I receive a session cookie and tokens for API access.

## Acceptance Criteria

1. POST `/api/signin` with valid credentials returns 200 with `{ 2fa_enabled: false, access_token, refresh_token }` (AC: FR-01)
2. Session cookie is set as `__Host-auth_token` with `HttpOnly`, `Secure`, `SameSite=Lax` and contains a signed JWT (AC: DR-08, ADR-01, NFR-54)
3. Invalid credentials return 401 problem+json with `WWW-Authenticate: Bearer` header (AC: FR-09, NFR-56)
4. Response does not distinguish between wrong email and wrong password — including response time (constant-time validation) (AC: OWASP auth, NFR-53)
5. Access token is a signed JWT with 15-min TTL, algorithm pinned to RS256, containing claims: sub, iss, aud, exp, iat, nbf, jti, sid, roles (AC: NFR-05, NFR-38, NFR-50)
6. Refresh token is stored as SHA-256 hash (AC: NFR-15)
7. AuthSession records ipAddress and userAgent (AC: ADR-01, NFR-33)
8. After 20 failed attempts for the same email within 1h, account is locked for 15 min (AC: NFR-55)
9. Sign-in latency remains under 300ms at p95 under normal load (AC: NFR-01)

## Tasks / Subtasks

- [ ] Task 1: Create SignInDto (AC: #1)
  - [ ] `src/User/Application/DTO/SignInDto.php` — email, password, remember_me
  - [ ] Validation rules in `config/validator/validation.yaml`
- [ ] Task 2: Create SignInCommand and Handler (AC: #1, #4, #5, #7, #8)
  - [ ] `src/User/Domain/Command/SignInCommand.php` (implements CommandInterface)
  - [ ] `src/User/Application/CommandHandler/SignInCommandHandler.php`
  - [ ] Check account lockout (Redis counter) before credential validation
  - [ ] Validate credentials via PasswordHasherInterface — MUST hash against dummy even when user not found (constant-time)
  - [ ] Create AuthSession + AuthRefreshToken (recording IP and user-agent)
  - [ ] Generate JWT access token (RS256) with all required claims: sub, iss(`vilnacrm-user-service`), aud(`vilnacrm-api`), exp(+15min), iat, nbf, jti(random UUID), sid(AuthSession ULID), roles
  - [ ] Detect 2FA status and branch accordingly
  - [ ] On failure: increment lockout counter in Redis (key: `signin_lockout:{email}`, TTL: 1h)
  - [ ] Emit `UserSignedIn` or `SignInFailed` domain events (on lockout: emit `AccountLockedOut`)
- [ ] Task 3: Create SignInProcessor (AC: #1, #2, #3)
  - [ ] `src/User/Application/Processor/SignInProcessor.php`
  - [ ] Set `__Host-auth_token` session cookie with JWT value on response (`Path=/`)
  - [ ] On 401: include `WWW-Authenticate: Bearer` header
  - [ ] Return appropriate JSON body
- [ ] Task 4: Register API Platform operation (AC: #1)
  - [ ] Add `signin_http` operation in `config/api_platform/resources/` (new resource or User.yaml)
  - [ ] Route: `POST /api/signin`, public access
- [ ] Task 5: Tests (AC: #1-#9)
  - [ ] Unit: SignInCommandHandler (valid, invalid, 2FA branch, event emission, constant-time, lockout)
  - [ ] Unit: Verify JWT contains all required claims (sub, iss, aud, exp, iat, nbf, jti, sid, roles)
  - [ ] Integration: full sign-in flow
  - [ ] Behat: E2E sign-in scenarios
  - [ ] Behat: account lockout scenario (20 failures → 423)
  - [ ] Timing test: response time for non-existent email ≈ response time for wrong password
  - [ ] Performance test: sign-in p95 remains under 300ms under normal load profile

## Dev Notes

- SignInCommandHandler should emit `UserSignedIn` (success), `SignInFailed` (failure), and `AccountLockedOut` domain events
- Cookie name is `__Host-auth_token` (with `__Host-` prefix for subdomain attack prevention)
- `__Host-` cookie prefix requires `Path=/` and no Domain attribute
- Cookie value is a JWT (same as bearer token), not a PHP session ID — works with `stateless: true`
- Cookie Max-Age: 900 (15 min) or 2592000 (30 days) based on `remember_me` flag
- JWT TTL is 15 minutes (not 1 hour) — limits the revocation window after logout/session invalidation
- JWT must include `sid` claim (AuthSession ULID) — required for logout to identify which session to revoke
- **Constant-time defense:** When email not found, hash a dummy password against a pre-computed dummy hash (same bcrypt cost) to prevent timing-based email enumeration
- **Account lockout:** Use `AccountLockoutServiceInterface` (Domain) / `RedisAccountLockoutService` (Infrastructure)
- Error responses must use `Shared/Application/Provider/ErrorProvider` for RFC 7807 compliance
- All 401 responses must include `WWW-Authenticate: Bearer` header (RFC 7235)
- Generic 401 message: "Invalid credentials" — never leak whether email exists

### References

- [Source: Architecture POST /api/signin, ADR-01]
- [Source: PRD FR-01, NFR-01, UJ-01, UJ-05]

---

# Story 1.2: Sign-in with 2FA detection

Status: ready-for-dev

## Story

As a user with 2FA enabled,
I want sign-in to return a pending session instead of tokens,
so that my account requires a second factor before granting access.

## Acceptance Criteria

1. Valid credentials + `twoFactorEnabled = true` returns `{ 2fa_enabled: true, pending_session_id }` (AC: FR-02)
2. No tokens, no cookie in 2FA response (AC: FR-02)
3. Pending session expires after 5 minutes (configurable via `PENDING_2FA_TTL_SECONDS`) (AC: FR-02)

## Tasks / Subtasks

- [ ] Task 1: Extend SignInCommandHandler for 2FA branch (AC: #1, #2)
  - [ ] Detect `twoFactorEnabled` on User entity
  - [ ] Create PendingTwoFactor record
  - [ ] Return pending_session_id instead of tokens
- [ ] Task 2: Add `PENDING_2FA_TTL_SECONDS` env var (AC: #3)
  - [ ] Default: 300 (5 minutes)
- [ ] Task 3: Tests
  - [ ] Unit: handler 2FA branch
  - [ ] Behat: sign-in with 2FA user

### References

- [Source: Architecture PendingTwoFactor entity]
- [Source: PRD FR-02, UJ-02]

---

# Story 2.1: Complete 2FA sign-in (TOTP)

Status: ready-for-dev

## Story

As a user with a pending 2FA session,
I want to submit my TOTP code,
so that I receive tokens and a session cookie.

## Acceptance Criteria

1. POST `/api/signin/2fa` with valid pending_session_id + code returns 200 with tokens + cookie (AC: FR-03)
2. Expired pending session returns 401 (AC: FR-03)
3. Invalid TOTP code returns 401 but pending session remains valid for retry (AC: FR-03)
4. TOTP verification allows +/- 1 time window for clock skew (AC: NFR-07)

## Tasks / Subtasks

- [ ] Task 1: Create CompleteTwoFactorDto (AC: #1)
  - [ ] `src/User/Application/DTO/CompleteTwoFactorDto.php` — pending_session_id, two_factor_code
  - [ ] Validation: both NotBlank, code is 6-8 characters (TOTP: 6 digits, recovery: `xxxx-xxxx`)
- [ ] Task 2: Create TOTP verification service (AC: #4)
  - [ ] `src/User/Domain/Contract/TOTPVerifierInterface.php`
  - [ ] `src/User/Infrastructure/Service/TOTPVerifier.php` (uses `spomky-labs/otphp`)
  - [ ] Support +/- 1 time window
- [ ] Task 3: Create CompleteTwoFactorCommand + Handler (AC: #1, #2, #3)
  - [ ] Validate pending session exists and not expired
  - [ ] Determine code type: 6 digits = TOTP, `xxxx-xxxx` = recovery code
  - [ ] For TOTP: verify code against user's stored (decrypted) secret
  - [ ] On success: create AuthSession + AuthRefreshToken, delete PendingTwoFactor
  - [ ] On code failure: return error, keep pending session
  - [ ] Emit `TwoFactorCompleted` or `TwoFactorFailed` domain events
- [ ] Task 4: Create CompleteTwoFactorProcessor (AC: #1)
  - [ ] Set session cookie, return tokens
- [ ] Task 5: Tests
  - [ ] Unit: handler with valid/invalid/expired scenarios, TOTP vs recovery code
  - [ ] Behat: full 2FA flow from sign-in to completion

## Dev Notes

- TOTP library: `spomky-labs/otphp` (widely used, PHP 8+ compatible)
- Pending session TTL: 5 minutes (configurable via `PENDING_2FA_TTL_SECONDS`)
- After successful 2FA, the PendingTwoFactor record should be deleted (not just marked)
- Recovery code handling shares this endpoint — see Story 2.5

### References

- [Source: Architecture POST /api/signin/2fa]
- [Source: PRD FR-03, UJ-02, UJ-10]

---

# Story 2.2: 2FA setup for current user

Status: ready-for-dev

## Story

As an authenticated user,
I want to generate a TOTP secret and QR code URI,
so that I can configure Google Authenticator.

## Acceptance Criteria

1. POST `/api/users/2fa/setup` returns 200 with `{ otpauth_uri, secret }` (AC: FR-07)
2. Requires authentication (401 without) (AC: FR-09)
3. `twoFactorEnabled` remains false until confirmed (AC: FR-07)
4. Secret is encrypted before MongoDB persistence (AC: NFR-16)

## Tasks / Subtasks

- [ ] Task 1: Create SetupTwoFactorCommand + Handler (AC: #1, #3, #4)
  - [ ] Generate TOTP secret using TOTP library
  - [ ] Encrypt secret via TwoFactorSecretEncryptor
  - [ ] Store on User entity (twoFactorSecret), keep twoFactorEnabled = false
  - [ ] Return otpauth URI and plaintext secret (one-time display to user)
- [ ] Task 2: Create SetupTwoFactorProcessor (AC: #1)
  - [ ] `src/User/Application/Processor/SetupTwoFactorProcessor.php`
- [ ] Task 3: Register API Platform operation (AC: #2)
  - [ ] Route: `POST /api/users/2fa/setup`, security: `is_granted('ROLE_USER')`
- [ ] Task 4: Tests

## Dev Notes

- The otpauth URI format: `otpauth://totp/VilnaCRM:{email}?secret={secret}&issuer=VilnaCRM`
- Secret is shown once to user in response, then stored encrypted — never returned again
- Encryption: AES-256-GCM via `TwoFactorSecretEncryptor` (Infrastructure), key from `TWO_FACTOR_ENCRYPTION_KEY` env var
- Storage format: `base64(iv + ciphertext + tag)` — 12-byte random IV per encryption

### References

- [Source: Architecture POST /api/users/2fa/setup]
- [Source: PRD FR-07, UJ-04]

---

# Story 2.3: 2FA confirmation with recovery code generation

Status: ready-for-dev

## Story

As an authenticated user who has set up 2FA,
I want to confirm my TOTP setup by submitting a valid code,
so that 2FA is enabled on my account and I receive recovery codes.

## Acceptance Criteria

1. POST `/api/users/2fa/confirm` with valid code returns 200 with `{ recovery_codes }` and sets `twoFactorEnabled = true` (AC: FR-08, FR-16)
2. Invalid code returns 401, `twoFactorEnabled` stays false (AC: FR-08)
3. Requires authentication (AC: FR-09)
4. 8 recovery codes generated, stored as SHA-256 hashes (AC: NFR-42)
5. All sessions except current are revoked on successful 2FA enable (AC: FR-20, NFR-52)

## Tasks / Subtasks

- [ ] Task 1: Create ConfirmTwoFactorDto (AC: #1, #2)
  - [ ] `two_factor_code` — NotBlank, 6 digits
- [ ] Task 2: Create ConfirmTwoFactorCommand + Handler (AC: #1, #2, #4, #5)
  - [ ] Decrypt stored secret, verify code via TOTPVerifier
  - [ ] On success: set `twoFactorEnabled = true`
  - [ ] Generate 8 recovery codes (format: `xxxx-xxxx`)
  - [ ] Store code hashes in RecoveryCode entities
  - [ ] Revoke all sessions except current (same pattern as password change in Story 4.5)
  - [ ] Return plaintext codes in response
  - [ ] Emit `TwoFactorEnabled` domain event + `AllSessionsRevoked` with reason `two_factor_enabled`
- [ ] Task 3: Create ConfirmTwoFactorProcessor + API Platform operation (AC: #3)
  - [ ] Response changed from 204 to 200 (now returns recovery codes)
- [ ] Task 4: Tests
  - [ ] Unit: handler with valid/invalid, recovery code generation
  - [ ] Verify recovery codes stored as SHA-256 hashes

### References

- [Source: Architecture POST /api/users/2fa/confirm]
- [Source: PRD FR-08, FR-16, UJ-04]

---

# Story 2.4: 2FA disable

Status: ready-for-dev

## Story

As an authenticated user with 2FA enabled,
I want to disable 2FA by confirming with a valid code,
so that my account no longer requires a second factor.

## Acceptance Criteria

1. POST `/api/users/2fa/disable` with valid TOTP code returns 204 (AC: FR-15)
2. `twoFactorEnabled` becomes false, `twoFactorSecret` is cleared (AC: FR-15)
3. All recovery codes are invalidated (AC: FR-15)
4. Invalid code returns 401, 2FA remains enabled (AC: FR-15)
5. If 2FA not enabled, returns 403 (AC: FR-15)
6. Requires authentication (AC: FR-09)

## Tasks / Subtasks

- [ ] Task 1: Create DisableTwoFactorDto (AC: #1, #4)
  - [ ] `two_factor_code` — NotBlank (accepts TOTP or recovery code)
- [ ] Task 2: Create DisableTwoFactorCommand + Handler (AC: #1, #2, #3, #4, #5)
  - [ ] Verify 2FA is currently enabled (else 403)
  - [ ] Verify code (TOTP or recovery)
  - [ ] Set `twoFactorEnabled = false`, clear `twoFactorSecret`
  - [ ] Delete all RecoveryCode records for user
  - [ ] Emit `TwoFactorDisabled` domain event
- [ ] Task 3: Create DisableTwoFactorProcessor + API Platform operation (AC: #6)
  - [ ] Route: `POST /api/users/2fa/disable`, security: `is_granted('ROLE_USER')`
- [ ] Task 4: Tests
  - [ ] Unit: handler with valid TOTP, valid recovery code, invalid code, 2FA not enabled
  - [ ] Behat: full disable flow

### References

- [Source: Architecture POST /api/users/2fa/disable]
- [Source: PRD FR-15, UJ-09]

---

# Story 2.5: Complete 2FA sign-in with recovery code

Status: ready-for-dev

## Story

As a user who lost their TOTP device,
I want to use a recovery code to complete sign-in,
so that I can access my account.

## Acceptance Criteria

1. Recovery code (format `xxxx-xxxx`) accepted at `/api/signin/2fa` as `two_factor_code` (AC: FR-17)
2. Valid recovery code returns 200 with tokens + cookie (AC: FR-17)
3. Recovery code marked as used (usedAt set), cannot be reused (AC: FR-17)
4. Used recovery code returns 401 (AC: FR-17)
5. `RecoveryCodeUsed` domain event emitted with remaining code count (AC: NFR-33)
6. When remaining recovery codes <= 2, response includes `recovery_codes_remaining` field and warning message (AC: NFR-68)
7. When remaining recovery codes == 0, response includes prominent warning to regenerate codes (AC: NFR-68)

## Tasks / Subtasks

- [ ] Task 1: Extend CompleteTwoFactorCommandHandler (AC: #1, #2, #3, #4, #6, #7)
  - [ ] Add recovery code detection: if code matches `xxxx-xxxx` format, check RecoveryCode repo
  - [ ] Look up by SHA-256(code) + userId
  - [ ] Verify code not already used (usedAt is null)
  - [ ] On success: mark usedAt, proceed with session creation
  - [ ] Count remaining unused codes; include in response if <= 2
  - [ ] Emit `RecoveryCodeUsed` event with remaining unused count
- [ ] Task 2: Tests
  - [ ] Unit: recovery code validation, used code rejection
  - [ ] Unit: verify warning when remaining codes <= 2
  - [ ] Behat: sign-in with recovery code
  - [ ] Behat: use 7th code, verify `recovery_codes_remaining` in response

### References

- [Source: Architecture POST /api/signin/2fa]
- [Source: PRD FR-17, UJ-10]

---

# Story 2.6: Regenerate recovery codes

Status: ready-for-dev

## Story

As an authenticated user with 2FA enabled,
I want to regenerate my recovery codes,
so that I have fresh codes after using some.

## Acceptance Criteria

1. POST `/api/users/2fa/recovery-codes` returns 200 with `{ recovery_codes }` (8 new codes) (AC: FR-18)
2. All previous recovery codes are invalidated (AC: FR-18)
3. Requires authentication + 2FA enabled (403 if 2FA not enabled) (AC: FR-09)
4. Requires recent high-trust re-auth (password or TOTP within 5 minutes); otherwise returns sudo-mode challenge response (AC: FR-18)

## Tasks / Subtasks

- [ ] Task 1: Create RegenerateRecoveryCodesCommand + Handler (AC: #1, #2, #3, #4)
  - [ ] Verify 2FA is enabled (else 403)
  - [ ] Verify high-trust re-auth window (<= 5 minutes) using session marker (e.g., `highTrustVerifiedAt`)
  - [ ] If window missing/expired: return sudo-mode challenge and do not rotate codes
  - [ ] Delete all existing RecoveryCode records for user
  - [ ] Generate 8 new codes, store hashes
  - [ ] Return plaintext codes
- [ ] Task 2: Create RegenerateRecoveryCodesProcessor + API Platform operation (AC: #1)
  - [ ] Route: `POST /api/users/2fa/recovery-codes`, security: `is_granted('ROLE_USER')`
- [ ] Task 3: Tests
  - [ ] Behat: regeneration succeeds after recent password/TOTP re-auth
  - [ ] Behat: regeneration blocked with sudo-mode challenge when re-auth window expired

### References

- [Source: Architecture POST /api/users/2fa/recovery-codes]
- [Source: PRD FR-18]

---

# Story 3.1: Refresh JWT using refresh token

Status: ready-for-dev

## Story

As an authenticated client,
I want to exchange a refresh token for a new JWT,
so that I can maintain my session without re-authenticating.

## Acceptance Criteria

1. POST `/api/token` with valid refresh_token returns 200 with new tokens (AC: FR-04)
2. Old refresh token is marked as rotated with `rotatedAt` timestamp (AC: ADR-05)
3. Invalid/expired/revoked tokens return 401 (AC: FR-04)
4. Token refresh latency remains under 100ms at p95 under normal load (AC: NFR-02)

## Tasks / Subtasks

- [ ] Task 1: Create RefreshTokenDto (AC: #1)
  - [ ] `refresh_token` — NotBlank
- [ ] Task 2: Create RefreshTokenCommand + Handler (AC: #1, #2, #3)
  - [ ] Find token by SHA-256 hash
  - [ ] Validate: not expired, not revoked
  - [ ] Handle rotation logic (see Story 3.2 for grace window)
  - [ ] **Atomic rotation:** Use MongoDB `findOneAndUpdate` with `rotatedAt: null` precondition to prevent race conditions
  - [ ] Issue new access + refresh tokens (JWT with all required claims including `sid`)
  - [ ] Mark old token with `rotatedAt = now()` (via atomic operation)
  - [ ] Emit `RefreshTokenRotated` domain event
- [ ] Task 3: Create RefreshTokenProcessor + API Platform operation
  - [ ] Route: `POST /api/token`, public access
- [ ] Task 4: Tests
  - [ ] Performance test: token refresh p95 remains under 100ms under normal load profile

### References

- [Source: Architecture ADR-05, POST /api/token]
- [Source: PRD FR-04, NFR-02, UJ-03]

---

# Story 3.2: Refresh token rotation grace window

Status: ready-for-dev

## Story

As a client that may crash during token rotation,
I want a short grace window to reuse a rotated token,
so that crashes do not log me out.

## Acceptance Criteria

1. Rotated token within grace window (60s default) returns 200 with new tokens; sets `graceUsed = true` (AC: FR-05)
2. Rotated token after grace window returns 401 and revokes entire session (AC: FR-06)
3. Rotated token used twice within grace window (`graceUsed` already true) returns 401 and revokes session (AC: FR-06)
4. Grace window configurable via `REFRESH_TOKEN_GRACE_WINDOW_SECONDS` env var
5. Theft detection emits CRITICAL-level audit log (AC: NFR-34)

## Tasks / Subtasks

- [ ] Task 1: Implement grace window logic in RefreshTokenCommandHandler (AC: #1, #2, #3, #5)
  - [ ] Check `rotatedAt` field on token record
  - [ ] If `graceUsed` is true: revoke session, emit `RefreshTokenTheftDetected`, return 401
  - [ ] If within grace and `graceUsed` is false: set `graceUsed = true`, issue new tokens
  - [ ] If after grace: revoke all session tokens, emit `RefreshTokenTheftDetected`, return 401
- [ ] Task 2: Add `REFRESH_TOKEN_GRACE_WINDOW_SECONDS` env var (AC: #4)
  - [ ] Default: 60, inject via services.yaml
- [ ] Task 3: Behat scenarios for all edge cases
  - [ ] Normal rotation
  - [ ] Grace reuse (first time — success)
  - [ ] Grace reuse (second time — theft detection)
  - [ ] Post-grace theft detection
  - [ ] Verify CRITICAL log on theft

### References

- [Source: Architecture ADR-05 pseudocode (corrected)]
- [Source: PRD FR-05, FR-06, NFR-34]

---

# Story 4.0: Test infrastructure for authenticated requests

Status: ready-for-dev

## Story

As a developer,
I want test helpers that inject valid auth tokens into Behat and integration tests,
so that existing tests continue to pass after the firewall is enabled.

## Acceptance Criteria

1. Auth test helpers exist for Behat contexts and integration tests (AC: test infrastructure)
2. Helpers can generate valid bearer tokens for a given user/role (AC: test infrastructure)
3. All existing tests pass with helpers in place (firewall still disabled) (AC: no regression)
4. `make psalm` reports 0 errors for auth-related changes (AC: NFR-29)
5. `make tests-with-coverage` confirms >=90% coverage for new auth code (AC: NFR-30)

## Tasks / Subtasks

- [ ] Task 1: Create test OAuth client and token factory (AC: #1, #2)
  - [ ] Test OAuth client seeded in test database
  - [ ] Token generation helper that creates valid JWTs for tests
  - [ ] Support for ROLE_USER and ROLE_SERVICE tokens
- [ ] Task 2: Create Behat auth context trait (AC: #1)
  - [ ] Mixin or trait for Behat contexts that adds `iAmAuthenticatedAs(user)` step
  - [ ] Injects `Authorization: Bearer <token>` header into subsequent requests
- [ ] Task 3: Create integration test base class helper (AC: #1)
  - [ ] Helper method for PHPUnit integration tests
- [ ] Task 4: Run existing test suite (AC: #3)
  - [ ] Verify all tests pass without modification
  - [ ] Inventory tests that will need auth after firewall is enabled
- [ ] Task 5: Run quality gates for auth changes (AC: #4, #5)
  - [ ] Run `make psalm`
  - [ ] Run `make tests-with-coverage` and verify coverage >=90% for new auth code

## Dev Notes

- This story is a prerequisite for Story 4.1 (firewall enablement)
- Must be merged BEFORE the firewall is turned on
- The test token factory should mimic what League OAuth2 Server produces
- Consider using a test-only symmetric JWT signer for speed in tests

### References

- [Source: TEA Challenge M-08]
- [Source: Architecture ADR-03]
- [Source: PRD NFR-29, NFR-30]

---

# Story 4.1: Enable Symfony security firewall

Status: ready-for-dev

## Story

As the system,
I want the Symfony firewall enabled with OAuth2 authenticator,
so that all requests are authenticated before reaching controllers.

## Acceptance Criteria

1. `api` firewall has `security: true`, `stateless: true`, `oauth2: true` (AC: NFR-04)
2. `oauth` firewall covers `^/(token|\.well-known)` with `security: false` (AC: ADR-03)
3. Unauthenticated requests to `/api/` routes return 401 (AC: FR-09, UJ-05)
4. Existing tests continue to pass (no regression) (AC: Story 4.0 dependency)
5. Auth gate overhead remains below 5ms per request under normal load (AC: NFR-03)

## Tasks / Subtasks

- [ ] Task 1: Update `config/packages/security.yaml` (AC: #1, #2)
  - [ ] Add `oauth` firewall (pattern: `^/(token|\.well-known)`, security: false)
  - [ ] Change `main` to `api` firewall with `security: true`, `stateless: true`, `oauth2: true`
- [ ] Task 2: Implement DualAuthenticator (AC: #1)
  - [ ] `src/Shared/Infrastructure/Security/DualAuthenticator.php`
  - [ ] Check `Authorization: Bearer <token>` header first
  - [ ] Fall back to extracting JWT from `__Host-auth_token` cookie
  - [ ] Pin algorithm to RS256 (reject HS256 or others)
  - [ ] Validate claims: `iss` == `vilnacrm-user-service` (single string, not array), `aud` == `vilnacrm-api`, `nbf <= now`, `exp > now`
  - [ ] Extract `sid` claim for session identification
  - [ ] On failure: return 401 with `WWW-Authenticate: Bearer` header
- [ ] Task 3: Update access_control rules (AC: #2)
  - [ ] Add public allowlist per Architecture ADR-03 (CORRECTED patterns)
  - [ ] Add `ROLE_SERVICE` for batch
  - [ ] Add catch-all `ROLE_USER` for `/api/` and `/api/graphql`
- [ ] Task 4: Update existing tests for auth changes (AC: #4)
  - [ ] Inject auth tokens via helpers from Story 4.0
  - [ ] Run full test suite
- [ ] Task 5: Add auth-gate overhead verification (AC: #5)
  - [ ] Add integration benchmark for middleware + authenticator overhead on protected routes

## Dev Notes

- This is the highest-risk story — it changes auth for ALL existing endpoints
- MUST depend on Story 4.0 (test infrastructure) being complete
- The League OAuth2 bundle provides the `oauth2` authenticator — verify compatibility with Symfony 7.4
- DualAuthenticator reads JWT from `__Host-auth_token` cookie or `Authorization: Bearer` header — both paths go through the same validation
- JWT claims validation is critical: the `fast-jwt` vulnerability showed that `iss` as array bypasses checks — must validate as single string
- The `sid` claim in JWT is essential for Story 6.1 (logout) — without it, can't identify which session to revoke
- Access token TTL is now 15 minutes — this means more frequent refresh token usage, but limits the revocation window

### References

- [Source: Architecture ADR-03]
- [Source: PRD FR-09, NFR-03, NFR-04, UJ-05]

---

# Story 4.2: Access control with public allowlist

Status: ready-for-dev

## Story

As the system,
I want explicit access control rules with a public allowlist,
so that only designated endpoints are accessible without authentication.

## Acceptance Criteria

1. Public endpoints accessible without auth (AC: FR-10)
2. Protected endpoints return 401 without auth (AC: FR-09)
3. Batch endpoint returns 403 for `ROLE_USER` (requires `ROLE_SERVICE`) (AC: FR-11)
4. Route enumeration integration test verifies every route

## Tasks / Subtasks

- [ ] Task 1: Define access_control rules in security.yaml (AC: #1, #2, #3)
  - [ ] Per Architecture ADR-03 specification (CORRECTED patterns — especially `/api/reset-password`)
- [ ] Task 2: Create route enumeration integration test (AC: #4)
  - [ ] Iterate all registered routes via `RouterInterface`
  - [ ] Verify each is either in public allowlist or returns 401 without auth
  - [ ] Special check: `/api/users/batch` returns 403 with ROLE_USER
- [ ] Task 3: Behat scenarios for access control

### References

- [Source: Architecture ADR-03 access_control rules (corrected)]
- [Source: PRD FR-09, FR-10, FR-11]

---

# Story 4.3: Ownership enforcement on user resources (REST + GraphQL)

Status: ready-for-dev

## Story

As the system,
I want write/delete operations on user resources to enforce ownership on both REST and GraphQL,
so that users can only modify their own data.

## Acceptance Criteria

1. PATCH/PUT/DELETE on another user's resource returns 403 via REST (AC: FR-12)
2. PATCH/PUT/DELETE on own resource proceeds normally via REST (AC: FR-12)
3. `updateUser`/`deleteUser`/`resendEmailTo` GraphQL mutations on another user return auth error (AC: FR-12)
4. GET collection and GET item remain accessible to any authenticated user (AC: ADR-03)

## Tasks / Subtasks

- [ ] Task 1: Add `security` expressions to REST operations in User.yaml (AC: #1, #2)
  - [ ] Patch: `"is_granted('ROLE_USER') and object.getId().toRfc4122() == user.getId().toRfc4122()"`
  - [ ] Put: same expression
  - [ ] Delete: same expression
  - [ ] Get/GetCollection: `"is_granted('ROLE_USER')"`
- [ ] Task 2: Add `security` expressions to GraphQL mutations in User.yaml (AC: #3)
  - [ ] `updateUser`: `"is_granted('ROLE_USER') and object.getId().toRfc4122() == user.getId().toRfc4122()"`
  - [ ] `deleteUser`: same expression
  - [ ] `resendEmailTo`: same expression
- [ ] Task 3: Behat scenarios for ownership enforcement (AC: #1-#4)
  - [ ] REST: PATCH/PUT/DELETE own vs. other
  - [ ] GraphQL: updateUser/deleteUser/resendEmailTo own vs. other

### References

- [Source: Architecture ADR-03 ownership expressions (REST + GraphQL)]
- [Source: PRD FR-12, DR-01]

---

# Story 4.4: Disable OAuth password grant

Status: ready-for-dev

## Story

As the system,
I want the OAuth password grant disabled,
so that 2FA cannot be bypassed via the legacy grant type.

## Acceptance Criteria

1. `grant_type=password` returns OAuth error (unsupported grant type) (AC: NFR-41)
2. client_credentials, authorization_code, refresh_token grants still work (AC: NFR-41)

## Tasks / Subtasks

- [ ] Task 1: Update `config/packages/league_oauth2_server.yaml` (AC: #1)
  - [ ] Set `enable_password_grant: false`
- [ ] Task 2: Remove or update UserResolveListener (AC: #1)
  - [ ] Remove listener registration in services.yaml (line ~160-166) — no longer needed
- [ ] Task 3: Tests (AC: #1, #2)
  - [ ] Integration: password grant returns error
  - [ ] Integration: other grants still work
  - [ ] Update existing OAuth Behat scenarios that use password grant

## Dev Notes

- The UserResolveListener is only used for password grant — can be removed entirely
- Existing Behat scenarios in `features/oauth.feature` that test password grant must be updated to expect errors
- Clients previously using password grant must migrate to `POST /api/signin`

### References

- [Source: Architecture ADR-07]
- [Source: PRD NFR-41]

---

# Story 4.5: Password change invalidates other sessions

Status: ready-for-dev

## Story

As the system,
I want password changes to revoke all sessions except the current one,
so that compromised sessions are terminated when the user changes their password.

## Acceptance Criteria

1. After password change, all other AuthSessions for the user are revoked (AC: FR-19, NFR-31, UJ-11)
2. Current session remains valid (AC: FR-19)
3. Revoked sessions' refresh tokens are also revoked (AC: NFR-31)
4. Audit log emitted with reason "password_change" (AC: NFR-33)

## Tasks / Subtasks

- [ ] Task 1: Extend UpdateUser command handler (AC: #1, #2, #3)
  - [ ] After password hash update, query all AuthSessions for userId
  - [ ] Revoke all sessions except current (determined by bearer token's session ID)
  - [ ] Revoke all refresh tokens associated with revoked sessions
  - [ ] Emit `AllSessionsRevoked` domain event with reason
- [ ] Task 2: Tests (AC: #1-#4)
  - [ ] Unit: handler revokes other sessions
  - [ ] Integration: multi-session scenario
  - [ ] Verify audit log emission

## Dev Notes

- Requires knowing the current session ID — extract from the JWT claims in the request
- Only trigger session revocation when `newPassword` is present in the update payload
- Reuse the same revocation logic as `SignOutAllCommandHandler` but exclude current session

### References

- [Source: PRD FR-19, NFR-31, UJ-11]
- [Source: Architecture ADR-08]

---

# Story 5.1: Multi-tier rate limiting (global + existing endpoints)

Status: ready-for-dev

## Story

As the system,
I want endpoint-specific rate limiting for global traffic and existing endpoints,
so that abuse is prevented at each sensitivity level.

## Acceptance Criteria

1. Global anonymous: 100/min per IP, 429 on exceed (AC: NFR-08)
2. Global authenticated: 300/min per IP, 429 on exceed (AC: NFR-08)
3. Registration: 5/min per IP, token bucket (AC: NFR-09)
4. Token exchange: 10/min per client_id, sliding window (AC: NFR-10)
5. Email confirmation: 10/min per IP (AC: NFR-46)
6. User collection: 30/min per IP (AC: NFR-43)
7. User update: 10/min per user (AC: NFR-47)
8. User delete: 3/min per user (AC: NFR-48)
9. Resend confirmation: 3/min per IP + 3/min per target user (AC: NFR-13, NFR-49)
10. All 429 responses include `Retry-After` header + RFC 7807 body (AC: NFR-14)

## Tasks / Subtasks

- [ ] Task 1: Add rate limiter configs to `config/packages/rate_limiter.yaml` (AC: #1-#9)
  - [ ] `global_api_anonymous`: sliding_window, 100/min
  - [ ] `global_api_authenticated`: sliding_window, 300/min
  - [ ] `registration`: token_bucket, 5/min
  - [ ] `oauth_token`: sliding_window, 10/min
  - [ ] `email_confirmation`: sliding_window, 10/min
  - [ ] `user_collection`: sliding_window, 30/min
  - [ ] `user_update`: sliding_window, 10/min
  - [ ] `user_delete`: sliding_window, 3/min
  - [ ] `resend_confirmation`: token_bucket, 3/min
  - [ ] `resend_confirmation_target`: token_bucket, 3/min
- [ ] Task 2: Create ApiRateLimitListener (AC: #1-#10)
  - [ ] `src/Shared/Application/EventListener/ApiRateLimitListener.php`
  - [ ] Register at `kernel.request` priority 120
  - [ ] Resolve endpoint-specific limiter by route + method
  - [ ] Apply global limiter after endpoint-specific
  - [ ] Return 429 with `Retry-After` + problem+json on exceed
- [ ] Task 3: Add env vars for all limits
- [ ] Task 4: Register listener in `config/services.yaml`
- [ ] Task 5: Tests
  - [ ] Unit: listener with mock limiter factories
  - [ ] Integration: verify 429 responses with correct headers

## Dev Notes

- Follow the existing `RateLimitedRequestPasswordResetHandler` decorator pattern for reference
- The listener approach is preferred over per-handler decorators because it catches requests before the auth gate, protecting against pre-auth abuse
- All limits configurable via env vars for production tuning

### References

- [Source: Architecture ADR-02]
- [Source: PRD NFR-08 through NFR-14, NFR-43 through NFR-49]

---

# Story 5.2: Sign-in, 2FA, and auth-specific rate limiting

Status: ready-for-dev

## Story

As the system,
I want sign-in, 2FA, and auth-specific endpoints rate-limited per IP and per account,
so that credential stuffing and brute-force attacks are mitigated.

## Acceptance Criteria

1. Sign-in: 10/min per IP, 5/min per email (AC: NFR-11)
2. 2FA verification: 5/min per user ID + secondary per-IP limiter (AC: NFR-12)
3. 2FA setup: 5/min per user (AC: NFR-44)
4. 2FA confirm: 5/min per user (AC: NFR-45)
5. 2FA disable: 3/min per user
6. Resend confirmation: also 3/min per target user (AC: NFR-49)
7. Account lockout: 20 failed sign-in attempts per email within 1h → 15-min lockout → 423 Locked (AC: NFR-55)
8. 429 with `Retry-After` + RFC 7807 body on exceed; 423 with `Retry-After` on lockout (AC: NFR-14, UJ-06)

## Tasks / Subtasks

- [ ] Task 1: Add rate limiter configs (AC: #1-#7)
  - [ ] `signin_ip`: sliding_window, 10/min
  - [ ] `signin_email`: sliding_window, 5/min
  - [ ] `twofa_verification_user`: sliding_window, 5/min
  - [ ] `twofa_verification_ip`: sliding_window, configurable per-IP guard
  - [ ] `twofa_setup`: sliding_window, 5/min
  - [ ] `twofa_confirm`: sliding_window, 5/min
  - [ ] `twofa_disable`: sliding_window, 3/min
  - [ ] Account lockout: Redis counter `signin_lockout:{email}`, TTL 1h, threshold 20, lockout 15min
- [ ] Task 2: Extend ApiRateLimitListener for sign-in and 2FA routes (AC: #1-#6)
  - [ ] Extract email from request body for per-email limiting
  - [ ] Resolve `pending_session_id` to user ID, then apply per-user 2FA limiter (`rate_limit:2fa:user:{user_id}`)
  - [ ] Apply secondary per-IP 2FA limiter on the same request (`rate_limit:2fa:ip:{ip_address}`)
  - [ ] Extract user ID from token for authenticated 2FA endpoints
- [ ] Task 3: Create AccountLockoutService (AC: #7)
  - [ ] `src/User/Domain/Contract/AccountLockoutServiceInterface.php`
  - [ ] `src/User/Infrastructure/Service/RedisAccountLockoutService.php`
  - [ ] Methods: `isLocked(email)`, `recordFailure(email)`, `resetOnSuccess(email)`
  - [ ] Inject into SignInCommandHandler
- [ ] Task 4: Tests
  - [ ] Behat: verify 429 after exceeding per-IP and per-email limits
  - [ ] Behat: verify 429 after exceeding 2FA attempts
  - [ ] Behat: verify 429 after exceeding 2FA setup/confirm/disable limits
  - [ ] Behat: verify 423 after 20 failed sign-in attempts, then success after 15-min wait

### References

- [Source: Architecture ADR-02, ADR-10]
- [Source: PRD NFR-11, NFR-12, NFR-44, NFR-45, NFR-49, NFR-55, UJ-06]

---

# Story 5.3: Security headers

Status: ready-for-dev

## Story

As the system,
I want security headers on all API responses,
so that the service passes security audits.

## Acceptance Criteria

1. HSTS header present on all production responses (AC: NFR-19)
2. X-Content-Type-Options: nosniff (AC: NFR-20)
3. X-Frame-Options: DENY (AC: NFR-21)
4. Referrer-Policy: strict-origin-when-cross-origin (AC: NFR-22)
5. Content-Security-Policy: default-src 'none'; frame-ancestors 'none' (AC: NFR-23)
6. Server header removed (AC: ADR-04)
7. `Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()` on all responses (AC: NFR-66)
8. External traffic is served with TLS 1.2+ and HSTS in production (AC: NFR-18)

## Tasks / Subtasks

- [ ] Task 1: Update Caddyfile with `header` block (AC: #1-#8)
  - [ ] Add headers to production server block
  - [ ] Remove Server header
  - [ ] Add Permissions-Policy header
  - [ ] Ensure production TLS policy enforces TLS 1.2+ for external traffic
- [ ] Task 2: Behat tests for header presence (AC: #1-#8)
  - [ ] Verify each header on a standard API response

## Dev Notes

- Headers applied at Caddy level (before PHP) for maximum coverage
- No need for a Symfony response listener if Caddy handles it — keeps it simple

### References

- [Source: Architecture ADR-04]
- [Source: PRD NFR-18 through NFR-23]

---

# Story 5.4: GraphQL hardening (introspection, depth, complexity)

Status: ready-for-dev

## Story

As the system,
I want GraphQL introspection disabled in production and query limits enforced,
so that the API schema is not leaked and DoS via complex queries is prevented.

## Acceptance Criteria

1. Introspection query returns error in production (AC: NFR-24)
2. Introspection query returns schema in development (AC: NFR-24)
3. Query depth > 20 returns error (AC: NFR-35)
4. Query complexity > 500 returns error (AC: NFR-36)

## Tasks / Subtasks

- [ ] Task 1: Update `config/packages/api_platform.yaml` (AC: #1-#4)
  - [ ] Add `max_query_depth: 20` and `max_query_complexity: 500` in base config
  - [ ] Add `graphql.introspection: false` under `when@prod`
- [ ] Task 2: Integration tests (AC: #1-#4)
  - [ ] Test with `APP_ENV=prod`: introspection returns error
  - [ ] Test with `APP_ENV=dev`: introspection returns schema
  - [ ] Test deep query (depth > 20): returns error
  - [ ] Test complex query (complexity > 500): returns error

### References

- [Source: Architecture ADR-06]
- [Source: PRD NFR-24, NFR-35, NFR-36, NFR-59, NFR-62]

---

# Story 5.8: JWT key security, GraphQL batch defense, and transport hardening

Status: ready-for-dev

## Story

As the system,
I want key material, GraphQL entrypoint behavior, and transport settings hardened,
so that key compromise and rate limit bypass attacks are prevented.

## Acceptance Criteria

1. JWT private key has 600 permissions (owner read/write only) (AC: NFR-61)
2. JWT public key has 644 permissions (AC: NFR-61)
3. Dockerfile enforces key permissions on build (AC: NFR-61)
4. CI check verifies private key is not world-readable (AC: NFR-61)
5. All auth-related API Platform resources have `graphql: false` (AC: NFR-62)
6. GraphQL introspection shows no sign-in/2FA/sign-out mutations (AC: NFR-62)
7. GraphQL batch requests (JSON arrays to /api/graphql) are rejected with 400 (AC: NFR-59)
8. Implicit OAuth grant disabled in test environment (AC: NFR-64)
9. CORS `allow_credentials: true` with explicit origin in all environments (AC: NFR-65)
10. MongoDB production connection string enables TLS (`?tls=true`) (AC: NFR-17)
11. External traffic is served via TLS 1.2+ with HSTS in production (AC: NFR-18)

## Tasks / Subtasks

- [ ] Task 1: Fix JWT key permissions (AC: #1, #2, #3, #4)
  - [ ] `chmod 600 config/jwt/private.pem`
  - [ ] `chmod 644 config/jwt/public.pem`
  - [ ] Add to Dockerfile: `RUN chmod 600 /app/config/jwt/private.pem`
  - [ ] Add CI check: verify permissions in `make ci` or pre-commit hook
- [ ] Task 2: Exclude auth operations from GraphQL (AC: #5, #6)
  - [ ] Add `graphql: false` to all sign-in, 2FA, token, sign-out resource configs
  - [ ] Integration test: GraphQL introspection shows no auth mutations
- [ ] Task 3: Add GraphQLBatchRejectListener (AC: #7)
  - [ ] `src/Shared/Application/EventListener/GraphQLBatchRejectListener.php`
  - [ ] Register at `kernel.request` priority 130 (before rate limiter at 120)
  - [ ] If path is `/api/graphql` and body is JSON array → 400 Bad Request
  - [ ] Integration test: batch request returns 400
- [ ] Task 4: Disable implicit grant in test (AC: #8)
  - [ ] Set `OAUTH_ENABLE_IMPLICIT_GRANT=0` in `.env.test`
- [ ] Task 5: Fix CORS configuration (AC: #9)
  - [ ] Add `allow_credentials: true` to `nelmio_cors` defaults
  - [ ] Change dev `allow_origin` from `['*']` to explicit origin
  - [ ] Integration test: verify CORS headers include `credentials: true`
- [ ] Task 6: Enforce transport hardening in production config (AC: #10, #11)
  - [ ] Ensure production MongoDB DSN enables `tls=true`
  - [ ] Validate external TLS policy (TLS 1.2+) and HSTS in production edge configuration
  - [ ] Add config validation test for production environment parameters

## Dev Notes

- This story should be implemented EARLY — ideally before Story 4.1 (firewall)
- JWT key permissions are the highest-priority fix (RC-03 in TEA R3)
- GraphQL batch rejection is critical for rate limiting to be effective (RC-01 in TEA R3)
- OWASP API2:2023 explicitly documents GraphQL batching as a rate limit bypass vector

### References

- [Source: TEA R3 RC-01, RC-03, RH-01, RH-02, RH-04]
- [Source: OWASP API2:2023 Broken Authentication]
- [Source: Architecture ADR-04, ADR-06, ADR-12]
- [Source: PRD NFR-17, NFR-18, NFR-59, NFR-61, NFR-62, NFR-64, NFR-65]

---

# Story 5.5: Bcrypt cost upgrade

Status: ready-for-dev

## Story

As the system,
I want password hashing upgraded to bcrypt cost >= 12,
so that brute-force attacks are computationally infeasible.

## Acceptance Criteria

1. security.yaml has `cost: 12` with `migrate_from` for cost 4 (AC: NFR-32)
2. Existing cost-4 hashes are verified and transparently upgraded on login (AC: NFR-32)
3. New registrations use cost 12 (AC: NFR-32)

## Tasks / Subtasks

- [ ] Task 1: Update `config/packages/security.yaml` (AC: #1)
  - [ ] Set `cost: 12`
  - [ ] Add `migrate_from: [{ algorithm: auto, cost: 4 }]`
- [ ] Task 2: Tests (AC: #2, #3)
  - [ ] Integration: register user, verify hash uses cost 12
  - [ ] Integration: sign in with cost-4 hash, verify it gets upgraded

## Dev Notes

- Symfony's password hasher automatically re-hashes when `needsRehash()` returns true
- No database migration needed — happens transparently on next successful authentication
- Cost 12 adds ~200-300ms to password verification — within P95 budget

### References

- [Source: Architecture ADR-09]
- [Source: PRD NFR-32]

---

# Story 5.6: Confirmation token hardening

Status: ready-for-dev

## Story

As the system,
I want confirmation tokens to be at least 32 characters,
so that brute-force guessing is infeasible.

## Acceptance Criteria

1. `CONFIRMATION_TOKEN_LENGTH` set to 32 in `.env` (AC: NFR-37)
2. Generated tokens are 32 characters (AC: NFR-37)

## Tasks / Subtasks

- [ ] Task 1: Update `.env` and `.env.test` (AC: #1)
  - [ ] Change `CONFIRMATION_TOKEN_LENGTH=10` to `CONFIRMATION_TOKEN_LENGTH=32`
- [ ] Task 2: Verify tests pass with longer tokens (AC: #2)

### References

- [Source: PRD NFR-37]
- [Source: TEA Challenge C-13]

---

# Story 5.7: Request body size limit

Status: ready-for-dev

## Story

As the system,
I want request body size limited to 64KB at the proxy level,
so that memory exhaustion attacks are prevented.

## Acceptance Criteria

1. Caddy rejects requests with body > 64KB (AC: NFR-39)
2. Normal API requests (< 64KB) proceed normally (AC: NFR-39)

## Tasks / Subtasks

- [ ] Task 1: Update Caddyfile (AC: #1, #2)
  - [ ] Add `request_body { max_size 64KB }` to each server block
- [ ] Task 2: Test
  - [ ] Behat: send oversized request, verify 413 response

### References

- [Source: Architecture ADR-04]
- [Source: PRD NFR-39]

---

# Story 6.1: Logout (current session)

Status: ready-for-dev

## Story

As an authenticated user,
I want to log out from my current session,
so that my tokens are revoked and my session cookie is cleared.

## Acceptance Criteria

1. POST `/api/signout` returns 204 (AC: FR-13)
2. `__Host-auth_token` cookie cleared via `Set-Cookie` with `Max-Age=0` (AC: FR-13, NFR-54)
3. Current AuthSession revoked (AC: FR-13)
4. All refresh tokens for this session revoked (AC: FR-13)
5. Existing JWT access token expires within 15 min (accepted tradeoff — refresh tokens immediately revoked) (AC: FR-13)
6. Audit log entry emitted (AC: NFR-33)

## Tasks / Subtasks

- [ ] Task 1: Create SignOutCommand + Handler (AC: #3, #4, #5)
  - [ ] Extract current session ID from JWT claims
  - [ ] Revoke AuthSession (set revokedAt)
  - [ ] Revoke all AuthRefreshTokens for session (set revokedAt)
  - [ ] Emit `SessionRevoked` domain event with reason "logout"
- [ ] Task 2: Create SignOutProcessor (AC: #1, #2)
  - [ ] Clear cookie: `Set-Cookie: __Host-auth_token=; Max-Age=0; Path=/; HttpOnly; Secure; SameSite=Lax`
  - [ ] Return 204
- [ ] Task 3: Register API Platform operation
  - [ ] Route: `POST /api/signout`, security: `is_granted('ROLE_USER')`
- [ ] Task 4: Tests
  - [ ] Unit: handler revokes session and tokens
  - [ ] Behat: full logout flow

### References

- [Source: Architecture POST /api/signout]
- [Source: PRD FR-13, UJ-07]

---

# Story 6.2: Sign out everywhere

Status: ready-for-dev

## Story

As an authenticated user,
I want to revoke all my sessions,
so that all devices/clients are logged out.

## Acceptance Criteria

1. POST `/api/signout/all` returns 204 (AC: FR-14)
2. ALL AuthSessions for user revoked (AC: FR-14)
3. ALL refresh tokens for user revoked (AC: FR-14)
4. Auth cookie cleared (AC: FR-14)
5. Audit log entry emitted (AC: NFR-33)

## Tasks / Subtasks

- [ ] Task 1: Create SignOutAllCommand + Handler (AC: #2, #3, #5)
  - [ ] Query all AuthSessions for userId where revokedAt is null
  - [ ] Revoke all sessions
  - [ ] Revoke all associated refresh tokens
  - [ ] Emit `AllSessionsRevoked` domain event with reason "user_initiated"
- [ ] Task 2: Create SignOutAllProcessor (AC: #1, #4)
  - [ ] Clear cookie, return 204
- [ ] Task 3: Register API Platform operation
  - [ ] Route: `POST /api/signout/all`, security: `is_granted('ROLE_USER')`
- [ ] Task 4: Tests
  - [ ] Unit: handler revokes all sessions
  - [ ] Behat: multi-session sign-out

### References

- [Source: Architecture POST /api/signout/all]
- [Source: PRD FR-14, UJ-08]

---

# Story 6.3: Audit logging for auth events

Status: ready-for-dev

## Story

As the system,
I want all authentication events logged with structured JSON,
so that security incidents can be investigated.

## Acceptance Criteria

1. `UserSignedIn` event logs userId, ip, userAgent, twoFactorUsed at INFO level (AC: NFR-33)
2. `SignInFailed` event logs attemptedEmail, ip, reason at WARNING level (AC: NFR-33)
3. `RefreshTokenTheftDetected` event logs sessionId, userId, ip at CRITICAL level (AC: NFR-34)
4. `RecoveryCodeUsed` event logs userId, remainingCodes at WARNING level (AC: NFR-33)
5. `TwoFactorEnabled`/`TwoFactorDisabled` events logged at INFO (AC: NFR-33)
6. `SessionRevoked`/`AllSessionsRevoked` events logged at INFO with reason (AC: NFR-33)

## Tasks / Subtasks

- [ ] Task 1: Create domain event classes (AC: #1-#6)
  - [ ] `src/User/Domain/Event/UserSignedIn.php`
  - [ ] `src/User/Domain/Event/SignInFailed.php`
  - [ ] `src/User/Domain/Event/TwoFactorCompleted.php`
  - [ ] `src/User/Domain/Event/TwoFactorFailed.php`
  - [ ] `src/User/Domain/Event/TwoFactorEnabled.php`
  - [ ] `src/User/Domain/Event/TwoFactorDisabled.php`
  - [ ] `src/User/Domain/Event/SessionRevoked.php`
  - [ ] `src/User/Domain/Event/AllSessionsRevoked.php`
  - [ ] `src/User/Domain/Event/RefreshTokenRotated.php`
  - [ ] `src/User/Domain/Event/RefreshTokenTheftDetected.php`
  - [ ] `src/User/Domain/Event/RecoveryCodeUsed.php`
  - [ ] `src/User/Domain/Event/AccountLockedOut.php`
- [ ] Task 2: Create AuthEventLogSubscriber (AC: #1-#6)
  - [ ] `src/User/Infrastructure/EventSubscriber/AuthEventLogSubscriber.php`
  - [ ] Inject PSR LoggerInterface
  - [ ] Map each event to appropriate log level and structured context
  - [ ] Register via `_instanceof` auto-tagging (DomainEventSubscriberInterface)
- [ ] Task 3: Tests
  - [ ] Unit: subscriber logs correct level and context for each event
  - [ ] Integration: verify log output on sign-in, theft detection, etc.

## Dev Notes

- All events extend the existing `DomainEvent` base class
- Event subscriber follows existing `DomainEventSubscriberInterface` pattern — auto-tagged
- Use Monolog's structured logging (pass context array as second argument)
- CRITICAL level for theft detection ensures it's visible in alerting systems

### References

- [Source: Architecture ADR-08]
- [Source: PRD NFR-33, NFR-34]

---

## Story Dependency Order

```text
1.3 (entities) ─────────────────────────────────────────────┐
                                                             │
4.0 (test infra) ──► 4.1 (firewall) ──► 4.2 (access ctrl)  │
                                                             │
1.1 (sign-in) ──┬──► 1.2 (2FA detect) ──► 2.1 (2FA TOTP)   │
                │                                             │
                │    2.2 (2FA setup) ──► 2.3 (2FA confirm) ──┤
                │                          ▼                  │
                │                        2.4 (2FA disable)    │
                │                          ▼                  │
                │                        2.5 (recovery sign)  │
                │                          ▼                  │
                │                        2.6 (regen codes)    │
                │                                             │
                └──► 3.1 (refresh) ──► 3.2 (grace window)    │
                                                             │
4.3 (ownership REST+GQL)                                     │
4.4 (password grant) ─────────────────────────────────────── │
4.5 (pwd change invalidate) ──────────────────────────────── │
                                                             │
5.1 (rate limit global) ──► 5.2 (rate limit auth)            │
5.3 (security headers)                                       │
5.4 (GraphQL hardening)                                      │
5.5 (bcrypt cost)                                            │
5.6 (confirmation token)                                     │
5.7 (body size limit)                                        │
5.8 (JWT key + GraphQL batch + CORS) ──────────────────────── │
                                                             │
6.1 (logout) ◄────────────────────────────────────────────── │
6.2 (sign-out-all) ◄──────────────────────────────────────── │
6.3 (audit logging) ◄────────────────── depends on all events│
```

## Dev Agent Record

### Agent Model Used

Claude Opus 4.6

### Completion Notes

- All stories aligned with BMAD epic breakdown (6 epics, 28 stories)
- Every acceptance criterion traced back to PRD FR/NFR or Architecture ADR
- Stories expanded substantially from the initial draft based on TEA Party Mode findings
- R2 updates: Stories 1.1, 2.2, 2.3, 3.1, 4.1, 5.2, 6.1, 6.3 updated with R2 findings (4 critical + 7 moderate gaps)
- R2 key changes: JWT claims structure (NFR-50/51), constant-time validation (NFR-53), `__Host-` cookie (NFR-54), account lockout (NFR-55), `WWW-Authenticate` header (NFR-56), AES-256-GCM encryption (NFR-57), atomic refresh rotation (NFR-58), 2FA-enable session invalidation (FR-20/NFR-52)
- R3 updates: New Story 5.8 (JWT key + GraphQL batch + transport + CORS), Story 2.5 updated (recovery code warning), Story 5.3 updated (Permissions-Policy), Story 5.4 updated (GraphQL batching)
- R3 key changes: GraphQL batching bypass defense (NFR-59), JWT key permissions (NFR-61), auth ops excluded from GraphQL (NFR-62), CORS fix (NFR-65), recovery code exhaustion warning (NFR-68), bearer token sidejack accepted risk (NFR-60)
- New stories added (R1): 4.0, 2.4, 2.5, 2.6, 4.4, 4.5, 5.5, 5.6, 5.7, 6.1, 6.2, 6.3
- New story added (R3): 5.8 (JWT key security + GraphQL batch defense + transport + CORS + implicit grant)
- Story dependency graph included for implementation ordering
- Security hardening stories (Epic 5) expanded from 4 to 7 per TEA R1, then to 8 per TEA R3
- New Epic 6 (Session Lifecycle and Observability) created per TEA R1
- OWASP 2025 Top 10 cross-referenced in TEA R2
- OWASP API Security Top 10 2023 + JWT Cheat Sheet cross-referenced in TEA R3
