# Passkey Authentication PRD

## Executive Summary

Add backend passkey support to user-service so clients can register and authenticate users with WebAuthn credentials while preserving the service's existing DDD, CQRS, API Platform, MongoDB, and session-token patterns.

## Vision

Passkeys become a first-class authentication option alongside passwords, social OAuth, and TOTP, giving users a phishing-resistant way to sign up, add a device credential, and sign in.

## Scope

In scope:

- REST WebAuthn ceremony endpoints for passkey sign-up, authenticated passkey enrollment, and passkey sign-in.
- MongoDB persistence for active challenges and verified credential records.
- WebAuthn attestation/assertion verification through `web-auth/webauthn-lib`.
- Unit tests and integration documentation.

Out of scope:

- Frontend UI implementation.
- GraphQL passkey mutations.
- Username-less discoverable credential login.
- Enterprise attestation metadata policy.

## Functional Requirements

### FR-1 Start Passkey Sign-Up

`POST /api/passkeys/signup/options` accepts `email`, `initials`, and optional `displayName`. It returns a challenge id and WebAuthn public key creation options.

Acceptance:

- The request validates email and initials using application-layer validation.
- Existing emails are rejected consistently with current registration behavior.
- The returned options use the configured relying party id/name and user verification `required`.
- The pending sign-up challenge expires automatically.

### FR-2 Complete Passkey Sign-Up

`POST /api/passkeys/signup/complete` accepts a challenge id, credential label, remember-me flag, and WebAuthn attestation credential JSON.

Acceptance:

- The backend verifies the attestation response against the stored creation options.
- A new user is created with a generated password hash that is not returned.
- The credential record is persisted and associated with the new user.
- The user receives access and refresh tokens using existing session issuance.

### FR-3 Start Authenticated Passkey Registration

`POST /api/passkeys/register/options` requires `ROLE_USER` and returns a challenge id plus public key creation options for the current user.

Acceptance:

- Existing passkey credential descriptors are excluded from the registration options.
- The pending registration challenge is tied to the authenticated user id.
- The response does not expose stored credential records.

### FR-4 Complete Authenticated Passkey Registration

`POST /api/passkeys/register/complete` requires `ROLE_USER` and verifies the attestation against the stored challenge.

Acceptance:

- A credential can only be stored for the authenticated user tied to the challenge.
- Duplicate credential ids are rejected.
- The persisted credential includes the WebAuthn credential record, user handle, label, timestamps, and counter state.

### FR-5 Start Passkey Sign-In

`POST /api/passkeys/signin/options` accepts `email` and optional `rememberMe`. It returns a challenge id and WebAuthn public key request options.

Acceptance:

- Existing user credentials become the allowed credentials list.
- Nonexistent users and users without passkeys receive the same response shape and cannot authenticate successfully.
- The pending authentication challenge stores the remember-me choice and expires automatically.

### FR-6 Complete Passkey Sign-In

`POST /api/passkeys/signin/complete` accepts a challenge id and WebAuthn assertion credential JSON.

Acceptance:

- The backend looks up the stored credential by credential id and verifies the assertion against the stored request options.
- The credential counter/record is updated after successful verification.
- Tokens are issued through `IssuedSessionFactory`.
- The sign-in event is published through `SignInPublisher`.

## Nonfunctional Requirements

- Domain layer remains framework-free.
- API Platform, validation, serialization, and Doctrine mapping use YAML/XML config.
- Challenges must expire via MongoDB TTL index.
- WebAuthn relying party id, name, allowed origins, and timeout must be configurable via environment parameters.
- CI checks must remain green.

## Non-Functional Requirements

- Security: server-generated challenges must be random, single-use, short-lived, and verified against the original WebAuthn options.
- Privacy: sign-in option responses must avoid exposing credential records or user existence beyond the email-first flow already present in password sign-in.
- Compatibility: options and credential responses must use browser WebAuthn JSON payload shapes.
- Observability: successful passkey sign-ins reuse the existing sign-in publisher path.
- Maintainability: WebAuthn library details stay behind application/infrastructure adapters.
