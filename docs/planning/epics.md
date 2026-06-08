# Passkey Authentication Epics

Canonical source: `specs/passkey-authentication/epics.md`.

## Epic 1: Planning And Configuration

### Story 1.1: Create BMAD Planning Artifacts

Add the passkey BMAD planning bundle, WebAuthn dependency, environment
parameters, container wiring, and a transition-readable planning mirror for the
current `bmalph implement` discovery paths.

Acceptance Criteria:

- Given local BMALPH assets are installed, When `bmalph doctor` runs, Then BMAD
  assets and repository configuration are reported as healthy.
- Given the passkey PRD, architecture, epics, and readiness report exist under
  `specs/passkey-authentication` and are mirrored under `docs/planning`, When
  `bmalph implement` runs, Then the transition discovers all required BMAD
  artifacts and writes Ralph implementation context.
- Given the implementation transition completes, When `bmalph status` runs,
  Then the project reports Phase 4 Implementation with status `implementing`.
- Given the passkey planning bundle is reviewed, When repository artifacts are
  inspected, Then the canonical planning artifacts remain committed under
  `specs/passkey-authentication`.

## Epic 2: Persistence Model

### Story 2.1: Persist Passkey Credentials And Challenges

Add `PasskeyCredential` and `PasskeyChallenge` domain entities, repository
interfaces, MongoDB repository implementations, XML mappings, credential lookup
indexes, and challenge TTL cleanup.

Acceptance Criteria:

- Given passkey credentials are persisted, When the application queries by
  encoded credential id or user id, Then the repository can resolve the matching
  records through indexed lookups.
- Given passkey challenges expire, When MongoDB evaluates the challenge
  collection, Then expired challenge records are removed through a TTL index.
- Given Deptrac analyzes the domain layer, When passkey domain entities are
  scanned, Then they have no Symfony, Doctrine, or WebAuthn imports.

## Epic 3: Registration Ceremonies

### Story 3.1: Support Passkey Sign-Up And Enrollment

Implement public sign-up options/completion and authenticated registration
options/completion, including attestation verification, discoverable credential
requirements, rollback on downstream sign-up failures, and duplicate
credential-id rejection.

Acceptance Criteria:

- Given a new user completes passkey sign-up, When attestation verification
  succeeds, Then the application creates the user and stores the verified
  credential.
- Given an authenticated user completes passkey enrollment, When attestation
  verification succeeds, Then the stored credential is tied to the current user.
- Given a credential id already exists, When another registration attempts to
  store it, Then the application rejects the duplicate credential id.

## Epic 4: Authentication Ceremony

### Story 4.1: Support Passkey Sign-In

Implement sign-in options/completion, assertion verification, credential counter
updates, privacy-safe unknown-user behavior, 2FA parity, and existing session
token issuance.

Acceptance Criteria:

- Given a passkey sign-in completion request, When assertion verification runs,
  Then it uses the stored request options from the claimed challenge.
- Given assertion verification succeeds, When the credential is persisted, Then
  the credential counter and record state are updated.
- Given the user does not require pending two-factor confirmation, When passkey
  sign-in completes, Then the response includes access and refresh tokens.

## Epic 5: Documentation And Tests

### Story 5.1: Verify And Document Passkeys

Add automated coverage, update OpenAPI/GraphQL contracts and passkey
documentation, maintain the NonFunctionals.com evidence matrix, and record
manual browser authenticator evidence without fabricating results.

Acceptance Criteria:

- Given passkey source changes are present, When focused unit, integration,
  Behat, memory, and load checks run through repository make or container
  commands, Then they pass with the expected assertions.
- Given frontend or API developers integrate passkeys, When they read the
  documentation, Then it explains browser WebAuthn JSON payloads and fallback
  authentication paths.
- Given the PR is opened, When GitHub CI completes, Then applicable checks are
  green.
