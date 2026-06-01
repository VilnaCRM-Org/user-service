# Passkey Authentication Current-Head Impact Context

Generated: 2026-06-01 UTC

Base ref: `refs/remotes/origin/main`

Current PR head for this evidence refresh is recorded by the strict BMAD gate
with `git rev-parse HEAD` when the gate runs. This committed support file does
not pin the exact head SHA because evidence refresh commits change `HEAD`; the
strict-gate generated `codebase-graph-impact-context.md` is authoritative for
the exact reviewed commit.

Previous strict-gate graph artifact reported as stale before this remediation:
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
committed. The refreshed graph includes:

- `scripts/normalize-graphql-passkey-descriptions.php`
- `src/User/Application/EventListener/PasskeyProductionReadinessListener.php`
- `tests/Unit/User/Application/EventListener/PasskeyProductionReadinessListenerTest.php`

The strict BMAD gate also generates a fresh `codebase-graph-impact-context.md`
in its log directory for the exact head it reviews.

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
coverage. Later edits through the strict-gate reviewed head include
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
  and `tests/Unit/User/Application/EventListener/PasskeyProductionReadinessListenerTest.php`
- `tests/Integration/Auth/GraphQLAuthSupportTest.php` generated GraphQL
  description regression coverage

## Relationship Edges

Runtime GraphQL rate-limit path:

- `ApiRateLimitRequestResolver`
  -> `ApiRateLimitGraphQlAuthTargetResolver`
  -> `ApiRateLimitGraphQlQueryInspector`
  -> `ApiRateLimitGraphQlDocumentResolver`
  -> `ApiRateLimitGraphQlQueryInspection`
  -> `ApiRateLimitGraphQlRootFields`
  -> `ApiRateLimitGraphQlFieldValueResolver`
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
  -> JSON, raw-body, and query-string passkey GraphQL mutation detection through
  `ApiRateLimitGraphQlQueryInspector`
  -> URL-selected GraphQL `operationName` detection for multi-operation requests
  -> RFC 7807 `503` JSON response while `prod` traffic is disabled or
  monitoring readiness is false
- `PasskeyProductionReadinessListenerTest`
  -> non-prod allowance
  -> subrequest skip
  -> disabled REST and GraphQL traffic
  -> GraphQL GET/query-string and URL-selected `operationName` coverage
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
  JSON GraphQL, raw-body GraphQL, GET/query-string GraphQL, and URL-selected
  operation-name GraphQL requests.
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
The artifact is bridged to the strict-gate reviewed head by the
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
