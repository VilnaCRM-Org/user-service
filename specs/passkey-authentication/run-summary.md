# Passkey Authentication BMAD Run Summary

## Task

Issue #221: add passkey-based authentication support for sign-in and sign-up.

## BMALPH Evidence

- Current transition evidence was collected on `2026-06-01T05:00:52Z` at
  commit `b5a593a7df00e51fa215d6990799945a104dd199` with BMALPH CLI
  `2.11.0`.
- `bmalph -C . doctor --json` initially failed because local generated BMAD
  and Ralph assets were absent: `_bmad/`, `_bmad/COMMANDS.md`,
  `_bmad/lite/create-prd.md`, `.ralph/ralph_loop.sh`, and `.ralph/lib/`.
- `bmalph -C . upgrade --force` restored the local untracked BMAD/Ralph
  assets: `_bmad/`, `.ralph/ralph_loop.sh`, `.ralph/ralph_import.sh`,
  `.ralph/ralph_monitor.sh`, `.ralph/lib/`, `.ralph/PROMPT.md`,
  `.ralph/@AGENT.md`, `.ralph/REVIEW_PROMPT.md`, `.ralph/.ralphrc`, and
  `.ralph/RALPH-REFERENCE.md`.
- `bmalph -C . doctor --json` then passed all installation checks:
  `passed=19`, `failed=0`, `total=19`; the successful checks included
  `_bmad/ directory present`, `ralph_loop.sh present and has content`,
  `.ralph/lib/ directory present`, `_bmad/COMMANDS.md present`,
  `_bmad/lite/create-prd.md present`, and `version marker matches:
v2.11.0`.
- Before transition, `bmalph -C . status --json` reported Phase 3
  `Solutioning`, status `planning`, artifact directory `docs/planning`, found
  `architecture.md`, `epics.md`, `implementation-readiness.md`, and `prd.md`,
  with no missing artifacts and next action `Run: bmalph implement`.
- `bmalph -C . implement` discovered the BMAD planning mirror, parsed stories,
  generated a fix plan for 5 stories, copied specs to `.ralph/specs/`,
  generated `.ralph/SPECS_INDEX.md`, `.ralph/PROJECT_CONTEXT.md`, and
  `.ralph/PROMPT.md`, and completed with `phase 4 (implementing)`.
- `bmalph -C . status --json` after transition reported `phase=4`,
  `phaseName=Implementation`, `status=implementing`, Ralph status
  `not_started`, and next action `Start Ralph loop with: bmalph run`.
- `.ralph/PROJECT_CONTEXT.md` contains the Ralph implementation context for the
  passkey feature: project goals, scope boundaries, and non-functional
  requirements covering REST/GraphQL ceremonies, MongoDB challenge and
  credential persistence, `web-auth/webauthn-lib`, DDD/CQRS/API Platform
  boundaries, WebAuthn privacy, TTL/replay resistance, fallback authentication
  paths, and verification evidence expectations.
- `_bmad/COMMANDS.md` was used to map the planning sequence. The committed
  transition-readable mirror remains under `docs/planning` because the current
  `bmalph implement` release discovers transition artifacts from fixed paths
  such as `docs/planning` and `_bmad-output/planning-artifacts`.

## Planning Artifacts

- `specs/passkey-authentication/research.md`
- `specs/passkey-authentication/product-brief.md`
- `specs/passkey-authentication/prd.md`
- `specs/passkey-authentication/architecture.md`
- `specs/passkey-authentication/epics.md`
- `specs/passkey-authentication/implementation-readiness.md`

Transition mirror for `bmalph implement`:

- `docs/planning/prd.md`
- `docs/planning/architecture.md`
- `docs/planning/epics.md`
- `docs/planning/implementation-readiness.md`

## Implementation Summary

- Added REST passkey ceremonies for sign-up, authenticated registration, and sign-in:
  - `POST /api/passkeys/signup/options`
  - `POST /api/passkeys/signup/complete`
  - `POST /api/passkeys/register/options`
  - `POST /api/passkeys/register/complete`
  - `POST /api/passkeys/signin/options`
  - `POST /api/passkeys/signin/complete`
- Added WebAuthn integration through `web-auth/webauthn-lib`.
- Added passkey DTOs, processors, CQRS commands and command handlers, domain
  entities, repositories, validation, and container wiring.
- Added MongoDB mappings for passkey credentials and passkey challenges.
- Added passkey environment configuration to `.env`, `.env.test`, and `.env.load_test`.
- Updated OpenAPI output and documentation.

## Performance Changes

- `PasskeyCredential` has a unique `credential_id` index for constant-time assertion credential lookup.
- `PasskeyCredential` has a `user_id` index for authenticated user passkey listing and duplicate checks.
- `PasskeyChallenge` has a compound `(purpose, user_id)` index so active challenge cleanup and lookup do not scan all challenges.
- `PasskeyChallenge` has a TTL index on `expires_at` with `expireAfterSeconds=0` so expired challenge records are removed by MongoDB instead of application sweeps.
- Challenge TTL is configurable with `PASSKEY_CHALLENGE_TTL_SECONDS`; ceremony timeout is configurable with `PASSKEY_TIMEOUT_SECONDS`.
- Passkey completion now atomically claims an active challenge before verification, preventing replay races without an application-level read/modify/write window.
- Rate-limit target resolution now covers passkey public and authenticated endpoints without adding user-enumeration paths.

