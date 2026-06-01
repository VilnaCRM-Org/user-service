# Passkey Authentication

The API supports username-first WebAuthn passkey flows for account sign-up,
authenticated passkey enrollment, and passkey sign-in.

## Configuration

Set these environment variables for every deployed origin that will call the
browser WebAuthn APIs:

- `PASSKEY_RP_ID`: relying party id, usually the registrable domain such as
  `app.example.com` or `example.com`.
- `PASSKEY_RP_NAME`: relying party display name shown by authenticators.
- `PASSKEY_ALLOWED_ORIGINS`: comma-separated origins allowed in WebAuthn client
  data, for example `https://app.example.com,https://admin.example.com`.
- `PASSKEY_TIMEOUT_SECONDS`: browser ceremony timeout returned in public key
  options.
- `PASSKEY_CHALLENGE_TTL_SECONDS`: server-side TTL for stored WebAuthn
  challenges.
- `PASSKEY_PRODUCTION_TRAFFIC_ENABLED`: production-only release flag for
  passkey REST and GraphQL traffic.
- `PASSKEY_PRODUCTION_MONITORING_READY`: production-only readiness flag for
  the required passkey dashboard and alert controls.

MongoDB stores passkey credential records in `passkey_credentials` and
short-lived ceremony state in `passkey_challenges`. The challenge collection has
a TTL index on `expires_at`.

## Sign-Up Flow

1. Client calls `POST /api/passkeys/signup/options` with `email`, `initials`,
   and optional `displayName`.
2. Client parses the returned `public_key` WebAuthn JSON, then passes the parsed
   options to `navigator.credentials.create()`.
3. Client calls `POST /api/passkeys/signup/complete` with `challengeId`,
   optional `label`, optional `rememberMe`, and the WebAuthn credential JSON.
4. The completion handler verifies attestation, creates the user, stores the
   passkey credential, issues access and refresh tokens, and sets the auth
   cookie.

The options endpoint returns a conflict when the submitted email is already
registered, matching the existing registration behavior. The completion
endpoint re-checks the email and returns a conflict if it was registered after
options were issued.

Registration options require discoverable credentials. This keeps later
email-first passkey sign-in from exposing stored credential descriptors while
still allowing the browser to select the user's credential.

## Authenticated Enrollment

1. Authenticated client calls `POST /api/passkeys/register/options` with an empty
   JSON object.
2. Client parses the returned `public_key` WebAuthn JSON, then passes it to
   `navigator.credentials.create()`.
3. Client calls `POST /api/passkeys/register/complete` with `challengeId`,
   optional `label`, and the WebAuthn credential JSON.
4. The completion handler verifies attestation and stores the passkey for the
   current user.

Existing passkey credential IDs are excluded from the registration options so
authenticators can reject duplicate enrollment when supported.

Registration options require discoverable credentials for the same privacy
reason as sign-up: sign-in options do not reveal user-specific credential
descriptors.

## Sign-In Flow

1. Client calls `POST /api/passkeys/signin/options` with `email` and optional
   `rememberMe`.
2. Client parses the returned `public_key` WebAuthn JSON, then passes it to
   `navigator.credentials.get()`.
3. Client calls `POST /api/passkeys/signin/complete` with `challengeId` and the
   WebAuthn credential JSON.
4. The completion handler verifies assertion, updates the stored credential
   record and counter, then either issues tokens/cookie or returns
   `2fa_enabled` with a `pending_session_id` when the user requires TOTP.

Users without a passkey should keep using the existing password sign-in and
password reset flows. A failed passkey challenge does not remove password-based
fallback.

Passkey sign-in options intentionally omit `allowCredentials` for both known and
unknown emails. Registered passkeys therefore must be discoverable credentials.

## Frontend Notes

The `public_key` response is WebAuthn JSON with base64url-encoded binary
fields, not the final `PublicKeyCredentialCreationOptions` or
`PublicKeyCredentialRequestOptions` object required by browser APIs. Parse it
before starting the browser ceremony:

```js
const signupOptions = await fetch('/api/passkeys/signup/options', request).then(response =>
  response.json()
);
const publicKey = PublicKeyCredential.parseCreationOptionsFromJSON(signupOptions.public_key);
const credential = await navigator.credentials.create({ publicKey });
```

```js
const signInOptions = await fetch('/api/passkeys/signin/options', request).then(response =>
  response.json()
);
const publicKey = PublicKeyCredential.parseRequestOptionsFromJSON(signInOptions.public_key);
const credential = await navigator.credentials.get({ publicKey });
```

For browsers without `PublicKeyCredential.parseCreationOptionsFromJSON()` or
`PublicKeyCredential.parseRequestOptionsFromJSON()`, convert the base64url
fields to `ArrayBuffer` values before calling `navigator.credentials.create()`
or `navigator.credentials.get()`. Creation options need `challenge`, `user.id`,
and each `excludeCredentials[].id` converted. Request options need `challenge`
and each `allowCredentials[].id` converted.

