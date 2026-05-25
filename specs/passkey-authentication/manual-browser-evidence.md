# Passkey Manual Browser Evidence

This file records sanitized browser/WebAuthn evidence for the passkey
authentication PR. It intentionally excludes bearer tokens, refresh tokens,
credential private material, TOTP secrets, and recovery-code values.

## Run Metadata

- Tester: Codex
- Execution date/time (UTC): 2026-05-25 01:36 UTC
- Tested commit SHA: `c0e6fe896143ecbeb26e0e54796c5eb38f3746e6`
- Repro SHA: `58a46bd848e5b9cff70e11e7dc8593c3f1d734f4`
- Application URL: `https://localhost:65443`
- RP ID: `localhost`
- Origin: `https://localhost:65443`
- Browser: Google Chrome / HeadlessChrome 148
- Authenticator: Chrome DevTools virtual CTAP2 authenticators with resident
  keys, user verification, and automatic presence simulation enabled
- Runtime stack: isolated Docker Compose project
  `user-service-pr286-manual`, PHP 8.4.5, MongoDB 7.0, Redis 8,
  `APP_ENV=test`

## Serialization Defect Found

Before the service wiring fix, live endpoint probing showed
`POST /api/passkeys/signup/options` returned HTTP 400 with detail
`Malformed UTF-8 characters`.

Root cause: Symfony autowired the default application serializer into
`PasskeyJsonTransformer`. That serializer attempted to normalize random binary
WebAuthn challenge bytes as UTF-8 JSON. The fix pins the optional transformer
serializer arguments to `null`, which forces the transformer to build and use
the WebAuthn serializer from `PasskeyWebauthnFactory`.

Post-fix proof: the same endpoint returned HTTP 200 with non-empty
`challenge_id`, base64url `public_key.challenge`, RP ID `localhost`, and
`authenticatorSelection.userVerification=required`.

## Browser Ceremony Run

Sanitized run id: `1779672967201-kekp2o`.

Observed scenarios:

1. Existing-email signup rejection:
   `manual-existing-1779672967201-kekp2o@example.test` was created as a
   baseline account. A later signup-options request for the same email returned
   HTTP 409 and no `challenge_id`.
2. New-email signup ceremony:
   `manual-signup-1779672967201-kekp2o@example.test` requested signup options,
   completed `navigator.credentials.create()` with the returned `public_key`,
   submitted `credential.toJSON()` to `/api/passkeys/signup/complete`, and
   received an authenticated session response with `2fa_enabled=false`.
3. Challenge replay:
   Resubmitting the completed signup challenge returned HTTP 401 and no access
   or refresh token fields.
4. Passkey sign-in before 2FA:
   Sign-in options returned an empty `allowCredentials` collection, the browser
   completed `navigator.credentials.get()`, and sign-in completed with an
   authenticated session response with `2fa_enabled=false`.
5. Authenticated registration:
   The session bearer token from signup was used for
   `/api/passkeys/register/options`. A second virtual authenticator created a
   second credential, and `/api/passkeys/register/complete` returned a
   credential id.
6. 2FA parity:
   The existing `/api/2fa/setup` and `/api/2fa/confirm` flow enabled TOTP and
   returned 8 recovery codes. A later passkey sign-in returned
   `2fa_enabled=true`, included a `pending_session_id`, and omitted access and
   refresh token fields.

## Expiration Run

- Email: `manual-expired-1779673120988@example.test`
- Challenge id: `01KSECHK4BX8HYP4Z2ZE66SXP2`
- TTL override: `PASSKEY_CHALLENGE_TTL_SECONDS=1`

Observed result: after waiting past TTL,
`POST /api/passkeys/signup/complete` returned HTTP 401 with detail
`Invalid or expired passkey challenge.` and no access token field.

## Focused Verification

- `PasskeyAuthEndpointsIntegrationTest::testSignupOptionsReturnsBrowserSafeWebauthnJson`
  plus refresh-token integration coverage passed: 2 tests, 37 assertions.
- `PasskeyJsonTransformerTest` and `PasskeyOptionsFactoryTest` passed: 13 tests,
  73 assertions.
- `bin/console lint:yaml --parse-tags config/services.yaml` passed.
- `bin/console lint:container` passed.
- `./scripts/validate-configuration.sh` passed in the isolated PHP container
  with the known container git-worktree warning.
- Host `git diff --check` passed.