## Subagent Execution Log

The user explicitly requested subagent coverage for this final review pass. Four focused audits were run in parallel:

- Archimedes audited REST/OpenAPI/spec coverage and flagged missing full REST behavior coverage plus validation boundaries.
- Zeno audited unit, integration, memory, and load-test coverage and flagged weak challenge lifecycle coverage.
- Carver audited authentication interop and GraphQL coverage and found passkey sign-in needed 2FA parity plus GraphQL support.
- Planck audited GitHub PR status, CI, and unresolved reviewer threads and identified the five current Cubic review findings.

The BMAD stages were executed in the main session:

- analyst / research: `research.md`
- create-brief: `product-brief.md`
- create-prd: `prd.md`
- create-architecture: `architecture.md`
- create-epics-stories: `epics.md`
- implementation-readiness: `implementation-readiness.md`

## Review Feedback Addressed

- Split passkey credential verification into focused collaborators to keep PHPMD coupling/complexity below project limits.
- Introduced `PasskeyJsonTransformerInterface` so processors and tests depend on an application contract instead of a concrete transformer.
- Split large passkey command handler tests into support objects to reduce test fixture coupling.
- Added a test-only Behat request-context decorator so `X-Test-Client-Ip` drives IP-sensitive session and rate-limit scenarios under the no-port local runner.
- Regenerated `.github/openapi-spec/spec.yaml` with the new passkey endpoints.
- Local AI review found and fixed three issues: passkey ceremonies now require WebAuthn user verification, frontend docs now explain WebAuthn JSON parsing or base64url-to-ArrayBuffer conversion before browser API calls, and passkey challenge consumption is now an atomic repository claim to prevent replay races.
- Current Cubic review findings were addressed: `none` attestation support is registered, authentication result creation no longer publishes side effects from the factory, sign-in options no longer expose credential descriptors, unknown-email passkey completion follows the generic invalid-credential path, sign-in observes existing 2FA policy, and signup completion rolls back persisted user/credential state on downstream failures.
- GraphQL passkey mutations were added for sign-up, sign-in, and authenticated registration using the existing `AuthPayload` mutation surface.
- BMAD FR/NFR remediation updated sign-up options so existing emails are rejected before challenge creation, documented the `409` signup-options response in API Platform/OpenAPI, aligned frontend documentation with that behavior, and added manual/required-check evidence without fabricating browser authenticator results.
- Browser evidence refresh found a live WebAuthn serialization wiring bug:
  the application container autowired Symfony's default serializer into
  `PasskeyJsonTransformer`, causing passkey options to fail on random binary
  challenges with `Malformed UTF-8 characters`. The transformer service now
  explicitly keeps the optional serializer dependencies `null`, so it builds the
  WebAuthn serializer from `PasskeyWebauthnFactory`; an integration test covers
  browser-safe passkey options JSON.
- Current-head FR/NFR risk review found that username-first sign-in intentionally
  returns zero `allowCredentials`, so registration must require discoverable
  credentials. Registration options now set resident-key `required` and expose
  `requireResidentKey=true`; focused unit/integration coverage asserts that
  browser JSON contract. Passkey REST `200` responses now include OpenAPI
  success body schemas/examples for options, token responses, and registration
  completion.
- Strict BMAD review on 2026-06-01 found three remaining evidence gaps:
  missing API-level `/api/graphql` passkey ceremony tests, missing
  average/stress/spike K6 evidence for passkey option scenarios, and stale
  browser/WebAuthn evidence. The remediation added deterministic GraphQL
  integration coverage, reran the browser/WebAuthn ceremony with Chrome
  DevTools virtual authenticators at runtime source base
  `69af2cf13c46f797da7076bff272fa7736e01ce9`, bridged that artifact to the
  reviewed PR head, and reran all passkey K6 profiles after the load-test
  database setup.

## Current-Head Remediation Evidence

Status: source fixes plus browser/authenticator evidence bridged to reviewed PR
head `109e753876270bfe82864b65014702a16d023f64`.

Verifier: Codex.
Date: 2026-05-25 UTC.
Serializer repro SHA before the browser-safe JSON fix:
`58a46bd848e5b9cff70e11e7dc8593c3f1d734f4`.
Browser ceremony tested SHA:
`c0e6fe896143ecbeb26e0e54796c5eb38f3746e6`.
Discoverable-credential/OpenAPI source-tested SHA:
`b6ced150d8eacd4e2d59e099e6c72f043c8c875b`.
Current PR head bridge scope: source-impact bridge through the current PR head
under BMAD review. For the latest manual-browser evidence blocker, the reviewed
head is `109e753876270bfe82864b65014702a16d023f64`; pushed follow-up commits
refresh this bridge through automated tests, graph context, and PR check
evidence.
Manual checklist: `specs/passkey-authentication/manual-test-checklist.md`.
Sanitized browser evidence:
`specs/passkey-authentication/manual-browser-evidence.md`.
Durable sanitized browser transcript:
`specs/passkey-authentication/manual-browser-run-1779672967201-kekp2o.sanitized.md`.
Current-head graph and relationship evidence:
`specs/passkey-authentication/current-head-impact-context.md`.

