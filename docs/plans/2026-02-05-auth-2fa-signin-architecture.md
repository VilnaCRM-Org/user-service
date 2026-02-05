# Auth Sign-in + 2FA Architecture (BMAD)

Date: 2026-02-05

## Overview
This design extends the User Service with sign-in, 2FA, session cookies, and refresh token rotation using the existing Symfony + API Platform + CQRS patterns. The architecture remains hexagonal: controllers and DTOs in Application, pure entities in Domain, and persistence in Infrastructure.

## Components
- **Controllers / API Platform operations**: handle `/api/signin`, `/api/signin/2fa`, `/api/token`, `/api/users/2fa/setup`, `/api/users/2fa/confirm`.
- **Command handlers**: encapsulate sign-in and refresh flows.
- **Security services**: token generation/rotation, session issuance, 2FA validation.
- **Repositories**: AuthSession, AuthRefreshToken, PendingTwoFactor, User.
- **Auth gate**: request listener enforcing authentication on protected routes.

## Data Model
- **User**: add `twoFactorEnabled` (bool), `twoFactorSecret` (nullable string).
- **auth_sessions**: session cookies with TTL and revoke support.
- **auth_refresh_tokens**: hashed refresh tokens with rotation and grace metadata.
- **auth_pending_2fa**: temporary pending session for 2FA completion.

## API Endpoints
### POST /api/signin
Request: `{ email, password, remember_me }`
Response:
- 2FA disabled: `200` with `{ 2fa_enabled: false, access_token, refresh_token }` and session cookie.
- 2FA enabled: `200` with `{ 2fa_enabled: true, pending_session_id }` only.

### POST /api/signin/2fa
Request: `{ pending_session_id, two_factor_code }`
Response:
- Valid: `200` with `{ 2fa_enabled: true, access_token, refresh_token }` and session cookie.
- Invalid/expired: `401` problem+json.

### POST /api/token (refresh-only)
Request: `{ refresh_token }`
Response:
- Valid: `200` with new `{ access_token, refresh_token }`.
- Invalid/expired/misuse: `401` problem+json.

### POST /api/users/2fa/setup
Auth: required (current user)
Response: `200` with `{ otpauth_uri, secret }`.

### POST /api/users/2fa/confirm
Auth: required (current user)
Request: `{ two_factor_code }`
Response: `204` on success, `401` on invalid code.

## Authentication Gate
A kernel request listener:
- Allows unauthenticated access to `/api/signin`, `/api/signin/2fa`, `/api/token`, `/api/oauth/*`, `/api/health`.
- Checks JWT bearer or session cookie for all other routes.
- Responds with RFC 7807 401 on failure.

## Token Rotation & Grace Window
- Each refresh call issues a new refresh token and marks the prior token as rotated.
- The rotated token can be used once during the grace window (default 60s).
- Reuse after grace triggers session revocation and 401.

## 2FA Behavior
- 2FA is TOTP-compatible (Google Authenticator).
- If 2FA is enabled for a user, JWT issuance requires `two_factor_code` confirmation.
- Setup returns secret and otpauth URI; confirm flips `twoFactorEnabled` to true.

## Error Handling
- Invalid credentials, invalid refresh, and invalid 2FA responses return 401 with problem+json.
- All DTO validation uses `config/validator/validation.yaml`.

## References
- Existing sign-in design doc: `docs/plans/2026-02-04-auth-2fa-signin-design.md`.
