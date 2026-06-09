# PRD — 2FA brute-force + TOTP replay hardening (#314)

## Problem

The second authentication factor (a 6-digit TOTP, `/^\d{6}$/`) could be both
brute-forced and replayed:

1. **Brute force (CWE-307).** `CompleteTwoFactorCommandHandler` verified the
   code and, on failure, only published an event and threw — it never
   incremented a per-pending-session counter and never invalidated the
   `PendingTwoFactor` (which is consumed only after a *successful* verification).
   A single `pendingSessionId` could therefore be reused for unlimited guesses
   within its 5-minute TTL. The only throttle was the Symfony rate limiter,
   attached purely by REST path (`/api/signin/2fa`). The equivalent GraphQL
   mutation `completeTwoFactor` is served at `POST /api/graphql`, which matched
   no endpoint limiter, so via GraphQL there was effectively no per-user 2FA
   throttling at all.

2. **Replay (CWE-294).** `TOTPValidator::verify()` accepted the previous,
   current, **and next** time-step (a 90-second window) and never recorded that
   a code had been used. The same valid TOTP code could be submitted repeatedly
   to `/api/signin/2fa`, `/api/2fa/confirm`, and `/api/2fa/disable` for up to
   ~90s, enabling a relay/phishing attacker to reuse an observed code. RFC 6238
   §5.2 requires a previously accepted OTP be rejected.

## Functional Requirements

- **FR-1** `PendingTwoFactor` tracks a server-side `failedAttempts` counter
  (max `PendingTwoFactor::MAX_FAILED_ATTEMPTS = 5`).
- **FR-2** On every invalid 2FA code, `CompleteTwoFactorCommandHandler`
  increments and persists the counter; once attempts are exhausted it deletes
  (invalidates) the pending session. This defense is protocol-agnostic and
  therefore covers both REST and GraphQL.
- **FR-3** A pending session whose attempts are already exhausted is rejected up
  front with "Invalid or expired two-factor session."
- **FR-4** `User` records the last accepted TOTP time-step
  (`lastAcceptedTotpTimestep`); a code whose matched time-step is `<=` the stored
  value is rejected as a replay, and an accepted time-step is advanced and
  persisted atomically on success (mirroring the recovery-code consume path).
- **FR-5** `TOTPValidator` accepts only the current and previous time windows
  (the future `+period` window is removed) and exposes the matched time-step via
  `resolveAcceptedTimestep()`.
- **FR-6** The rate limiter is protocol-agnostic for 2FA verification: the
  GraphQL `completeTwoFactor` mutation at `/api/graphql` is subject to the same
  `twofa_verification_ip` (always) and `twofa_verification_user` (when the
  pending session resolves to a user) limiters as the REST endpoint.

## Non-Functional Requirements

- **NFR-Security** Defense in depth: the server-side per-session counter (FR-1/2)
  is the primary, transport-independent brute-force control; the GraphQL rate
  limiter (FR-6) is an additional layer. TOTP replay is closed by per-user
  time-step tracking (FR-4/5), conforming to RFC 6238 §5.2. Disabling 2FA clears
  the stored time-step so a re-enabled secret starts fresh.
- **NFR-Compatibility** No public API, request, or response shape changes. New
  persisted fields are nullable / default-zero and backward compatible with
  existing documents (absent `lastAcceptedTotpTimestep` ⇒ no prior acceptance;
  absent `failedAttempts` ⇒ 0). Existing REST `/api/signin/2fa` behaviour is
  unchanged for legitimate users.
- **NFR-Maintainability** Hexagonal/DDD boundaries preserved (Domain stays
  framework-free; persistence advanced via the existing repository ports). No
  new directories or `*Service` suffixes; no threshold/config relaxation.
  PHPInsights complexity ≥94 %, quality/style 100 %, Deptrac 0, Psalm 0.

## Out of Scope

- Account lockout / notification on repeated 2FA failure beyond invalidating the
  pending session.
- Changing TOTP secret length, period, or algorithm.
- Distributed replay protection across simultaneous in-flight requests (handled
  by the atomic counter advance + persistence, but not a global mutex).
- Rate-limit threshold tuning (env-driven values are unchanged).