The credential returned by the browser should be submitted as WebAuthn JSON.
Use `credential.toJSON()` when available, or serialize `id`, `rawId`, and binary
response members as base64url strings before submitting completion requests.

GraphQL clients can use the matching passkey auth mutations on `AuthPayload`.
The `credential` input and `publicKey` output use API Platform's `Iterable`
scalar so WebAuthn browser JSON can be passed without flattening.

The generated GraphQL contract exposes these passkey mutation field names:

- `passkeySignUpOptionsUser(input: passkeySignUpOptionsUserInput!)`
- `passkeySignUpCompleteUser(input: passkeySignUpCompleteUserInput!)`
- `passkeySignInOptionsUser(input: passkeySignInOptionsUserInput!)`
- `passkeySignInCompleteUser(input: passkeySignInCompleteUserInput!)`
- `passkeyRegistrationOptionsUser(input: passkeyRegistrationOptionsUserInput!)`
- `passkeyRegistrationCompleteUser(input: passkeyRegistrationCompleteUserInput!)`

Sign-up options:

```graphql
mutation PasskeySignUpOptions($input: passkeySignUpOptionsUserInput!) {
  passkeySignUpOptionsUser(input: $input) {
    user {
      challengeId
      publicKey
    }
  }
}
```

```json
{
  "input": {
    "email": "person@example.test",
    "initials": "PE",
    "displayName": "Person Example"
  }
}
```

Sign-up completion:

```graphql
mutation PasskeySignUpComplete($input: passkeySignUpCompleteUserInput!) {
  passkeySignUpCompleteUser(input: $input) {
    user {
      accessToken
      refreshToken
      credentialId
    }
  }
}
```

```json
{
  "input": {
    "challengeId": "01J00000000000000000000000",
    "credential": {
      "id": "credential-id",
      "rawId": "credential-raw-id",
      "type": "public-key",
      "response": {}
    },
    "label": "Work laptop",
    "rememberMe": true
  }
}
```

Sign-in options:

```graphql
mutation PasskeySignInOptions($input: passkeySignInOptionsUserInput!) {
  passkeySignInOptionsUser(input: $input) {
    user {
      challengeId
      publicKey
    }
  }
}
```

```json
{
  "input": {
    "email": "person@example.test",
    "rememberMe": true
  }
}
```

Sign-in completion:

```graphql
mutation PasskeySignInComplete($input: passkeySignInCompleteUserInput!) {
  passkeySignInCompleteUser(input: $input) {
    user {
      accessToken
      refreshToken
      pendingSessionId
    }
  }
}
```

```json
{
  "input": {
    "challengeId": "01J00000000000000000000000",
    "credential": {
      "id": "credential-id",
      "rawId": "credential-raw-id",
      "type": "public-key",
      "response": {}
    }
  }
}
```

Authenticated registration options:

```graphql
mutation PasskeyRegistrationOptions($input: passkeyRegistrationOptionsUserInput!) {
  passkeyRegistrationOptionsUser(input: $input) {
    user {
      challengeId
      publicKey
    }
  }
}
```

```json
{
  "input": {}
}
```

Authenticated registration completion:

```graphql
mutation PasskeyRegistrationComplete($input: passkeyRegistrationCompleteUserInput!) {
  passkeyRegistrationCompleteUser(input: $input) {
    user {
      credentialId
    }
  }
}
```

```json
{
  "input": {
    "challengeId": "01J00000000000000000000000",
    "credential": {
      "id": "credential-id",
      "rawId": "credential-raw-id",
      "type": "public-key",
      "response": {}
    },
    "label": "Work laptop"
  }
}
```

## Load And Performance Verification

The repeatable k6 coverage targets the server-side start-ceremony paths:

- `passkeySignupOptions`: `POST /api/passkeys/signup/options`
- `passkeySigninOptions`: `POST /api/passkeys/signin/options`
- `passkeyRegistrationOptions`: `POST /api/passkeys/register/options`

The load profiles in `tests/Load/config.json.dist` and
`tests/Load/config.prod.json` require `checks` above `99%`, p99 under `1500ms`
for smoke and average, p99 under `3000ms` for stress, and p99 under `5000ms`
for spike.

Use these documented targets when validating the passkey runtime path:

```bash
make smoke-load-tests
make average-load-tests
make stress-load-tests
make spike-load-tests
make execute-load-tests-script scenario=passkeySignupOptions
make execute-load-tests-script scenario=passkeySigninOptions
make execute-load-tests-script scenario=passkeyRegistrationOptions
```

Full sign-up, registration, and sign-in completion require browser-created
WebAuthn attestation or assertion payloads. k6 does not provide an authenticator,
so completion is covered by integration tests and the manual browser evidence in
`specs/passkey-authentication/`.

