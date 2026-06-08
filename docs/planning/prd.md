# Passkey Authentication PRD

Canonical source: `specs/passkey-authentication/prd.md`.

## Executive Summary

Add backend passkey support to user-service so clients can register and
authenticate users with WebAuthn credentials while preserving the service's
existing DDD, CQRS, API Platform, MongoDB, GraphQL, and session-token patterns.

## Vision

Passkeys become a first-class authentication option alongside passwords, social
OAuth, and TOTP. Users can sign up with a passkey, add a device credential to an
existing account, and sign in with phishing-resistant credentials.

## Scope

In scope:

- REST WebAuthn ceremony endpoints for passkey sign-up, authenticated passkey
  enrollment, and passkey sign-in.
- GraphQL passkey mutations for the same ceremonies.
- MongoDB persistence for active challenges and verified credentials.
- WebAuthn verification through `web-auth/webauthn-lib`.
- Unit, integration, Behat, K6, memory, OpenAPI, GraphQL, manual browser, and
  CI evidence for the passkey feature.

Out of scope:

- Frontend UI implementation.
- Enterprise attestation metadata policy.

## Functional Requirements

### FR-1 Start Passkey Sign-Up

`POST /api/passkeys/signup/options` accepts `email`, `initials`, and optional
`displayName`, rejects existing emails, and returns a challenge id plus browser
WebAuthn public key creation options.

### FR-2 Complete Passkey Sign-Up

`POST /api/passkeys/signup/complete` verifies the attestation response against
the stored challenge, creates the user, stores the credential, and issues the
existing access and refresh token response.

### FR-3 Start Authenticated Passkey Registration

`POST /api/passkeys/register/options` requires `ROLE_USER` and returns a
current-user challenge plus creation options without exposing stored credential
records.

### FR-4 Complete Authenticated Passkey Registration

`POST /api/passkeys/register/complete` requires `ROLE_USER`, verifies the
attestation, rejects duplicate credential ids, and stores the credential for the
challenge-bound authenticated user.

### FR-5 Start Passkey Sign-In

`POST /api/passkeys/signin/options` accepts `email` and optional `rememberMe`.
The response shape avoids exposing whether the account or passkeys exist.

### FR-6 Complete Passkey Sign-In

`POST /api/passkeys/signin/complete` verifies the assertion by credential id,
updates the credential record, issues tokens through `IssuedSessionFactory`, and
publishes the sign-in event through `SignInPublisher`.

## Non-Functional Requirements

- Domain entities remain framework-free.
- API Platform, validation, serialization, and Doctrine mapping use YAML/XML
  configuration.
- Challenges are random, single-use, replay-resistant, short-lived, and removed
  through a MongoDB TTL index.
- WebAuthn relying party id, name, allowed origins, timeout, and challenge TTL
  are environment-backed.
- Sign-in option responses preserve privacy and avoid credential enumeration.
- Browser WebAuthn JSON payloads remain stable for REST and GraphQL clients.
- Existing password, OAuth, and TOTP paths remain available as fallbacks.
- Endpoint latency, checks, and challenge backlog are covered by K6 option
  ceremony smoke evidence and the passkey operations runbook.
- CI, static analysis, architecture checks, mutation, OpenAPI, GraphQL,
  Schemathesis, Spectral, memory, and manual browser evidence remain green or
  recorded.
