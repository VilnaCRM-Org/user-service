# Passkey Authentication Epics

## Epic 1: Planning And Configuration

### Story 1.1: Create BMAD Planning Artifacts

- Add BMAD planning artifacts.
- Add WebAuthn dependency.
- Add environment parameters and service wiring.

Acceptance Criteria:

- BMAD doctor passes.
- BMAD implementation transition succeeds.
- Planning artifacts are committed under `specs/passkey-authentication`.

## Epic 2: Persistence Model

### Story 2.1: Persist Passkey Credentials And Challenges

- Add `PasskeyCredential` domain entity and repository.
- Add `PasskeyChallenge` domain entity and repository.
- Add MongoDB XML mappings and indexes.

Acceptance Criteria:

- Credentials are queryable by encoded credential id and user id.
- Challenges expire through a MongoDB TTL index.
- Domain entities have no Symfony, Doctrine, or WebAuthn imports.

## Epic 3: Registration Ceremonies

### Story 3.1: Support Passkey Sign-Up And Enrollment

- Implement sign-up options and completion.
- Implement authenticated registration options and completion.
- Persist verified credential records.

Acceptance Criteria:

- Sign-up completion creates a user and credential after attestation verification.
- Authenticated enrollment ties the credential to the current user.
- Duplicate credential ids cannot be stored.

## Epic 4: Authentication Ceremony

### Story 4.1: Support Passkey Sign-In

- Implement sign-in options and completion.
- Verify assertions and update credential counters.
- Issue tokens and auth cookies using existing session factories.

Acceptance Criteria:

- Sign-in completion verifies the assertion using stored request options.
- Credential record state is updated after successful verification.
- Response includes access and refresh tokens.

## Epic 5: Documentation And Tests

### Story 5.1: Verify And Document Passkeys

- Add unit coverage for services, processors, entities, and repositories.
- Add documentation for frontend integration and migration.
- Run CI and review gates before opening the PR.

Acceptance Criteria:

- Focused tests pass locally.
- Documentation explains frontend ceremony payloads and fallback paths.
- GitHub CI is green after PR creation.