- Serializer repro workspace identity: `git rev-parse HEAD` returned
  `58a46bd848e5b9cff70e11e7dc8593c3f1d734f4`.
- Local `_bmad` workflow was restored from the identical PR #284 BMALPH bundle
  into ignored path `_bmad/`; `_bmad/core/skills/bmad-review-adversarial-general/workflow.md`
  is present locally.
- GitHub check corroboration before this source fix: `gh pr checks 286 --json`
  reported all 25 non-BMAD checks `SUCCESS` on
  `58a46bd848e5b9cff70e11e7dc8593c3f1d734f4`; BMAD was the only failing status.
- Review corroboration before this source fix: `gh pr view 286 --json` reported
  `reviewDecision=APPROVED`; latest CodeRabbit, cubic, and Kravalg reviews were
  approved on `58a46bd8`; GraphQL review-thread pagination reported 897 total
  review threads and 0 unresolved.
- Manual browser evidence used Google Chrome/HeadlessChrome 148 with Chrome
  DevTools virtual authenticators against
  `https://localhost:65443`, RP ID `localhost`, origin
  `https://localhost:65443`, isolated Docker Compose project
  `user-service-pr286-manual`, MongoDB 7.0, Redis 8, `APP_ENV=test`.
- Browser run id `1779672967201-kekp2o` verified existing-email signup rejection
  returned `409` without a `challenge_id`, new-email signup completed with issued
  access/refresh tokens, challenge reuse returned `401` without tokens,
  authenticated registration completed using a second virtual authenticator,
  passkey sign-in worked with zero `allowCredentials`, TOTP setup/confirm
  returned 8 recovery codes, and passkey sign-in after 2FA returned a pending
  session without access/refresh tokens. Sanitized durable evidence is recorded
  in `specs/passkey-authentication/manual-browser-evidence.md`.
- Expiration run with `PASSKEY_CHALLENGE_TTL_SECONDS=1` verified challenge
  `01KSECHK4BX8HYP4Z2ZE66SXP2` completed after expiry returned `401`,
  detail `Invalid or expired passkey challenge.`, and no access token.
- Focused verification passed:
  `PasskeyAuthEndpointsIntegrationTest::testSignupOptionsReturnsBrowserSafeWebauthnJson`
  plus refresh-token integration coverage: 2 tests / 37 assertions.
- Focused unit verification passed:
  `PasskeyJsonTransformerTest` and `PasskeyOptionsFactoryTest`: 13 tests / 73
  assertions.
- Discoverable-credential/OpenAPI verification at
  `b6ced150d8eacd4e2d59e099e6c72f043c8c875b` passed:
  `PasskeyOptionsFactoryTest`, `PasskeyAuthEndpointsIntegrationTest`, and
  `PasskeyCredentialRequestSchemaTransformerTest`: 8 tests / 99 assertions.
  `PasskeyOptionsFactoryTest` and `PasskeyAuthEndpointsIntegrationTest` assert
  registration options include `residentKey=required` and
  `requireResidentKey=true`.
- OpenAPI verification passed after adding explicit passkey success response
  schemas: `bin/console api:openapi:export --yaml
--output=.github/openapi-spec/spec.yaml`, YAML lint for
  `config/api_platform/resources/EmptyResponse.yaml` and
  `.github/openapi-spec/spec.yaml`, and `./scripts/validate-openapi-spec.sh`
  with no hint-or-higher results.
- Focused quality verification passed for the discoverable-credential/OpenAPI
  changes: `phpmd` on the changed source/test files, PHP-CS-Fixer dry run on the
  changed PHP files, PHP Insights on the changed PHP files, and host
  `git diff --check`.
- Configuration verification passed: `bin/console lint:yaml --parse-tags
config/services.yaml`, `bin/console lint:container`,
  `./scripts/validate-configuration.sh` with only the existing container git
  worktree warning, and host `git diff --check`.
- NFR catalog remediation added measurable passkey option-ceremony load
  standards, passkey K6 scripts, operational monitoring/runbook guidance, and a
  detailed catalog evidence matrix in
  `specs/passkey-authentication/nfr-catalog-evidence.md`.
- Current PR passkey option-ceremony smoke/average/stress/spike evidence
  collected on 2026-06-01 UTC passed the configured thresholds after
  `make setup-load-test-db` prepared schema, indexes, OAuth client, and JWT
  fixtures for isolated Compose project `user-service-pr286-passkey-load`:
  `passkeySignupOptions` checks `100%`, p99 smoke `48.21ms`, average
  `78.89ms`, stress `89.48ms`, spike `165.49ms`; `passkeySigninOptions` checks
  `100%`, p99 smoke `357.2ms`, average `67.77ms`, stress `115.23ms`, spike
  `5.97ms`; `passkeyRegistrationOptions` checks `100%`, p99 smoke `108.6ms`,
  average `164.63ms`, stress `73.29ms`, spike `201.87ms`. Raw local logs are in
  `/home/kravtsov/tmp/pr286-passkey-load-mongo7-after-setup-20260601T022759Z`;
  durable sanitized summary is
  `specs/passkey-authentication/passkey-load-run-20260601T022759Z.sanitized.md`.
