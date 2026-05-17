# Passkey Authentication BMAD Run Summary

## Task

Issue #221: add passkey-based authentication support for sign-in and sign-up.

## BMALPH Evidence

- `bmalph status` showed Phase 1 / Analyst planning.
- `bmalph doctor` initially found missing bundled BMAD assets.
- `bmalph upgrade --force` restored `_bmad`, `.ralph`, and BMAD command assets.
- `bmalph doctor` then passed all checks.
- `_bmad/COMMANDS.md` was used to map the planning sequence.

## Planning Artifacts

- `specs/passkey-authentication/research.md`
- `specs/passkey-authentication/product-brief.md`
- `specs/passkey-authentication/prd.md`
- `specs/passkey-authentication/architecture.md`
- `specs/passkey-authentication/epics.md`
- `specs/passkey-authentication/implementation-readiness.md`

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

## Mandatory Skill Gate

- `api-platform-crud`: Applicable. API Platform YAML operations were added for the passkey REST endpoints.
- `cache-management`: Applicable. Verified no new cache invalidation contract is required; existing user/session cache behavior is unchanged.
- `ci-workflow`: Applicable. CI-equivalent commands were run through Docker because the local `make ci` port is occupied by another workspace.
- `code-organization`: Applicable. Passkey code follows existing User bounded-context Application/Domain/Infrastructure layout.
- `code-review`: Applicable. Review feedback was addressed before push.
- `complexity-management`: Applicable. Verifier and test support classes were split to keep complexity gates green.
- `database-migrations`: Applicable. MongoDB ODM mappings were added with passkey indexes and TTL cleanup.
- `deptrac-fixer`: Applicable. `deptrac.yaml` was updated for passkey and WebAuthn dependencies; deptrac passes with no violations and no uncovered dependencies.
- `documentation-creation`: Applicable. Added `docs/passkey-authentication.md`.
- `documentation-sync`: Applicable. Updated `docs/main.md` and `docs/advanced-configuration.md`.
- `implementing-ddd-architecture`: Applicable. Domain entities remain framework-free; validation stays in YAML/Application layer.
- `load-testing`: Not applicable for this PR. No load test scenario was added because the feature is a new authentication ceremony and current CI load suites are broader smoke checks.
- `observability-instrumentation`: Not applicable for this PR. The ceremonies reuse existing authentication/session error handling and do not introduce a new metrics contract.
- `openapi-development`: Applicable. OpenAPI spec was regenerated and validated.
- `quality-standards`: Applicable. PHP Insights, PHPMD, Psalm, taint analysis, and whitespace checks were run.
- `query-performance-analysis`: Applicable. Passkey lookup/cleanup indexes were added as listed in Performance Changes.
- `structurizr-architecture-sync`: Not applicable for this PR. No deployment/container relationship changed.
- `testing-workflow`: Applicable. Unit, integration, Behat, and targeted passkey/rate-limit tests were run.

## Verification Evidence

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
- PHP syntax lint for modified and added PHP files: passed.
- PHP-CS-Fixer for modified and added PHP files: passed.
- Local AI review loop was run in a clean temporary worktree at commit `32334012`; it reported the three issues listed above, the fix pass changed only passkey source/tests/docs, and targeted re-verification passed.
- Targeted passkey/repository/rate-limit unit tests after AI review fixes: 195 tests, 484 assertions.
- Passkey application Infection slice after CI fix: 183 mutations generated, 183 killed; MSI 100%, covered MSI 100%.
- Changed-source Infection run after CI fix: 446 mutations generated, 446 killed; MSI 100%, covered MSI 100%.

## Local CI Note

The literal `make ci` target could not be run in this workspace because another local checkout owns hardcoded development ports. Equivalent Docker commands were run with isolated dependency ports.

For Behat, the local `mongo:8.0` container repeatedly exited with code 139 after successful health checks in this workspace. The Behat verification therefore used an isolated, no-host-port Compose stack with a transient `mongo:7.0` override and a recreated PHP service running `APP_ENV=test` and `APP_DEBUG=0`. The test database was rebuilt immediately before the successful full Behat run.

## Open Questions

- Whether a dedicated passkey JSON scalar should replace API Platform's `Iterable` scalar later.
- Whether enterprise attestation policy is required for managed organization devices.
