# Passkey Authentication Architecture

Canonical source: `specs/passkey-authentication/architecture.md`.

## Tech Stack

- PHP 8.4
- Symfony 7.4
- API Platform 4.1
- Doctrine MongoDB ODM
- `web-auth/webauthn-lib` 5.3
- PHPUnit, Behat, Psalm, Deptrac, PHP Insights, Infection, Schemathesis,
  Spectral, K6, and memory leak tests

## Design

Passkey support lives inside the existing `User` bounded context.

Domain:

- `PasskeyCredential` stores credential metadata and serialized WebAuthn
  credential records.
- `PasskeyChallenge` stores short-lived ceremony state.
- Repository interfaces stay in Domain and keep framework details out.

Application:

- DTOs model option and completion payloads.
- API Platform processors adapt REST payloads and dispatch commands.
- GraphQL resolvers expose matching passkey mutation flows through the existing
  authentication payload surface.
- Command handlers create options, verify completion payloads, persist state,
  and issue sessions.
- Factories, validators, transformers, and response factories isolate WebAuthn
  library and browser JSON details.

Infrastructure:

- MongoDB repositories persist credentials and challenges.
- XML mappings define indexes, including credential id/user id lookup indexes
  and challenge TTL cleanup.
- WebAuthn serializer and validator construction remains outside Domain.

## Configuration

Environment-backed passkey configuration:

- `PASSKEY_RP_ID`
- `PASSKEY_RP_NAME`
- `PASSKEY_ALLOWED_ORIGINS`
- `PASSKEY_TIMEOUT_SECONDS`
- `PASSKEY_CHALLENGE_TTL_SECONDS`

## Endpoint Flow

1. Options endpoints validate input.
2. Application factories create WebAuthn creation or request options.
3. A `PasskeyChallenge` stores options JSON and contextual user data.
4. Completion endpoints atomically claim the challenge.
5. Application validators verify attestation or assertion through
   `web-auth/webauthn-lib`.
6. Repositories persist or update the credential record.
7. Sign-up and sign-in completion issue sessions through the existing auth path.

## Layer Boundaries

- Domain objects contain no WebAuthn, Symfony, API Platform, or Doctrine
  imports.
- Validation remains in DTO/YAML/Application validators.
- Processors adapt framework input and delegate to commands.
- Repositories are Domain contracts with MongoDB implementations in
  Infrastructure.