- A prior passkey load run without the repository load-test DB setup found a
  real NFR/precondition failure: `passkeySignupOptions` stress p99 was `6.14s`,
  above the `3000ms` threshold. The fix was to run the documented
  `make setup-load-test-db` preparation and rerun all passkey profiles.
- NFR follow-up closed the passkey GraphQL rate-limit gap by mapping passkey
  GraphQL sign-up mutations to the registration limiter and passkey GraphQL
  sign-in mutations to the sign-in IP/email limiters. Focused verification:
  `ApiRateLimitListenerIntegrationTest` passed 9 tests / 109 assertions.
- Current-head graph evidence was refreshed with Graphify
  (`uvx --from graphifyy graphify update . --force --no-cluster`) and recorded
  in `graphify-out/graph.json` with 20,350 nodes and 263,498 edges. Relationship
  notes were added in
  `specs/passkey-authentication/current-head-impact-context.md` for
  post-`c889013e4402ab30060b2bb9dd6cb968fe96783c` rate-limit, recovery-code,
  test, documentation, dependency, and strict BMAD parser-adapter changes.
  Generated `graphify-out/` artifacts are local review evidence and are not
  committed.
- Browser/WebAuthn evidence was rerun on 2026-06-01 UTC with Google Chrome
  headless through Chrome DevTools Protocol and virtual CTAP2 authenticators at
  runtime source base `69af2cf13c46f797da7076bff272fa7736e01ce9`. It verified
  new-email signup, existing-email rejection, replay rejection, passkey sign-in
  before 2FA, authenticated registration, 2FA setup/parity, passkey sign-in
  after 2FA, and expiration rejection. Durable sanitized evidence is in
  `specs/passkey-authentication/manual-browser-run-1780280604724-451d4e.sanitized.md`;
  raw local JSON is `/home/kravtsov/tmp/pr286-manual-webauthn-current.json`.
  The bridge to reviewed PR head
  `109e753876270bfe82864b65014702a16d023f64` accounts for later production
  changes in `IssuedSessionFactory` rollback-failure logging and
  `ApiRateLimitPayloadValueResolver` legacy GraphQL rate-limit fallback parsing.
  Neither change alters browser WebAuthn option generation,
  credential/assertion verification, challenge claim semantics, successful
  session response shape, pending-2FA response shape, or persisted passkey
  credential state.
- BMAD strict remediation on 2026-06-01 removed the forbidden PHPMD
  static-access suppression from `ApiRateLimitGraphQlQueryInspector` by
  extracting Webonyx document parsing to the injected
  `ApiRateLimitGraphQlDocumentResolver`. The same remediation consumes the
  global API limiter before endpoint-specific limiter resolution, so anonymous
  GraphQL parsing work is behind the cheap global throttle as well as the
  existing 64KB request-body cap and Webonyx parser recursion limit. Focused
  verification after clearing stale Symfony test cache passed:
  `ApiRateLimitGraphQlQueryInspectionTest`,
  `ApiRateLimitGraphQlQueryInspectionDefaultValueTest`,
  `ApiRateLimitPayloadValueResolverTest`,
  `ApiRateLimitPayloadValueResolverInvalidGraphQlFallbackTest`,
  `ApiRateLimitRequestResolverGraphQlLimitersTest`,
  `ApiRateLimitRequestResolverGraphQlFragmentLimitersTest`,
  `ApiRateLimitListenerIntegrationTest`, and `ApiRateLimitListenerTest`: 100
  tests / 312 assertions.
- Static and quality verification for the strict remediation passed on
  2026-06-01 UTC: `make phpmd` reported 0 source violations and 0 test
  violations; `make phpinsights` reported source Code 100, Complexity 97.2,
  Architecture 100, Style 100 and tests Code 100, Complexity 97.8,
  Architecture 100, Style 100; `make psalm` reported no errors; `git diff
--check` and `git diff --cached --check` passed.
- Focused mutation verification for the GraphQL rate-limit parser path passed:
  100 PHPUnit coverage tests / 312 assertions, then Infection generated 148
  mutants for `ApiRateLimitListener` plus the GraphQL parser/resolver path, with
  121 killed and 27 timed out. MSI, mutation code coverage, and covered code MSI
  were all 100%.

## Mandatory Skill Gate