## Operations And Monitoring

Passkey endpoints use the existing API metrics and logging path. The
`ApiEndpointBusinessMetricsSubscriber` emits `EndpointInvocations` EMF metrics
with operation names such as `passkey_signup_options_http`,
`passkey_signup_complete_http`, `passkey_register_options_http`,
`passkey_register_complete_http`, `passkey_signin_options_http`, and
`passkey_signin_complete_http`.

Monitor these passkey signals:

- p95 and p99 latency for every passkey REST operation and matching GraphQL
  mutation.
- Request throughput for options endpoints, because each options request stores
  a short-lived challenge.
- 5xx rate above `1%` for five minutes.
- 401, 409, and 422 spikes above normal baseline after a deploy.
- Count of active `passkey_challenges` compared with
  `observed_options_rps * PASSKEY_CHALLENGE_TTL_SECONDS`.
- Count of expired `passkey_challenges` where `expires_at` is older than now;
  any sustained backlog indicates TTL index drift or MongoDB TTL monitor delay.
- `passkey_credentials.credential_id` unique-index conflicts.
- Sign-in completion outcomes, including the existing `2fa_enabled=true` branch.

Alerting expectations:

- Page on passkey 5xx rate above `1%` for five minutes or p99 above `5000ms`
  for ten minutes.
- Page when expired challenge backlog stays non-zero for two TTL windows.
- Open an urgent ticket when 401/422 rates triple after a release, because that
  usually points to RP ID, allowed-origin, timeout, or browser JSON drift.
- Treat credential duplicate conflicts as warning-level unless they spike after
  a deployment.

Runbook:

1. Check service health and MongoDB availability first.
2. Confirm `PASSKEY_RP_ID`, `PASSKEY_RP_NAME`,
   `PASSKEY_ALLOWED_ORIGINS`, `PASSKEY_TIMEOUT_SECONDS`, and
   `PASSKEY_CHALLENGE_TTL_SECONDS` match the deployed frontend origins.
3. Check passkey operation metrics for latency, 5xx rate, and traffic changes.
4. Inspect `passkey_challenges` TTL-index health and expired backlog.
5. Inspect recent 401/422 response bodies for RP/origin, invalid challenge, and
   validation errors.
6. If passkey-only traffic is affected, keep password, OAuth, password reset,
   and TOTP flows available as fallback while the passkey incident is isolated.
7. Record remediation evidence in the PR or incident document, including the
   metric window, config values reviewed, and whether TTL cleanup recovered.

PR evidence collected on 2026-05-25 UTC:

- `passkeySignupOptions` smoke: `checks=100%`, p99 `1.17s`.
- `passkeySigninOptions` smoke: `checks=100%`, p99 `44.92ms`.
- `passkeyRegistrationOptions` smoke: `checks=100%`, p99 `65.03ms`.

Strict BMAD remediation evidence collected on 2026-06-01 UTC:

| Scenario                     | Checks | Smoke p99 | Average p99 | Stress p99 | Spike p99 |
| ---------------------------- | ------ | --------: | ----------: | ---------: | --------: |
| `passkeySignupOptions`       | 100%   |   48.21ms |     78.89ms |    89.48ms |  165.49ms |
| `passkeySigninOptions`       | 100%   |   357.2ms |     67.77ms |   115.23ms |    5.97ms |
| `passkeyRegistrationOptions` | 100%   |   108.6ms |    164.63ms |    73.29ms |  201.87ms |

The run used isolated Compose project `user-service-pr286-passkey-load`, the
MongoDB 7 load-test override, and `make setup-load-test-db` before executing
`tests/Load/execute-load-test.sh <scenario> true true true true` for each
passkey scenario.

Strict BMAD remediation also added API-level `/api/graphql` integration coverage
for passkey sign-up options, sign-in options, authenticated registration
options, completion response shapes, validation, conflict, unauthenticated
access, challenge replay, wrong-user registration, and duplicate credential
errors.

Production passkey traffic is guarded by two release flags:

- `PASSKEY_PRODUCTION_TRAFFIC_ENABLED`: keep `false` until the production
  monitoring controls below are implemented.
- `PASSKEY_PRODUCTION_MONITORING_READY`: set to `true` only after passkey
  latency, traffic, error-rate, active-challenge, expired-challenge, and
  TTL-index monitoring panels exist with alerts for p99 latency, 5xx rate, and
  expired challenge backlog.

In `prod`, passkey REST endpoints and passkey GraphQL mutations return `503`
while traffic is disabled, or while traffic is enabled without monitoring
readiness. The GraphQL gate inspects JSON bodies, raw GraphQL bodies, URL query
strings, and URL-selected `operationName` values. This keeps password, OAuth,
password reset, and TOTP fallback flows available while preventing production
passkey traffic before the required operational controls exist.
