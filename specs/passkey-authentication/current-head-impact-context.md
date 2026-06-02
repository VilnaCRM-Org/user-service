# Passkey Authentication Current-Head Impact Context

Generated: 2026-06-01 UTC

Base ref: `refs/remotes/origin/main`

Current PR head bridge scope for this evidence refresh: the browser transcript
is source-impact bridged to the active PR head, and the strict BMAD report
records the exact pushed SHA reviewed after each remediation commit.

Previous strict-gate graph artifact reported as stale before this remediation
and retained here only as historical context:
`/home/kravtsov/tmp/bmad-pr286-strict-20260531_182458/`
`review-loop-final-noapproval-e650f0d4-20260601_124135/`
`bmad-required-impact-and-github-context.md`

This file records current-head relationship evidence for the post-graph changes.
The local Graphify artifact was regenerated with:

```bash
/home/kravtsov/.cache/uv/archive-v0/5ZCKDI9OgasHyZPzNKgqk/bin/graphify \
  update . --force --no-cluster
```

Generated `graphify-out/` artifacts are local review evidence and are not
committed. The latest clean local refresh completed on 2026-06-02 04:46:30 +0300
after the passkey sign-in rate-limit hardening, Infection survivor fix,
resolver extraction, quality/load-test fixes, BMAD readiness-order review, and
post-claim passkey cleanup hardening, and produced 20,759 nodes and 434,402
edges. This committed evidence file can move in a later docs-only commit; the
final BMAD sidecar report records the exact reviewed PR SHA.
The refreshed graph includes:

- `scripts/normalize-graphql-passkey-descriptions.php`
- `src/User/Application/EventListener/PasskeyGraphQlRequestResolver.php`
- `src/User/Application/EventListener/PasskeyProductionReadinessListener.php`
- `src/Shared/Application/Resolver/RateLimit/ApiRateLimitGraphQlAuthTargetResolver.php`
- `src/Shared/Application/Resolver/RateLimit/ApiRateLimitGraphQlPayloadResolver.php`
- `src/Shared/Application/Resolver/RateLimit/ApiRateLimitGraphQlFieldValueResolver.php`
- `src/Shared/Application/Resolver/RateLimit/ApiRateLimitGraphQlObjectFieldResolver.php`
- `src/Shared/Application/Resolver/RateLimit/ApiRateLimitGraphQlVariableValueResolver.php`
- `tests/Unit/User/Application/EventListener/PasskeyProductionReadinessListenerTestCase.php`
- `tests/Unit/User/Application/EventListener/PasskeyReadinessListenerTest.php`
- `tests/Unit/User/Application/EventListener/PasskeyReadinessGraphQlBlockingTest.php`
- `tests/Unit/User/Application/EventListener/PasskeyReadinessGraphQlResolutionTest.php`
- `tests/Unit/Shared/Application/Resolver/RateLimit/ApiRateLimitRequestResolverGraphQlLimitersTest.php`
- `tests/Integration/Auth/ApiRateLimitListenerIntegrationTest.php`
- `src/User/Application/CommandHandler/CompletePasskeyRegistrationCommandHandler.php`
- `src/User/Application/CommandHandler/CompletePasskeySignInCommandHandler.php`
- `src/User/Application/Resolver/PasskeyChallengeResolver.php`
- `src/User/Application/Service/PasskeyAuthenticationIssuer.php`
- `src/User/Infrastructure/Repository/MongoDBPasskeyChallengeRepository.php`
- `tests/Unit/User/Application/CommandHandler/PasskeyPostClaimCleanupCommandHandlerTest.php`
- `tests/Unit/User/Application/CommandHandler/PasskeyRegistrationCommandHandlerTest.php`
- `tests/Integration/Auth/ApiRateLimitListenerPasskeyIntegrationTest.php`
- `scripts/ai-review-loop.sh`
- `tests/CLI/bats/make_negative_tests.bats`

