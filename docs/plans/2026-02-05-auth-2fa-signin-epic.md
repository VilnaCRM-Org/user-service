# Epic: Auth Sign-in + 2FA (BMAD)

Date: 2026-02-05
Epic Owner: User Service

## Epic Statement
Implement a secure sign-in experience with optional 2FA, session cookies, and refresh token rotation so that web, mobile, and third-party clients can authenticate reliably and safely.

## Scope
In scope:
- Sign-in and 2FA endpoints.
- Refresh-only token exchange.
- Auth gate for protected routes.
- Session cookies and refresh token rotation.
- TOTP setup and confirmation for the current user.

Out of scope:
- OAuth flow changes.
- Admin 2FA management for other users.
- UI changes.

## Deliverables
- API operations for `/api/signin`, `/api/signin/2fa`, `/api/token`, `/api/users/2fa/setup`, `/api/users/2fa/confirm`.
- Auth gate listener enforcing auth on protected routes.
- Database migrations for sessions, refresh tokens, pending 2FA, and user 2FA fields.
- Full test coverage (unit, integration, Behat) and green CI.

## Milestones
1. Specs approved (PRD + architecture + stories).
2. Core domain + persistence models implemented.
3. Sign-in/2FA flow implementation complete.
4. Refresh token rotation with grace window.
5. Auth gate and error handling complete.
6. Tests and CI green.

## Dependencies
- API Platform configuration patterns and DTO validation.
- Token hashing and JWT issuance services.
- Test fixtures for deterministic examples.

## Risks
- Refresh rotation edge cases causing unexpected 401s.
- Allowlist mistakes in auth gate.
- 2FA code verification drift due to clock skew.

## Definition of Done
- All stories accepted with documented criteria.
- 100% unit/integration/Behat coverage for new flows.
- `make ci` ends with "✅ CI checks successfully passed!".