- `api-platform-crud`: Applicable. API Platform YAML operations were added for the passkey REST endpoints.
- `bmad-autonomous-planning`: Applicable. The BMALPH planning bundle for this feature is listed in Planning Artifacts; no new autonomous planning artifacts were required for this remediation pass.
- `cache-management`: Applicable. Verified no new cache invalidation contract is required; existing user/session cache behavior is unchanged.
- `ci-workflow`: Applicable. CI-equivalent commands were run through Docker because the local `make ci` port is occupied by another workspace.
- `clean-architecture-llm`: Not applicable. Passkey authentication does not introduce LLM providers, prompts, model clients, tool orchestration, or AI-backed runtime behavior.
- `code-organization`: Applicable. Passkey code follows existing User bounded-context Application/Domain/Infrastructure layout.
- `code-review`: Applicable. Review feedback was addressed before push.
- `complexity-management`: Applicable. Verifier and test support classes were split to keep complexity gates green.
- `database-migrations`: Applicable. MongoDB ODM mappings were added with passkey indexes and TTL cleanup.
- `deptrac-fixer`: Applicable. `deptrac.yaml` was updated for passkey and WebAuthn dependencies; deptrac passes with no violations and no uncovered dependencies.
- `documentation-creation`: Applicable. Added `docs/passkey-authentication.md`.
- `documentation-sync`: Applicable. Updated `docs/main.md` and `docs/advanced-configuration.md`.
- `implementing-ddd-architecture`: Applicable. Domain entities remain framework-free; validation stays in YAML/Application layer.
- `load-testing`: Applicable. Added K6 coverage for passkey signup, signin, and authenticated registration options; browser WebAuthn completion remains manual/browser evidence because k6 cannot operate an authenticator.
- `observability-instrumentation`: Applicable. Passkey endpoints are covered by existing `EndpointInvocations` EMF metrics; passkey monitoring, alerting, capacity, and TTL-index runbook requirements are documented.
- `openapi-development`: Applicable. OpenAPI spec was regenerated and validated.
- `quality-standards`: Applicable. PHP Insights, PHPMD, Psalm, taint analysis, and whitespace checks were run.
- `query-performance-analysis`: Applicable. Passkey lookup/cleanup indexes were added as listed in Performance Changes.
- `structurizr-architecture-sync`: Not applicable for this PR. No deployment/container relationship changed.
- `testing-workflow`: Applicable. Unit, integration, Behat, and targeted passkey/rate-limit tests were run.

## Manual Test Evidence Checklist

Status: completed with Chrome DevTools virtual authenticators on
2026-05-25 UTC. Sanitized details are captured in
`specs/passkey-authentication/manual-test-checklist.md`.
Durable sanitized browser evidence is recorded in
`specs/passkey-authentication/manual-browser-evidence.md`.

Tester: Codex.
Execution date: 2026-05-25 UTC.
Environment: local PR #286 worktree (`user-service-pr286`), tested at
`c0e6fe896143ecbeb26e0e54796c5eb38f3746e6` with repro SHA
`58a46bd848e5b9cff70e11e7dc8593c3f1d734f4`; current
discoverable-credential/OpenAPI source-tested SHA
`b6ced150d8eacd4e2d59e099e6c72f043c8c875b`, isolated Docker Compose project
`user-service-pr286-manual`, `https://localhost:65443`, PHP 8.4.5, MongoDB 7.0,
Redis 8.
Browser/authenticator: Google Chrome/HeadlessChrome 148 with Chrome DevTools
virtual CTAP2 authenticators, resident keys enabled, user verification enabled,
automatic presence simulation enabled.

Latest browser rerun: 2026-06-01T02:23:28.150Z against
`http://localhost:19081` at runtime source base commit
`69af2cf13c46f797da7076bff272fa7736e01ce9`, using Google Chrome headless
through Chrome DevTools Protocol with virtual CTAP2 authenticators. Reviewed PR
head bridge: `109e753876270bfe82864b65014702a16d023f64`. Sanitized transcript:
`specs/passkey-authentication/manual-browser-run-1780280604724-451d4e.sanitized.md`.

### Scenario 1: Passkey Sign-Up Rejects Existing Email

Steps:

1. Sign in or create a baseline user with a known email address.
2. Submit `POST /api/passkeys/signup/options` with the existing email, valid
   initials, and an optional display name.
3. Confirm the API returns the documented conflict response and no WebAuthn
   challenge is created.

Observed result: browser run id `1779672967201-kekp2o` created a baseline user,
then `POST /api/passkeys/signup/options` for that email returned `409` and did
not return a `challenge_id`.
Artifacts: `specs/passkey-authentication/manual-browser-evidence.md`,
`specs/passkey-authentication/manual-browser-run-1779672967201-kekp2o.sanitized.md`;
checklist scenario 1.

### Scenario 2: Passkey Sign-Up Creates Options for New Email

Steps:

1. Submit `POST /api/passkeys/signup/options` with an email that is not
   registered, valid initials, and an optional display name.
2. Start the browser WebAuthn creation ceremony from the returned `public_key`
   JSON.
3. Submit `POST /api/passkeys/signup/complete` with the returned `challengeId`
   and browser credential JSON.
4. Confirm a user session is issued and the credential can be used for a later
   passkey sign-in.

Observed result: browser run id `1779672967201-kekp2o` created a credential via
`navigator.credentials.create()`, submitted `credential.toJSON()` to
`/api/passkeys/signup/complete`, and received access and refresh tokens with
`2fa_enabled=false`.
Artifacts: `specs/passkey-authentication/manual-browser-evidence.md`,
`specs/passkey-authentication/manual-browser-run-1779672967201-kekp2o.sanitized.md`;
checklist scenario 2.

### Scenario 3: Authenticated Passkey Enrollment

Steps:

1. Sign in with an existing account.
2. Submit `POST /api/passkeys/register/options`.
3. Start the browser WebAuthn creation ceremony from the returned `public_key`
   JSON.
4. Submit `POST /api/passkeys/register/complete` with the returned
   `challengeId` and browser credential JSON.
5. Confirm the account can sign in with the newly enrolled passkey.