The latest strict BMAD report includes graph counts and reviewed-head context in
its log directory for the exact head it reviews. The graph impact context is
recorded in that report rather than a separate committed graph-impact file.
The final graph-backed review links the post-claim cleanup path through
`CompletePasskeyRegistrationCommandHandler`, `CompletePasskeySignInCommandHandler`,
and `PasskeyChallengeResolver::deleteBestEffort`; the direct passkey sign-in
event flag through `PasskeyAuthenticationIssuer` and the command-handler
publisher assertions; the strict challenge expiry boundary through
`MongoDBPasskeyChallengeRepository`; the rate-limit/readiness ordering through
`config/services.yaml` and `ApiRateLimitListenerPasskeyIntegrationTest`; and the
no-approval BMAD runner/Bats fixes through `scripts/ai-review-loop.sh` and
`tests/CLI/bats/make_negative_tests.bats`.
The latest resolver extraction moves GraphQL payload extraction and
normalization into `ApiRateLimitGraphQlPayloadResolver`, and moves direct/nested
GraphQL AST object-field lookup into `ApiRateLimitGraphQlObjectFieldResolver`.

## Post-Graph Changed Files

The stale graph artifact was generated at
`c889013e4402ab30060b2bb9dd6cb968fe96783c`. Files changed between that commit
and the pre-remediation PR head:

- `composer.lock`
- `docs/passkey-authentication.md`
- `specs/passkey-authentication/current-head-impact-context.md`
- `specs/passkey-authentication/manual-browser-evidence.md`
- `specs/passkey-authentication/manual-test-checklist.md`
- `specs/passkey-authentication/run-summary.md`
- `src/Shared/Application/EventListener/ApiRateLimitListener.php`
- `src/Shared/Application/Resolver/RateLimit/ApiRateLimitGraphQlAuthTargetResolver.php`
- `src/Shared/Application/Resolver/RateLimit/ApiRateLimitGraphQlDocumentResolver.php`
- `src/Shared/Application/Resolver/RateLimit/ApiRateLimitGraphQlFieldValueResolver.php`
- `src/Shared/Application/Resolver/RateLimit/ApiRateLimitGraphQlQueryInspection.php`
- `src/Shared/Application/Resolver/RateLimit/ApiRateLimitGraphQlQueryInspector.php`
- `src/Shared/Application/Resolver/RateLimit/ApiRateLimitGraphQlRootFields.php`
- `src/Shared/Application/Resolver/RateLimit/ApiRateLimitGraphQlVariableValueResolver.php`
- `src/Shared/Application/Resolver/RateLimit/ApiRateLimitNestedPayloadStringResolver.php`
- `src/Shared/Application/Resolver/RateLimit/ApiRateLimitPayloadValueResolver.php`
- `src/User/Infrastructure/Factory/RecoveryCodeBatchFactory.php`
- `tests/CLI/bats/make_negative_tests.bats`
- `tests/Integration/Auth/ApiRateLimitListenerIntegrationTest.php`
- `tests/Memory/GraphQL/GraphQLMemoryWebTestCase.php`
- `tests/Unit/Shared/Application/EventListener/ApiRateLimitListenerTest.php`
- `tests/Unit/Shared/Application/Resolver/RateLimit/ApiRateLimitGraphQlQueryInspectionDefaultValueTest.php`
- `tests/Unit/Shared/Application/Resolver/RateLimit/ApiRateLimitGraphQlQueryInspectionTest.php`
- `tests/Unit/Shared/Application/Resolver/RateLimit/ApiRateLimitPayloadValueResolverInvalidGraphQlFallbackTest.php`
- `tests/Unit/Shared/Application/Resolver/RateLimit/ApiRateLimitPayloadValueResolverTest.php`
- `tests/Unit/Shared/Application/Resolver/RateLimit/ApiRateLimitRequestResolverGraphQlFragmentLimitersTest.php`
- `tests/Unit/Shared/Application/Resolver/RateLimit/ApiRateLimitRequestResolverGraphQlLimitersTest.php`
- `tests/Unit/Shared/Application/Resolver/RateLimit/RateLimitClientTestCase.php`
- `tests/Unit/User/Application/Processor/PasskeyProcessorTest.php`
- `tests/Unit/User/Application/Resolver/PasskeyAuthMutationResolverConstructionTest.php`
- `tests/Unit/User/Application/Transformer/PasskeyJsonTransformerTest.php`
- `tests/Unit/User/Infrastructure/Factory/RecoveryCodeBatchFactoryTest.php`

