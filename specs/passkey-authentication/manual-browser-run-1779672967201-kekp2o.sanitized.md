# Sanitized Passkey Browser Run Transcript

Run id: `1779672967201-kekp2o`

This transcript is a durable, sanitized artifact for the manual browser/WebAuthn
run summarized in `manual-browser-evidence.md`. It excludes bearer tokens,
refresh tokens, credential private material, TOTP secrets, and recovery-code
values.

## Environment

- Tested source SHA:
  `c0e6fe896143ecbeb26e0e54796c5eb38f3746e6`
- Repro SHA for serializer failure:
  `58a46bd848e5b9cff70e11e7dc8593c3f1d734f4`
- Browser: Google Chrome / HeadlessChrome 148
- Authenticator: Chrome DevTools virtual CTAP2 authenticators with resident
  keys, user verification, and automatic presence simulation enabled
- URL: `https://localhost:65443`
- RP ID: `localhost`
- Origin: `https://localhost:65443`
- Stack: Docker Compose project `user-service-pr286-manual`, PHP 8.4.5,
  MongoDB 7.0, Redis 8, `APP_ENV=test`

## Sanitized Observations

| Scenario                      | Sanitized request/result                                                                                                                                                                                                                   |
| ----------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Existing-email signup options | Baseline account `manual-existing-1779672967201-kekp2o@example.test` was created. A later `POST /api/passkeys/signup/options` for the same email returned HTTP 409 and no `challenge_id`.                                                  |
| New-email signup options      | `POST /api/passkeys/signup/options` for `manual-signup-1779672967201-kekp2o@example.test` returned HTTP 200 with non-empty `challenge_id`, base64url `public_key.challenge`, RP ID `localhost`, and required user verification.            |
| New-email signup complete     | `navigator.credentials.create()` completed with the returned `public_key`; `credential.toJSON()` submitted to `/api/passkeys/signup/complete` returned `2fa_enabled=false` with access and refresh token fields present but redacted here. |
| Challenge replay              | Reusing the completed signup `challengeId` returned HTTP 401 and no access or refresh token fields.                                                                                                                                        |
| Sign-in before 2FA            | `POST /api/passkeys/signin/options` returned an empty `allowCredentials` collection. `navigator.credentials.get()` completed and `/api/passkeys/signin/complete` returned `2fa_enabled=false` with token fields present but redacted here. |
| Authenticated registration    | The redacted bearer token from signup was used for `/api/passkeys/register/options`; a second virtual authenticator created a second credential; `/api/passkeys/register/complete` returned a `credential_id`.                             |
| 2FA parity                    | `/api/2fa/setup` and `/api/2fa/confirm` enabled TOTP and returned 8 recovery codes, all redacted here. A later passkey sign-in returned `2fa_enabled=true`, included `pending_session_id`, and omitted access and refresh token fields.    |
| Expiration                    | With `PASSKEY_CHALLENGE_TTL_SECONDS=1`, challenge `01KSECHK4BX8HYP4Z2ZE66SXP2` submitted after expiry returned HTTP 401 with detail `Invalid or expired passkey challenge.` and no access token field.                                     |

## Current Source Bridge

The later source fix
`b6ced150d8eacd4e2d59e099e6c72f043c8c875b` strengthens registration options
from resident-key `preferred` to resident-key `required`. The browser run above
already used virtual authenticators with resident keys enabled; the current
source-specific proof is the focused integration/unit verification listed in
`run-summary.md`, which asserts the browser JSON now includes
`residentKey=required` and `requireResidentKey=true`.
