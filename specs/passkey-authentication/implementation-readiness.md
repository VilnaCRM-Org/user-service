# Passkey Authentication Implementation Readiness

## Ready

- The feature has clear REST endpoints and uses existing API Platform processor patterns.
- The persistence model follows existing MongoDB ODM XML mapping conventions.
- `web-auth/webauthn-lib` covers core WebAuthn validation, so the application does not hand-roll cryptographic verification.
- Existing `IssuedSessionFactory` can issue tokens after successful passkey sign-up/sign-in.

## Watch Items

- CI may reject large command handlers through PHPMD; keep orchestration small
  and split factories, resolvers, and validators.
- GraphQL is intentionally out of scope for the first PR because WebAuthn credential JSON is nested and browser-shaped.
- New feature verification must execute every local skill or document non-applicability.
- Frontend UI cannot be implemented in this backend repository; document request/response shapes instead.

## Implementation Decision

Proceed with backend REST support in one issue-specific PR for #221.
