# Passkey Authentication Manual Test Checklist

Use this checklist to capture the browser and hardware/software authenticator
evidence required for BMAD FR/NFR sign-off.

## Execution Metadata

- Tester: Codex
- Execution date/time (UTC): 2026-05-25 01:36 UTC
- Commit SHA: based on `58a46bd848e5b9cff70e11e7dc8593c3f1d734f4` plus the
  current passkey serializer wiring fix
- Environment URL: `https://localhost:65443`
- Browser and version: Google Chrome / HeadlessChrome 148
- Operating system/device: local Linux workspace
- Authenticator type: Chrome DevTools virtual CTAP2 authenticators with resident
  keys, user verification, and automatic presence simulation enabled
- RP ID: `localhost`
- Origin: `https://localhost:65443`
- Supporting artifacts: sanitized browser run id `1779672967201-kekp2o`;
  durable sanitized evidence in
  `specs/passkey-authentication/manual-browser-evidence.md`; expiration run
  challenge `01KSECHK4BX8HYP4Z2ZE66SXP2`; focused PHPUnit and configuration
  commands listed in `run-summary.md`
- Overall result: Pass

## Preconditions

- The service is running with the passkey environment values documented in
  `docs/passkey-authentication.md`.
- A registered baseline account exists for existing-email and authenticated
  enrollment checks.
- A registered account with TOTP enabled exists for the 2FA parity check.
- The browser supports WebAuthn JSON helpers or the client uses the documented
  base64url-to-ArrayBuffer fallback.
- Network/API response artifacts are captured without storing secrets,
  credential private material, or bearer tokens.

## Scenario 1: Existing-Email Sign-Up Options Rejection

Steps:

1. Choose an email address that already belongs to a registered account.
2. Submit `POST /api/passkeys/signup/options` with that email, valid initials,
   and an optional display name.
3. Confirm the API returns the documented conflict response.
4. Confirm no browser WebAuthn creation ceremony starts.
5. Confirm no passkey challenge is persisted for the rejected request.

Expected result: the endpoint returns `409`, no `challengeId` is returned, and no
passkey challenge is created.

Observed result: browser run id `1779672967201-kekp2o` created baseline account
`manual-existing-1779672967201-kekp2o@example.test`, then signup options for the
same email returned `409` and did not return a `challenge_id`.

Artifacts: `specs/passkey-authentication/manual-browser-evidence.md`, sanitized
Chrome virtual-authenticator run output.

Result: Pass

## Scenario 2: New-Email Sign-Up Ceremony

Steps:

1. Choose an email address that is not registered.
2. Submit `POST /api/passkeys/signup/options` with that email, valid initials,
   and an optional display name.
3. Start `navigator.credentials.create()` with the returned `public_key`
   options.
4. Submit `POST /api/passkeys/signup/complete` with the returned `challengeId`
   and browser credential JSON.
5. Confirm a user session is issued.
6. Confirm the stored credential can be used for a later passkey sign-in.

Expected result: the browser creates a credential, the API completes sign-up,
the user and credential are persisted, and the response issues the expected
session payload/cookie.

Observed result: browser run id `1779672967201-kekp2o` requested signup options
for `manual-signup-1779672967201-kekp2o@example.test`, created a credential with
`navigator.credentials.create()`, submitted `credential.toJSON()` to
`/api/passkeys/signup/complete`, and received access and refresh tokens with
`2fa_enabled=false`.

Artifacts: `specs/passkey-authentication/manual-browser-evidence.md`, sanitized
Chrome virtual-authenticator run output.

Result: Pass

## Scenario 3: Authenticated Passkey Enrollment

Steps:

1. Sign in with an existing account.
2. Submit `POST /api/passkeys/register/options`.
3. Start `navigator.credentials.create()` with the returned `public_key`
   options.
4. Submit `POST /api/passkeys/register/complete` with the returned
   `challengeId` and browser credential JSON.
5. Confirm the account can sign in with the newly enrolled passkey.

Expected result: authenticated enrollment stores one credential for the current
user and excludes existing credentials when supported.

Observed result: browser run id `1779672967201-kekp2o` used the issued bearer
token, requested authenticated registration options, created a second credential
on a second virtual authenticator, and `/api/passkeys/register/complete` returned
a credential id.

Artifacts: `specs/passkey-authentication/manual-browser-evidence.md`, sanitized
Chrome virtual-authenticator run output.

Result: Pass

## Scenario 4: Passkey Sign-In With 2FA Parity

Steps:

1. Use an account that has both a passkey and TOTP enabled.
2. Submit `POST /api/passkeys/signin/options` for that account.
3. Start `navigator.credentials.get()` with the returned `public_key` options.
4. Submit `POST /api/passkeys/signin/complete` with the returned `challengeId`
   and browser assertion JSON.
5. Confirm the response follows the existing pending 2FA session behavior
   instead of issuing final tokens immediately.

Expected result: passkey assertion succeeds, the API creates a pending 2FA
session, and final tokens/cookies are withheld until the existing 2FA completion
flow finalizes authentication.

Observed result: browser run id `1779672967201-kekp2o` enabled TOTP with
`/api/2fa/setup` and `/api/2fa/confirm`, receiving 8 recovery codes. A later
passkey sign-in returned `2fa_enabled=true` and a `pending_session_id`, and did
not return access or refresh tokens.

Artifacts: `specs/passkey-authentication/manual-browser-evidence.md`, sanitized
Chrome virtual-authenticator run output.

Result: Pass

## Scenario 5: Challenge Reuse And Expiration

Steps:

1. Complete one passkey sign-up, registration, or sign-in challenge
   successfully.
2. Resubmit the same `challengeId` and credential JSON.
3. Start a new ceremony, wait until the configured challenge TTL expires, and
   submit the expired challenge.

Expected result: reused and expired challenges are rejected through the generic
invalid-credential path, and no additional credential/session side effect is
created.

Observed result: browser run id `1779672967201-kekp2o` resubmitted the completed
signup challenge and received `401` without access or refresh tokens. A separate
expiration run used `PASSKEY_CHALLENGE_TTL_SECONDS=1`, waited past expiry for
challenge `01KSECHK4BX8HYP4Z2ZE66SXP2`, and completion returned `401` with
detail `Invalid or expired passkey challenge.` and no access token.

Artifacts: `specs/passkey-authentication/manual-browser-evidence.md`, sanitized
Chrome virtual-authenticator run output.

Result: Pass

## Evidence Update Instructions

After executing the scenarios, update `specs/passkey-authentication/run-summary.md`
with the commit SHA, tester, date/time, browser/authenticator details, scenario
results, and artifact references.
