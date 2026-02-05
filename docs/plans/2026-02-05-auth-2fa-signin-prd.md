# Auth Sign-in + 2FA PRD (BMAD)

Date: 2026-02-05
Owner: User Service

## Summary

Deliver a secure sign-in flow that supports both session cookies and JWTs with optional TOTP-based 2FA, aligned with the existing Symfony + API Platform patterns in the User Service. The API must enforce authentication on all protected endpoints while allowing access to sign-in and OAuth flows without credentials.

## Problem Statement

We need to support cookie-based and JWT-based authentication while adding optional 2FA. The current service lacks a cohesive sign-in flow, a 2FA completion flow, and refresh-token rotation. Clients must be able to authenticate via cookies (web) or JWTs (mobile/third-party) and receive consistent RFC 7807 errors when unauthenticated.

## Goals

- Provide `/api/signin` and `/api/signin/2fa` endpoints with clear 2FA behavior.
- Support JWT issuance for stateless clients via refresh tokens.
- Enforce authentication on all protected endpoints with a single request gate.
- Support Google Authenticator-compatible TOTP for 2FA.
- Use safe session/refresh token handling with rotation and a grace window.

## Non-goals

- OAuth flow changes or new OAuth grant types.
- Admin-managed 2FA for other users.
- UI/UX changes outside the API.

## Target Users

- Web and mobile clients consuming the User Service.
- Third-party clients needing JWT access.
- Users opting into 2FA for enhanced security.

## Use Cases

- Standard login (no 2FA) with session cookie and JWT.
- 2FA login with pending session + code confirmation.
- JWT refresh using a refresh token.
- Access control: unauthenticated calls to protected endpoints return 401.

## Requirements

### Functional

- `/api/signin` accepts `email`, `password`, `remember_me`.
- If 2FA disabled: issue session cookie + access + refresh tokens.
- If 2FA enabled: return `pending_session_id` without cookie or tokens.
- `/api/signin/2fa` accepts `pending_session_id`, `two_factor_code` and issues tokens + cookie on success.
- `/api/token` is refresh-only: accepts `refresh_token` and returns new access + refresh tokens.
- `/api/users/2fa/setup` and `/api/users/2fa/confirm` operate on the authenticated current user (Option A).
- Authentication gate allows unauthenticated access to `/api/signin`, `/api/signin/2fa`, `/api/token`, `/api/oauth/*`, `/api/health`.

### Security

- Session cookie uses `HttpOnly`, `Secure`, `SameSite=Lax`.
- `remember_me=false` uses short fixed TTL; `remember_me=true` uses long TTL.
- Refresh token rotation with one-time grace reuse (30–60 seconds).
- 2FA required for JWT issuance when 2FA is enabled.

### Reliability

- Return RFC 7807 problem+json on unauthorized responses.
- Consistent 401 for invalid/expired/abused refresh tokens.

## Success Metrics

- All sign-in and refresh flows pass unit, integration, and Behat coverage.
- Unauthorized requests to protected endpoints consistently return 401.
- 2FA-enabled accounts never receive tokens without code confirmation.
- `make ci` passes.

## Dependencies & Constraints

- Symfony 7.2 + API Platform 4.1.
- Hexagonal architecture + CQRS patterns.
- Validation via `config/validator/validation.yaml`.
- No GraphQL changes for this milestone.

## Spec Decisions / Deviations

- `/api/token` is refresh-only (no email/password grant).
- Session cookie uses `SameSite=Lax` (not Strict) to allow legitimate navigation.
- Grace window for refresh token reuse to handle client crashes.

## Risks

- Token rotation edge cases if clients fail to store new refresh token.
- Incorrect allowlist could expose protected endpoints.
- 2FA setup without confirmation could leave secrets unused.

## Open Questions

- Final grace window duration (default 60 seconds unless otherwise specified).
- Default short TTL value for non-remembered sessions.