Observed result: browser run id `1779672967201-kekp2o` used the issued bearer
token, requested registration options, created a second credential on a second
virtual authenticator, and `/api/passkeys/register/complete` returned a
credential id.
Artifacts: `specs/passkey-authentication/manual-browser-evidence.md`,
`specs/passkey-authentication/manual-browser-run-1779672967201-kekp2o.sanitized.md`;
checklist scenario 3.

### Scenario 4: Passkey Sign-In With 2FA Parity

Steps:

1. Use an account that has both a passkey and TOTP enabled.
2. Submit `POST /api/passkeys/signin/options` for that account.
3. Complete the browser WebAuthn assertion ceremony.
4. Submit `POST /api/passkeys/signin/complete`.
5. Confirm the response follows the existing 2FA pending-session behavior
   instead of issuing final tokens immediately.

Observed result: browser run id `1779672967201-kekp2o` enabled TOTP through the
existing `/api/2fa/setup` and `/api/2fa/confirm` flow, then completed passkey
sign-in. The response returned `2fa_enabled=true` and a `pending_session_id`, and
did not return access or refresh tokens.
Artifacts: `specs/passkey-authentication/manual-browser-evidence.md`,
`specs/passkey-authentication/manual-browser-run-1779672967201-kekp2o.sanitized.md`;
checklist scenario 4.

### Scenario 5: Challenge Reuse And Expiration

Steps:

1. Complete one passkey challenge successfully.
2. Resubmit the same `challengeId` and credential JSON.
3. Start a separate signup challenge with a one-second TTL, wait past expiry,
   and submit the browser credential JSON.

Observed result: browser run id `1779672967201-kekp2o` retried the completed
signup challenge and received `401` without access or refresh tokens. Expiration
run `manual-expired-1779673120988@example.test` used challenge
`01KSECHK4BX8HYP4Z2ZE66SXP2`; completion after expiry returned `401`, detail
`Invalid or expired passkey challenge.`, and no access token.
Artifacts: `specs/passkey-authentication/manual-browser-evidence.md`,
`specs/passkey-authentication/manual-browser-run-1779672967201-kekp2o.sanitized.md`;
checklist scenario 5.

## Verification Evidence

Status: current focused verification plus historical automated evidence for
earlier remediation commits. Full post-push CI is provided by GitHub Actions for
each pushed PR head.

Strict BMAD FR/NFR remediation on 2026-06-01 added a split
`tests/Integration/Auth/PasskeyGraphQL*` integration suite,
`tests/Shared/Auth/Support/ControllableCommandBus.php`, test-container wiring,
source-impact bridged browser evidence, current-head K6 evidence, and matching
documentation updates. The follow-up quality fix removed the temporary
PHPInsights carve-outs by replacing the 800-line GraphQL passkey test with
focused option, completion-response, and completion-failure test classes plus a
shared base test case. The new integration suite executes
`/api/graphql` passkey mutations for:

- `passkeySignUpOptionsUser` success, invalid-email validation, and
  existing-email conflict.
- `passkeySignInOptionsUser` known-user and unknown-user privacy-preserving
  response shape with empty `allowCredentials`.
- `passkeyRegistrationOptionsUser` unauthenticated rejection and authenticated
  browser-safe public-key response.
- `passkeySignUpCompleteUser`, `passkeySignInCompleteUser`, and
  `passkeyRegistrationCompleteUser` deterministic GraphQL completion response
  serialization through a test-only command bus decorator.
- Invalid signup credential JSON, sign-in challenge replay after an invalid
  credential, wrong-user registration challenge completion, and duplicate
  registration credential conflict.
- Command payload mapping for completion mutations, including challenge id,
  nested browser credential JSON, label, remember-me, authenticated user id, IP
  address, and user-agent where applicable.
- Rejected GraphQL mutations not leaking token, pending-session, credential,
  challenge, or public-key payload values in partial response data.

Focused GraphQL validation passed after clearing stale Symfony test cache:
`docker compose exec -T php ./vendor/bin/phpunit tests/Integration/Auth/PasskeyGraphQLAuthOptionsIntegrationTest.php tests/Integration/Auth/PasskeyGraphQLCompletionFailureTest.php tests/Integration/Auth/PasskeyGraphQLCompletionResponseTest.php`
passed 13 tests / 176 assertions.
Suppression-free command-bus validation plus the split GraphQL suite also
passed:
`docker compose exec -T php ./vendor/bin/phpunit tests/Unit/Shared/Auth/Support/ControllableCommandBusTest.php tests/Integration/Auth/PasskeyGraphQLAuthOptionsIntegrationTest.php tests/Integration/Auth/PasskeyGraphQLCompletionFailureTest.php tests/Integration/Auth/PasskeyGraphQLCompletionResponseTest.php`
passed 16 tests / 183 assertions.
Targeted PHP Insights validation for the new GraphQL test/support files passed
Code 100, Complexity 100, Architecture 100, and Style 100 without excluding the
new passkey GraphQL tests from complexity or function-length rules.
The broader CI-equivalent `make phpinsights` target also passed after the split:
PHPMD reported no source or test violations, PHPInsights reported Code 100,
Architecture 100, Style 100, and green complexity thresholds. Targeted Psalm
validation for the same files reported no errors, and full `make psalm` reported
no errors after replacing the DI-constructor suppression with direct unit
coverage for the test-only `ControllableCommandBus`.