The strict BMAD suppression-remediation delta adds the injected
`ApiRateLimitGraphQlDocumentResolver`, moves global limiter consumption before
endpoint-specific GraphQL target resolution, updates rate-limit construction
tests, applies passkey test coding-standard grouping fixes, and refreshes
current-head BMAD evidence files.

The final no-approval BMAD runner hardening on 2026-06-01 additionally changed:

- `.github/workflows/load-tests.yml`
- `scripts/ai-review-loop.sh`
- `scripts/ai-review-prompts/review.md`

This delta restricts the load-test workflow token to `contents: read`, pins the
checkout action used by the changed load-test workflow, disables persisted
checkout credentials, requires strict BMAD marker output from Codex reviews, and
allows detached automation worktrees to pass `AI_REVIEW_GITHUB_PR` or
`AI_REVIEW_PR_NUMBER` so the runner can post PR status updates without relying
on human approval or local branch-name inference. The final strict rerun also
keeps exact valid local base refs such as `HEAD` intact before falling back to
`origin/<branch>`, which preserves the Bats coverage for detached no-approval
review worktrees.

The strict FR/NFR remediation on 2026-06-01 additionally changed:

- `config/services_test.yaml`
- `docs/performance.md`
- `docs/passkey-authentication.md`
- `phpinsights-tests.php`
- `specs/passkey-authentication/manual-browser-evidence.md`
- `specs/passkey-authentication/manual-browser-run-1780280604724-451d4e.sanitized.md`
- `specs/passkey-authentication/manual-test-checklist.md`
- `specs/passkey-authentication/nfr-catalog-evidence.md`
- `specs/passkey-authentication/passkey-load-run-20260601T022759Z.sanitized.md`
- `specs/passkey-authentication/run-summary.md`
- `src/Shared/Application/Resolver/RateLimit/ApiRateLimitPayloadValueResolver.php`
- `src/User/Application/Factory/IssuedSessionFactory.php`
- `tests/Integration/Auth/PasskeyGraphQLAuthIntegrationTestCase.php`
- `tests/Integration/Auth/PasskeyGraphQLAuthOptionsIntegrationTest.php`
- `tests/Integration/Auth/PasskeyGraphQLCompletionFailureTest.php`
- `tests/Integration/Auth/PasskeyGraphQLCompletionResponseTest.php`
- `tests/Shared/Auth/Support/ControllableCommandBus.php`
- `tests/Unit/Shared/Auth/Support/ControllableCommandBusTest.php`

This delta adds `/api/graphql` passkey ceremony integration coverage, records
current-head passkey K6 smoke/average/stress/spike evidence, records a
Chrome DevTools virtual-authenticator browser rerun at runtime source base
`69af2cf13c46f797da7076bff272fa7736e01ce9`, removes the temporary PHPInsights
exclusions, and adds a DI-wired test command-bus decorator with direct unit
coverage. Later edits through the current PR-head evidence set include
`IssuedSessionFactory` rollback-failure logging, a legacy GraphQL rate-limit
regex fallback in `ApiRateLimitPayloadValueResolver`, the post-`109e7538`
single-use sign-up challenge fix that removes release/retry support from
`CompletePasskeySignUpCommandHandler`, `PasskeyChallengeResolver`,
`PasskeyChallenge`, `PasskeyChallengeRepositoryInterface`, and
`MongoDBPasskeyChallengeRepository`, and the production-readiness remediation
listed below.

The production-readiness remediation adds:

- `.env`, `.env.test`, and `.env.load_test` defaults for
  `PASSKEY_PRODUCTION_TRAFFIC_ENABLED` and
  `PASSKEY_PRODUCTION_MONITORING_READY`
- `config/services.yaml` registration for
  `PasskeyProductionReadinessListener`
- `config/api_platform/resources/EmptyResponse.yaml` and
  `.github/openapi-spec/spec.yaml` passkey REST `503` contract entries
- `config/api_platform/resources/AuthPayload.yaml` and
  `.github/graphql-spec/spec` passkey GraphQL descriptions
- `Makefile` and `scripts/normalize-graphql-passkey-descriptions.php`
  deterministic passkey GraphQL SDL description normalization
- `docker-compose.yml` and `docker-compose.prod.yml` pass-through for the
  production release flags
- `docs/advanced-configuration.md`, `docs/passkey-authentication.md`,
  `docs/planning/architecture.md`, and the passkey specs for manageability and
  release-readiness evidence
