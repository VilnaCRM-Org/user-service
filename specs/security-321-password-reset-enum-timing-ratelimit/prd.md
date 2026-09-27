# PRD — Password-reset request: enumeration timing oracle & weak rate limiting (#321)

## Problem

`POST /api/reset-password` returns a deliberately uniform `204` for both
existing and non-existing accounts, but the work performed per branch differs:

1. **User-enumeration timing oracle (CWE-208).** For an unknown email the
   handler (`RequestPasswordResetCommandHandler`) returns immediately after a
   cached `findByEmail()`. For a known email it additionally runs a 32-byte
   CSPRNG token generation, a synchronous MongoDB `persist + flush`, and a
   domain-event publish. The CSPRNG-generation difference made the two branches
   distinguishable by response latency, turning the uniform `204` into an
   account-enumeration oracle.

2. **Weak rate limiting / no normalization / no validation (CWE-307).** The
   only dedicated throttle was the email-keyed `password_reset` decorator at
   **1000 requests/hour per email** (mail-bombing). The request endpoint was
   not registered in `ApiRateLimitRequestResolver::resolveEndpointLimiters`, so
   it had no dedicated per-IP limiter (only the coarse 100/min global anonymous
   cap). The limiter key was the raw, attacker-supplied `email` string, and
   `RequestPasswordResetDto` had no `Assert\Email`/`NotBlank`/length
   constraints, so malformed/oversized/un-normalized keys reached the limiter
   (case/whitespace variants bypassed the per-email cap).

## Functional Requirements

- **FR-1** The password-reset request handler MUST perform the same CSPRNG
  token-generation work regardless of whether the supplied email maps to an
  existing user, so the deterministic per-request compute is constant across
  branches.
- **FR-2** The handler MUST NOT persist a token or publish a
  `PasswordResetRequested` event for a non-existent user (no DB pollution, no
  email send), and MUST still return the uniform `RequestPasswordResetCommandResponse`.
- **FR-3** A dedicated per-IP rate limiter (`password_reset_ip`) MUST apply to
  `POST /api/reset-password` (exact path; `POST /api/reset-password/confirm`
  MUST remain on its own `password_reset_confirm` limiter and MUST NOT match the
  request limiter).
- **FR-4** The per-email password-reset limiter key MUST be normalized
  (`trim` + `strtolower`) before consumption, so case/whitespace variants of the
  same address share one bucket.
- **FR-5** `RequestPasswordResetDto.email` MUST be validated (`NotBlank`,
  `Email`, max length 254) so malformed/oversized input is rejected with `422`
  before reaching the limiter or command bus.

## Non-Functional Requirements

- **NFR-Security** The default per-email cap is lowered from 1000/hour to
  **3/hour**; a per-IP cap of **5/hour** is added. Together with email
  normalization and DTO validation this caps the request volume an attacker can
  generate for enumeration sampling and mail-bombing. No secret/token values are
  logged. Domain layer stays framework-free.
- **NFR-Compatibility** No API contract change: the endpoint still returns
  `204` on success and `429` when throttled; `422` for invalid payload was
  already documented in the OpenAPI spec for this operation. Existing limiter
  wiring and the `password_reset` decorator are preserved. New env vars
  (`PASSWORD_RESET_IP_RATE_LIMIT_*`) are added to `.env` and `.env.test`.
- **NFR-Maintainability** Changes follow existing patterns: the new limiter is
  resolved by a private `resolvePasswordResetLimiter` mirroring
  `resolvePasswordResetConfirmLimiter`; normalization reuses the
  `strtolower(trim())` convention already used for `signin_email`. No new
  directories, no `*Service` suffixes, no threshold/deptrac config changes.

## Out of Scope

- Moving all per-user side effects onto an asynchronous queue (larger refactor;
  residual DB-write timing is instead bounded by the tightened rate limits).
- Changing the confirm endpoint behavior or other limiters.
- GraphQL password-reset mutation (separate resolver path).
