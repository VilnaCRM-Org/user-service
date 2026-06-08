# Passkey Authentication Research

## Objective

Implement issue #221: backend support for passkey registration and authentication for new and existing users, following BMALPH/BMAD planning before implementation.

## Current State

- The API exposes REST and GraphQL auth flows through API Platform YAML resources.
- Password sign-in uses `SignInProcessor`, `SignInCommandHandler`, `IssuedSessionFactory`, and `SignInPublisher`.
- User registration creates a `User`, hashes its password, saves it through `UserRepositoryInterface`, and publishes `UserRegisteredEvent`.
- Persistence is Doctrine MongoDB ODM with XML mappings under `config/doctrine/User`.
- Domain entities are framework-free; validation belongs in `config/validator/validation.yaml`.
- Existing 2FA and refresh-token features use short-lived persisted challenge/session entities (`PendingTwoFactor`, `AuthSession`, `AuthRefreshToken`) with TTL indexes.

## Standards And Library Findings

- WebAuthn registration starts when the relying party sends `PublicKeyCredentialCreationOptions` containing relying party data, user data, a random challenge, and supported public key algorithms.
- WebAuthn authentication starts when the relying party sends `PublicKeyCredentialRequestOptions` containing a random challenge and, for username-first flows, allowed credential descriptors.
- The server must persist the options/challenge generated for a ceremony and verify the browser response against those same options.
- `web-auth/webauthn-lib` v5.3 provides `PublicKeyCredentialCreationOptions`, `PublicKeyCredentialRequestOptions`, response denormalizers, and attestation/assertion validators.
- The library explicitly leaves application storage of credential records to the application, so this bounded context needs its own MongoDB credential and challenge entities.

## Feature Surface

Backend REST endpoints:

- `POST /api/passkeys/signup/options`
- `POST /api/passkeys/signup/complete`
- `POST /api/passkeys/register/options`
- `POST /api/passkeys/register/complete`
- `POST /api/passkeys/signin/options`
- `POST /api/passkeys/signin/complete`

The first implementation should expose REST and GraphQL flows. GraphQL passkey
mutations use API Platform's `Iterable` scalar for nested WebAuthn browser JSON.

## Risks

- WebAuthn payloads contain binary values serialized as base64/base64url; the application must preserve raw credential records without lossy string manipulation.
- Username-first sign-in can leak account existence if challenge responses differ for nonexistent users; the initial implementation should keep response shapes generic and document the remaining enumeration-hardening follow-up.
- New passwordless sign-up still needs a `User.password` value for existing entity invariants; the backend should store a generated random hashed password and document password reset as the fallback credential enrollment path.
- CI includes Deptrac, PHP Insights, Psalm, Infection, Behat, K6, and memory soak checks; code must stay small and layer-compliant.

## Source References

- W3C WebAuthn Level 3: registration and authentication ceremonies use `PublicKeyCredentialCreationOptions` and `PublicKeyCredentialRequestOptions`.
- Webauthn Framework v5.3 docs: store the generated options for the verification step; validators check challenge, origin, attestation/assertion, and signature.