Passkey load validation passed for all option scenarios with smoke, average,
stress, and spike enabled in the same K6 invocation:

| Scenario                     | Checks | Smoke p99 | Average p99 | Stress p99 | Spike p99 |
| ---------------------------- | ------ | --------: | ----------: | ---------: | --------: |
| `passkeySignupOptions`       | 100%   |   48.21ms |     78.89ms |    89.48ms |  165.49ms |
| `passkeySigninOptions`       | 100%   |   357.2ms |     67.77ms |   115.23ms |    5.97ms |
| `passkeyRegistrationOptions` | 100%   |   108.6ms |    164.63ms |    73.29ms |  201.87ms |

The load run used isolated Compose project `user-service-pr286-passkey-load`,
MongoDB 7 override, `make setup-load-test-db`, then
`tests/Load/execute-load-test.sh <scenario> true true true true`.

- Current focused integration verification:
  `./vendor/bin/phpunit tests/Integration/Auth/PasskeyAuthEndpointsIntegrationTest.php tests/Integration/Auth/AuthEndpointsIntegrationTest.php --filter "testSignupOptionsReturnsBrowserSafeWebauthnJson|testRefreshTokenEndpointRotatesTokenAndIssuesNewTokens"`
  passed: 2 tests, 37 assertions.
- Current focused unit verification:
  `./vendor/bin/phpunit tests/Unit/User/Application/Transformer/PasskeyJsonTransformerTest.php tests/Unit/User/Application/Factory/PasskeyOptionsFactoryTest.php`
  passed: 13 tests, 73 assertions.
- Current configuration verification: `bin/console lint:yaml --parse-tags
config/services.yaml` passed; `bin/console lint:container` passed;
  `./scripts/validate-configuration.sh` passed with the existing container
  worktree git warning; host `git diff --check` passed.

Remediation note for 2026-05-24 UTC: targeted validation was rerun in an isolated
Docker Compose project because local PHP is not installed and another checkout
owns the default development ports. Local `mongo:8.0` exited with code 139 after
initial health checks, so full local integration/Behat CI was not rerun in this
workspace; GitHub Actions is the source of full-suite verification after push.

- Targeted BMAD remediation tests:
  `PasskeyRegistrationCommandHandlerTest`,
  `PasskeySignInTwoFactorCommandHandlerTest`,
  `PasskeySignInCompleteProcessorTwoFactorTest`,
  `PasskeyAuthMutationResolverTest`, and
  `PasskeySignUpAuthenticationRollbackTest` passed: 24 tests, 244 assertions.
- All passkey-related unit tests under `tests/Unit` passed: 136 tests, 769
  assertions.
- `php bin/console api:openapi:export --yaml --output=.github/openapi-spec/spec.yaml`
  regenerated the OpenAPI spec; the resulting diff is exactly the documented
  `409` response for `/api/passkeys/signup/options`.
- `./scripts/validate-configuration.sh`: passed.
- `php bin/console lint:yaml --parse-tags config/api_platform/resources/EmptyResponse.yaml .github/openapi-spec/spec.yaml`:
  passed.
- `php bin/console lint:container`: passed.
- `./scripts/validate-openapi-spec.sh`: passed with no Spectral results at hint
  severity or higher.
- `git diff --check`: passed.

- Full unit suite: 2372 tests, 6793 assertions.
- Passkey unit filter: 126 tests, 656 assertions.
- Targeted passkey/GraphQL/session rollback tests: 43 tests, 360 assertions.
- Integration suite: 126 tests, 745 assertions.
- GraphQL auth config integration suite: 4 tests, 33 assertions.
- Behat suite: 644 scenarios, 3622 steps.
- `phpmd src`: passed.
- `phpmd tests`: passed.
- `phpinsights` source: Code 100, Complexity 97.6, Architecture 100, Style 100.
- `phpinsights analyse tests`: Code 100, Complexity 97.9, Architecture 100, Style 100.
- `psalm --no-cache --show-info=false --no-progress`: passed.
- `psalm --taint-analysis --show-info=false --no-progress`: passed.
- `deptrac analyse --config-file=deptrac.yaml --report-uncovered --fail-on-uncovered`: passed.
- `bin/console lint:yaml --parse-tags` for changed YAML files: passed.
- XML mapping parse check for passkey ODM mappings: passed.
- `bin/console lint:container`: passed.
- `composer validate`: passed with the existing Composer version-field warning.
- `symfony security:check`: passed.
- `symfony check:requirements`: passed.
- OpenAPI diff against main: backward compatible; six passkey endpoints added.
- Spectral OpenAPI validation: no hint-or-higher results.
- `git diff --check`: passed.
- Post-BMAD GraphQL limiter hardening evidence collected on 2026-05-31 UTC:
  selected-operation auth limiter detection and sign-in email extraction now use
  AST inspection, so aliases, unrelated JSON `email` fields, unselected
  operations, operation-looking tokens inside GraphQL strings, root fragments,
  inline fragments, missing/recursive fragments, and multi-root/aliased auth
  mutations cannot bypass or poison limiter selection. Registration and sign-in
  limiter targets are emitted per selected auth field; repeated same-email
  sign-in aliases preserve duplicate `signin_ip` and `signin_email` target
  consumption. Follow-up mutation-survivor hardening removed a redundant invalid
  GraphQL input-object fallback, made selected operation matching explicit, kept
  repeated-fragment traversal state observable, and added negative/edge tests for
  fragment expansion, selected-operation scoping, duplicate auth fields,
  same-email aliases, invalid GraphQL JSON fallback, blank emails, non-string
  variables, missing variable decoys, and omitted GraphQL variables that rely on
  operation-level default values. Focused PHPUnit passed 100 tests / 309
  assertions. Focused coverage passed 79 tests / 119 assertions and reported all
  covered for
  `ApiRateLimitGraphQlAuthTargetResolver`,
  `ApiRateLimitGraphQlFieldValueResolver`,
  `ApiRateLimitGraphQlQueryInspection`, `ApiRateLimitGraphQlQueryInspector`,
  `ApiRateLimitGraphQlRootFields`, `ApiRateLimitNestedPayloadStringResolver`,
  `ApiRateLimitGraphQlVariableValueResolver`, and
  `ApiRateLimitPayloadValueResolver`. Full local `make infection` passed before
  the default-variable follow-up: 5281 mutations generated, 5278 killed, 3 timed
  out, MSI 100%, covered MSI 100%. The affected rate-limit Infection slice also
  passed: 259 mutations generated, 256 killed, 3 timed out, MSI 100%, covered
  MSI 100%. The default-variable follow-up slice passed with 93 mutations
  generated and 93 killed, MSI 100%, covered MSI 100%.
  `make phpinsights` passed with source scores Code 100, Complexity 97.3,
  Architecture 100, Style 100 and test scores Code 100, Complexity 97.8,
  Architecture 100, Style 100. `make psalm` reported no errors, and
  `git diff --check` passed after the hardening.