- `src/User/Application/EventListener/PasskeyProductionReadinessListener.php`
  and the split `tests/Unit/User/Application/EventListener/PasskeyReadiness*Test.php`
  production-readiness suite
- `tests/Integration/Auth/GraphQLAuthSupportTest.php` generated GraphQL
  description regression coverage

The final strict skill/coverage remediation adds:

- `.claude/skills/code-review/reference/fr-nfr-quality-gate.md`, stricter
  `code-review`, `testing-workflow`, `quality-standards`, BMAD QA, and BMAD
  adversarial-review instructions for FR/NFR extraction, automated coverage,
  flaky-test review, expected CI checks, graph impact, quality attributes, and
  no human-approval dependency for BMAD status.
- `scripts/ai-review-prompts/review.md` and `fix.md` updates so the AI review
  loop runs the same strict gate and executes validation commands itself.
- `.github/workflows/load-tests.yml` and `Makefile` changes so pull-request K6
  runs `make load-tests`, and load-test targets rebuild load-test database state
  before smoke/average/stress/spike execution.
- Passkey rate-limit resolver updates so REST and GraphQL authenticated
  registration paths use the registration limiter.
- K6 passkey sign-in and registration scripts use the shared seeded-user
  selector to avoid empty-fixture modulo failures.
- Focused unit and integration coverage for REST/GraphQL passkey registration
  rate limiting and REST existing-email signup rejection without challenge
  persistence.

## Relationship Edges

Runtime GraphQL rate-limit path:

- `ApiRateLimitRequestResolver`
  -> REST `/api/passkeys/(signup|register)/(options|complete)`
  -> registration limiter keyed by client IP
  -> `ApiRateLimitGraphQlAuthTargetResolver`
  -> `ApiRateLimitGraphQlPayloadResolver`
  -> GraphQL passkey sign-up and authenticated registration mutations
  -> registration limiter keyed by client IP
  -> `ApiRateLimitGraphQlQueryInspector`
  -> `ApiRateLimitGraphQlDocumentResolver`
  -> `ApiRateLimitGraphQlQueryInspection`
  -> `ApiRateLimitGraphQlRootFields`
  -> `ApiRateLimitGraphQlFieldValueResolver`
  -> `ApiRateLimitGraphQlObjectFieldResolver`
  -> `ApiRateLimitGraphQlVariableValueResolver`
  -> `ApiRateLimitNestedPayloadStringResolver`
- `ApiRateLimitPayloadValueResolver`
  -> `ApiRateLimitGraphQlQueryInspector`
  -> `ApiRateLimitGraphQlQueryInspection`
  -> legacy regex fallback only when GraphQL parsing returns `null`
- `ApiRateLimitGraphQlAuthTargetResolver`
  -> `ApiRateLimitClientIdentityResolver`
  -> `ApiRateLimitPayloadValueResolver` for fallback sign-in email resolution
- `ApiRateLimitListener`
  -> `ApiRateLimitRequestResolver`
  -> configured Symfony limiter factories
- `ApiRateLimitListener`
  -> global API limiter first
  -> endpoint-specific limiter resolution only after the global limiter accepts

Passkey ceremony path:

- REST options endpoints
  -> passkey option processors
  -> start passkey command handlers
  -> `PasskeyOptionsFactory`
  -> `PasskeyJsonTransformer`
  -> `PasskeyChallengeResolver`
  -> MongoDB passkey challenge repository
- REST completion endpoints
  -> passkey completion processors
  -> complete passkey command handlers
  -> `PasskeyCredentialValidator`
  -> `PasskeyJsonTransformer`
  -> atomic challenge claim
  -> credential repository
  -> issued session or existing 2FA pending-session behavior
- `IssuedSessionFactory`
  -> successful session token response unchanged after the browser rerun
  -> rollback-failure logging only when downstream token issuance throws
  -> unit coverage for rollback cleanup and rollback-failure log context
- `CompletePasskeySignUpCommandHandler`
  -> `PasskeyChallengeResolver::resolveSignup`
  -> `PasskeyChallengeRepositoryInterface::claimActive`
  -> consumed sign-up challenge remains consumed when session issuance or user
  persistence fails
  -> automated retry-after-rollback rejection coverage in
  `PasskeySignUpAuthenticationRollbackTest` and
  `PasskeyCredentialSaveFailureCommandHandlerTest`
