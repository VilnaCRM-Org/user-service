# Passkey Manual Browser Evidence

This file records sanitized browser/WebAuthn evidence for the passkey
authentication PR. It intentionally excludes bearer tokens, refresh tokens,
credential private material, TOTP secrets, and recovery-code values.

## Run Metadata

- Tester: Codex
- Execution date/time (UTC): 2026-05-25 01:36 UTC
- Tested commit SHA: `c0e6fe896143ecbeb26e0e54796c5eb38f3746e6`
- Repro SHA: `58a46bd848e5b9cff70e11e7dc8593c3f1d734f4`
- Current-head rerun: 2026-06-01T02:23:28.150Z against
  `http://localhost:19081`, using Chrome DevTools virtual CTAP2
  authenticators. The strict BMAD gate records the exact reviewed head with
  `git rev-parse HEAD`.
- Historical application URL: `https://localhost:65443`
- RP ID: `localhost`
- Historical origin: `https://localhost:65443`
- Historical browser: Google Chrome / HeadlessChrome 148
- Historical authenticator: Chrome DevTools virtual CTAP2 authenticators with resident
  keys, user verification, and automatic presence simulation enabled
- Historical runtime stack: isolated Docker Compose project
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

Durable sanitized transcript:
`specs/passkey-authentication/manual-browser-run-1779672967201-kekp2o.sanitized.md`.

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

## Current Source Bridge

The full browser ceremony above was executed at
`c0e6fe896143ecbeb26e0e54796c5eb38f3746e6`. Current source fix
`b6ced150d8eacd4e2d59e099e6c72f043c8c875b` changes the registration options
contract from resident-key `preferred` to resident-key `required`, and adds
OpenAPI success response schemas. No passkey completion handler behavior changed
in that commit. Focused unit and integration verification at the bridge SHA
asserted that browser-safe signup options now include `residentKey=required` and
`requireResidentKey=true`.

## Current PR Head Browser Rerun

Sanitized run id: `1780280604724-451d4e`.

Execution date/time (UTC): `2026-06-01T02:23:28.150Z`.

Durable sanitized transcript:
`specs/passkey-authentication/manual-browser-run-1780280604724-451d4e.sanitized.md`.

Raw local JSON evidence:
`/home/kravtsov/tmp/pr286-manual-webauthn-current.json`.

Runtime source base commit:
`69af2cf13c46f797da7076bff272fa7736e01ce9`. Subsequent remediation edits are
tests, test-container wiring, and evidence docs only.

The current-head rerun used Google Chrome headless through Chrome DevTools
Protocol with virtual CTAP2 authenticators, resident keys, user verification,
and automatic presence simulation enabled. It targeted API origin
`http://localhost:19081` and RP ID `localhost`.

Observed current-head scenarios:

1. New-email signup ceremony returned a challenge id, RP ID `localhost`,
   `residentKey=required`, `2fa=false`, and access/refresh token fields after
   browser credential creation and signup completion.
2. Existing-email signup rejection returned HTTP 409 and no challenge id.
3. Challenge replay rejection returned HTTP 401 and no access token.
4. Passkey sign-in before 2FA returned empty `allowCredentials`,
   `userVerification=required`, `2fa=false`, and access/refresh token fields
   after browser assertion completion.
5. Authenticated registration returned a challenge id, an `excludeCredentials`
   array, `residentKey=required`, and a credential id after browser credential
   creation and registration completion.
6. 2FA setup returned a setup secret and otpauth URI, then 2FA confirmation
   returned 8 recovery codes. Secret and code values are intentionally omitted.
7. Passkey sign-in after 2FA returned `2fa=true`, a pending session id, and no
   access or refresh token fields.
8. Expiration rejection returned HTTP 401 and no access token.

Strict BMAD remediation on 2026-06-01 also added API-level `/api/graphql`
integration tests for passkey mutations, with deterministic completion
serialization coverage through the test-only command bus decorator and real
resolver coverage for options, validation, privacy shape, replay, wrong-user,
and duplicate-conflict behavior.

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