- PHP syntax lint for modified and added PHP files: passed.
- PHP-CS-Fixer for modified and added PHP files: passed.
- Local AI review loop was run in a clean temporary worktree at commit `32334012`; it reported the three issues listed above, the fix pass changed only passkey source/tests/docs, and targeted re-verification passed.
- Targeted passkey/repository/rate-limit unit tests after AI review fixes: 195 tests, 484 assertions.
- Passkey application Infection slice after CI fix: 183 mutations generated, 183 killed; MSI 100%, covered MSI 100%.
- Changed-source Infection run after CI fix: 446 mutations generated, 446 killed; MSI 100%, covered MSI 100%.

## Local CI Note

The literal `make ci` target could not be run in this workspace because another local checkout owns hardcoded development ports. Equivalent Docker commands were run with isolated dependency ports.

For Behat, the local `mongo:8.0` container repeatedly exited with code 139 after successful health checks in this workspace. The Behat verification therefore used an isolated, no-host-port Compose stack with a transient `mongo:7.0` override and a recreated PHP service running `APP_ENV=test` and `APP_DEBUG=0`. The test database was rebuilt immediately before the successful full Behat run.

## GitHub Required Check Configuration Evidence

Status: historical required-check configuration evidence plus current-head
pre-fix PR check/review corroboration in `Current-Head Remediation Evidence`.

Verifier: Codex.
Date: 2026-05-24 UTC.
Observed required checks: GitHub branch protection for `main` has strict status
checks enabled but currently lists no required status check contexts/checks.
Conversation resolution is required, code owner review is required,
last-push approval is required, and two approving reviews are required.
Artifacts:

- `gh api repos/VilnaCRM-Org/user-service/branches/main/protection` reported
  `required_status_checks.strict=true`, empty `contexts` and `checks`,
  `required_conversation_resolution.enabled=true`,
  `required_pull_request_reviews.required_approving_review_count=2`,
  `require_code_owner_reviews=true`, and `require_last_push_approval=true`.
- `gh pr view 286 --json ...` reported PR #286 open, non-draft, review decision
  `APPROVED`, and merge state `UNSTABLE` only because the BMAD status context was
  failing on the pre-remediation commit.
- `gh pr checks 286` reported all non-BMAD checks passing on
  `36bac4ef10d278d1e78762e6d6044dde5d74ed7e`: Behat, Deptrac, GraphQL
  Inspector, Infection, K6, Memory leak tests, Openapi-diff, PHP Insights,
  PHPUnit, Psalm, Schemathesis, Spectral Lint, CodeRabbit, cubic, codecov, qlty,
  Snyk, symfony-checks, eslint, lint, openapi-diff, and test-and-report.
- `gh pr checks 286 --json` later reported all 25 non-BMAD checks passing on
  `58a46bd848e5b9cff70e11e7dc8593c3f1d734f4`; `BMAD FR/NFR Review Gate` was
  the only failing status before the serializer wiring fix.
- Thread-aware GraphQL export for PR #286 reported 288 conversation comments,
  450 reviews, 894 review threads, 0 unresolved threads, and 0 active unresolved
  threads.
- Thread-aware GraphQL pagination later reported 897 total review threads and 0
  unresolved threads on `58a46bd8`.

## Open Questions

- Whether a dedicated passkey JSON scalar should replace API Platform's `Iterable` scalar later.
- Whether enterprise attestation policy is required for managed organization devices.