- GraphQL passkey mutations use the same passkey application command handlers
  after API Platform resolver dispatch. The new rate-limit AST inspection runs
  before resolver dispatch and only determines limiter target keys.
- The split `PasskeyGraphQLAuth*` / `PasskeyGraphQLCompletion*` integration
  tests exercise the GraphQL passkey mutation envelope through `/api/graphql`;
  the test-only `ControllableCommandBus` delegates to the real command bus
  unless a specific test injects deterministic completion results for WebAuthn
  browser-only response-shape coverage.

Production-readiness path:

- `config/services.yaml`
  -> `PasskeyProductionReadinessListener`
  -> `PASSKEY_PRODUCTION_TRAFFIC_ENABLED`
  -> `PASSKEY_PRODUCTION_MONITORING_READY`
- `PasskeyProductionReadinessListener`
  -> passkey REST path matcher for `/api/passkeys/*/(options|complete)`
  -> JSON, form/multipart `operations`, raw-body, and query-string passkey
  GraphQL mutation detection through `ApiRateLimitGraphQlQueryInspector`
  -> URL-selected GraphQL `operationName` detection for multi-operation requests
  -> RFC 7807 `503` JSON response while `prod` traffic is disabled or
  monitoring readiness is false

Load-test CI path:

- `.github/workflows/load-tests.yml`
  -> workflow token permissions restricted to `contents: read`
  -> checkout action pinned to
  `actions/checkout@34e114876b0b11c390a56381ad16ebd13914f8d5`
  -> checkout credentials are not persisted
  -> `make load-tests`
  -> `docker-compose.load-tests.yml` isolated stack
  -> `make setup-load-test-db`
  -> collections and standard MongoDB indexes, with search-index processing
  skipped because this service has no mapped search indexes
  -> `tests/Load/run-load-tests.sh`
  -> smoke, average, stress, and spike profiles for
  `passkeySignupOptions`, `passkeySigninOptions`, and
  `passkeyRegistrationOptions`
- `PasskeyReadiness*Test`
  -> non-prod allowance
  -> subrequest skip
  -> disabled REST and GraphQL traffic
  -> GraphQL GET/query-string, decoy body, and URL-selected `operationName`
  coverage
  -> missing monitoring readiness
  -> ready production allowance
  -> non-passkey GraphQL allowance
- `GraphQLAuthSupportTest`
  -> passkey GraphQL operation descriptions in API Platform config
  -> generated SDL description regression coverage

Deterministic 2FA recovery-code testability path:

- `RecoveryCodeBatchFactory`
  -> injected random-byte closure when provided by tests
  -> default `random_bytes` closure in production
  -> `RecoveryCodeFactoryInterface`
  -> `RecoveryCodeRepositoryInterface::saveAll`

## Impact Surfaces

- Runtime paths: GraphQL rate limiting now parses selected operations,
  fragments, aliases, defaults, and nested variables through AST helpers.
  The latest hardening also seeds GraphQL limiter parsing from query-string
  `query`, `operationName`, and `variables`, overlays JSON/form/multipart
  `operations` fields, parses form/multipart `operations`, limits passkey
  sign-in options by top-level `email` only, and ignores nested
  `credential.email` decoys for sign-in options and completion.
  REST passkey WebAuthn ceremony paths are unchanged by the rate-limit parser
  extraction. GraphQL passkey options, validation, completion serialization,
  replay, wrong-user, and duplicate-conflict paths now have API-level
  integration coverage. `IssuedSessionFactory` changed rollback-failure logging
  after token issuance failures; the successful passkey completion session
  response and pending-2FA branch are unchanged by that post-run edit. The
  post-`109e7538` sign-up completion change removes challenge release after
  rollback failure, so retries after a claimed challenge fail with the same
  invalid/expired challenge path as replay and expiry.
- Architecture and layer boundaries: all changed rate-limit collaborators are
  in `Shared/Application/Resolver/RateLimit`; Domain remains framework-free.
- Domain model: `PasskeyChallenge` no longer exposes `release()`. Its
  `consumedAt` state remains one-way after `consume()`, keeping the Domain
  model aligned with single-use challenge semantics.
