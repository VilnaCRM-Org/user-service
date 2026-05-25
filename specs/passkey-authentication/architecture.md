# Passkey Authentication Architecture

## Tech Stack

- PHP 8.4
- Symfony 7.4
- API Platform 4.1
- Doctrine MongoDB ODM
- `web-auth/webauthn-lib` 5.3
- PHPUnit, Psalm, Deptrac, PHP Insights, Behat, K6, Infection

## Design

Introduce passkey support inside the existing `User` bounded context.

Domain:

- `PasskeyCredential`: stored credential metadata plus serialized WebAuthn credential record.
- `PasskeyChallenge`: short-lived ceremony state for sign-up, registration, and authentication.
- Repository interfaces for both entities.

Application:

- DTOs for option and completion endpoints.
- Processors for each REST operation. They adapt API Platform payloads and
  dispatch commands through the command bus.
- Commands and command handlers:
  - Start handlers create WebAuthn creation/request options and persist
    challenge state.
  - Complete handlers verify attestation/assertion responses, persist user or
    credential changes, and issue sessions where needed.
- Factories, resolvers, validators, and transformers:
  - `PasskeyOptionsFactory` creates WebAuthn creation/request options.
  - `PasskeyCredentialValidator` deserializes and verifies browser responses.
  - `PasskeyResponseFactory` builds stable JSON response bodies.

Infrastructure:

- MongoDB repositories and XML mappings.
- WebAuthn serializer/validator adapter wiring.
- Base64url helper for stable credential id keys.

## Configuration

Add environment-backed arguments:

- `PASSKEY_RP_ID`
- `PASSKEY_RP_NAME`
- `PASSKEY_ALLOWED_ORIGINS`
- `PASSKEY_TIMEOUT_SECONDS`
- `PASSKEY_CHALLENGE_TTL_SECONDS`

Default local values should be compatible with the existing local API URL.

## Endpoint Flow

1. Options endpoint validates input.
2. Application creates WebAuthn options with a random challenge.
3. Application persists a `PasskeyChallenge` containing options JSON and contextual user data.
4. Complete endpoint loads and consumes the challenge.
5. Application verifies attestation/assertion with `web-auth/webauthn-lib`.
6. Application persists or updates `PasskeyCredential`.
7. Sign-up/sign-in completion issues session tokens through the existing auth session path.

## Layer Boundaries

- Domain objects contain no WebAuthn, Symfony, or Doctrine imports.
- WebAuthn library use stays in Application/Infrastructure factories,
  validators, and transformers.
- Processors only adapt HTTP/API Platform input to command dispatch.
- Repositories are interfaces in Domain and MongoDB implementations in Infrastructure.
