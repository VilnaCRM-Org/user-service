# Passkey Authentication Implementation Readiness

Canonical source: `specs/passkey-authentication/implementation-readiness.md`.

## Ready

- REST and GraphQL passkey ceremonies use existing API Platform processor and
  resolver patterns.
- MongoDB persistence follows existing ODM XML mapping conventions.
- `web-auth/webauthn-lib` performs WebAuthn cryptographic verification; the
  application does not hand-roll attestation or assertion checks.
- Session issuance reuses `IssuedSessionFactory`, existing token responses, and
  existing sign-in event publishing.
- Passkey option, completion, replay, expiry, 2FA parity, rate-limit, OpenAPI,
  GraphQL, memory, load, and browser evidence are represented in the passkey
  spec bundle.
- `docs/planning` mirrors the active passkey PRD, architecture, epics, and
  readiness report so the current `bmalph implement` release can transition the
  feature into Ralph implementation context.

## Watch Items

- The canonical BMAD source remains `specs/passkey-authentication`; update the
  transition mirror whenever those active passkey planning files change.
- Browser WebAuthn completion depends on an authenticator and remains manual or
  browser-driven evidence until a headless authenticator load harness exists.
- Full local verification should use repository make targets and Docker
  containers, not host PHP commands.

## Implementation Decision

Proceed with the passkey authentication PR. The remaining transition check is
to run `bmalph implement`, then confirm `bmalph status` reports Phase 4
Implementation with status `implementing`.