- Persistence and database: no new MongoDB mapping/index change after the stale
  graph artifact; passkey challenge TTL and credential indexes remain the
  existing PR changes. The challenge repository no longer offers `release()`,
  while `claimActive()` remains the atomic MongoDB update path for single-use
  challenge consumption.
- Public API and schema: current-head OpenAPI updates document passkey success
  and conflict responses. The final strict FR/NFR remediation adds explicit
  passkey GraphQL mutation descriptions in `AuthPayload.yaml` and the generated
  SDL, normalizes the generated SDL through `make generate-graphql-spec`, and
  documents the production-readiness `503` response for passkey REST operations.
- Async events and queues: no new async event or queue edge in the post-graph
  rate-limit changes.
- Configuration and environment: final strict FR/NFR remediation adds
  `PASSKEY_PRODUCTION_TRAFFIC_ENABLED` and
  `PASSKEY_PRODUCTION_MONITORING_READY` as production-only passkey release
  gates documented in `docs/passkey-authentication.md`. `docker-compose.yml`
  and `docker-compose.prod.yml` pass those flags into production PHP services.
- Dependencies and lockfiles: `composer.lock` changed before current head for
  dependency evidence. The suppression remediation adds no dependency.
- CI and workflows: automated evidence in `run-summary.md` includes focused
  rate-limit tests, Infection slices, PHP Insights, Psalm, and diff hygiene.
- Tests and fixtures: unit and integration coverage covers selected operation
  scoping, fragment traversal, aliases, repeated auth fields, default variables,
  invalid GraphQL fallback, deterministic recovery-code biased-byte rejection,
  API-level passkey GraphQL mutation execution, generated passkey GraphQL
  descriptions, and the production passkey readiness gate across REST,
  JSON GraphQL, form/multipart GraphQL `operations`, raw-body GraphQL,
  GET/query-string GraphQL, and URL-selected operation-name GraphQL requests.
- Documentation: `docs/passkey-authentication.md`, `docs/performance.md`,
  `nfr-catalog-evidence.md`, this impact context, and `run-summary.md` carry
  current-head automated evidence plus the manual browser source-impact bridge.
- Operations and observability: global limiter consumption now happens before
  endpoint-specific limiter resolution, which protects GraphQL AST target
  parsing with the cheap global throttle. The final readiness listener blocks
  `prod` passkey REST and GraphQL traffic with `503` unless the traffic flag and
  monitoring-readiness flag are both enabled.
- Security and privacy: GraphQL sign-up/sign-in rate limits now avoid decoy JSON
  fields, unselected operations, aliases, and string-token poisoning. Passkey
  sign-in privacy still uses empty `allowCredentials`, and API-level tests
  assert the same known-user/unknown-user response shape.
- Backward compatibility: public passkey APIs remain additive. Existing OAuth,
  password, and 2FA paths remain available.

## Manual Evidence Bridge

Historical manual browser evidence was executed at
`c0e6fe896143ecbeb26e0e54796c5eb38f3746e6` with a prior source bridge at
`b6ced150d8eacd4e2d59e099e6c72f043c8c875b`.

Browser/WebAuthn evidence was rerun on 2026-06-01 UTC against
`http://localhost:19081` with Google Chrome headless through Chrome DevTools
Protocol and virtual CTAP2 authenticators at runtime source base
`69af2cf13c46f797da7076bff272fa7736e01ce9`. The durable sanitized artifact is
`specs/passkey-authentication/manual-browser-run-1780280604724-451d4e.sanitized.md`.
The artifact is bridged to the current PR-head evidence set by the
production-change analysis above and by current automated coverage for session
rollback, GraphQL rate-limit extraction, atomic challenge claim,
retry-after-rollback rejection, GraphQL description quality, and production
readiness gating. No post-run source change altered browser-specific WebAuthn
ceremony code; the post-`109e7538` single-use sign-up challenge behavior is
server-side logic covered by repeatable automated tests.

## Quality Remediation

The forbidden PHPMD static-access suppression was removed from
`ApiRateLimitGraphQlQueryInspector`. GraphQL document parsing is now a separate
injected collaborator, `ApiRateLimitGraphQlDocumentResolver`, so the inspector
no longer owns Webonyx parser access or parse-failure fallback.
