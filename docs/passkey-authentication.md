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

The options endpoint does not reveal whether an email is registered. The
completion endpoint re-checks the email and returns a conflict if it was
registered after options were issued.

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
