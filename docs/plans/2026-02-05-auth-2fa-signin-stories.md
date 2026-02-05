# Auth Sign-in + 2FA Stories (BMAD)

Date: 2026-02-05

## Story 1: Sign-in without 2FA
As a user without 2FA enabled, I want to sign in with email/password so I can receive a session cookie and tokens.

Acceptance Criteria:
- POST `/api/signin` with valid credentials returns 200.
- Response includes `2fa_enabled=false`, `access_token`, `refresh_token`.
- Session cookie is set with `HttpOnly`, `Secure`, `SameSite=Lax`.

## Story 2: Sign-in with 2FA enabled
As a user with 2FA enabled, I want sign-in to require an extra code so my account is protected.

Acceptance Criteria:
- POST `/api/signin` returns 200 with `2fa_enabled=true` and `pending_session_id`.
- No access/refresh token and no session cookie are returned at this step.

## Story 3: Complete 2FA sign-in
As a user with a pending 2FA session, I want to submit my TOTP code so I can receive tokens and a cookie.

Acceptance Criteria:
- POST `/api/signin/2fa` with valid `pending_session_id` and `two_factor_code` returns 200.
- Response includes `access_token`, `refresh_token`, and sets session cookie.
- Invalid or expired pending sessions return 401 problem+json.

## Story 4: Refresh JWT using refresh token
As an authenticated client, I want to exchange a refresh token for a new JWT so I can keep my session alive.

Acceptance Criteria:
- POST `/api/token` accepts `refresh_token` only.
- Response returns new `access_token` and new `refresh_token`.
- Invalid/expired/abused refresh tokens return 401 problem+json.

## Story 5: Refresh token rotation grace reuse
As a client, I want a short grace window for refresh token rotation so crashes do not log me out.

Acceptance Criteria:
- A rotated token can be reused once within the grace window.
- Reuse after grace or multiple reuse attempts revoke the session and return 401.

## Story 6: 2FA setup for current user
As an authenticated user, I want to generate a TOTP secret and URI so I can configure Google Authenticator.

Acceptance Criteria:
- POST `/api/users/2fa/setup` returns `otpauth_uri` and `secret`.
- Secret is stored but `twoFactorEnabled` remains false until confirmed.

## Story 7: 2FA confirmation for current user
As an authenticated user, I want to confirm my TOTP setup so that 2FA is enabled on my account.

Acceptance Criteria:
- POST `/api/users/2fa/confirm` with valid `two_factor_code` returns 204.
- Invalid codes return 401.
- `twoFactorEnabled` becomes true on success.

## Story 8: Authentication gate for protected routes
As the system, I want to enforce authentication on protected endpoints so unauthenticated requests are rejected.

Acceptance Criteria:
- Requests to protected endpoints without cookie/JWT return 401 problem+json.
- Allowlisted paths (`/api/signin`, `/api/signin/2fa`, `/api/token`, `/api/oauth/*`, `/api/health`) bypass auth.

