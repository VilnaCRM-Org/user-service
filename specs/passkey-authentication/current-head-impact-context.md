# Passkey Authentication Current-Head Impact Context

Generated: 2026-06-01 UTC

Base ref: `refs/remotes/origin/main`

Current PR head is recorded by the strict BMAD gate with `git rev-parse HEAD`
when the gate runs. This committed support file does not pin the exact head SHA
because evidence refresh commits change `HEAD`; the strict-gate generated
`codebase-graph-impact-context.md` is authoritative for the exact reviewed
commit.

Previous strict-gate graph artifact reported as stale before this remediation:
`/home/kravtsov/tmp/bmad-pr286-strict-20260531_182458/review-loop-final-noapproval-20260601_001216/bmad-required-impact-and-github-context.md`

This file records current-head relationship evidence for the post-graph changes.
The local Graphify artifact was regenerated with
`uvx --from graphifyy graphify update . --force --no-cluster`, producing
`graphify-out/graph.json` with 20,350 nodes and 263,498 edges. Generated
`graphify-out/` artifacts are local review evidence and are not committed. The
strict BMAD gate also generates a fresh `codebase-graph-impact-context.md` in
its log directory for the exact head it reviews.

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
- `tests/Integration/Auth/PasskeyGraphQLAuthIntegrationTestCase.php`
- `tests/Integration/Auth/PasskeyGraphQLAuthOptionsIntegrationTest.php`
- `tests/Integration/Auth/PasskeyGraphQLCompletionFailureTest.php`
- `tests/Integration/Auth/PasskeyGraphQLCompletionResponseTest.php`
- `tests/Shared/Auth/Support/ControllableCommandBus.php`
- `tests/Unit/Shared/Auth/Support/ControllableCommandBusTest.php`

This delta adds `/api/graphql` passkey ceremony integration coverage, records
current-head passkey K6 smoke/average/stress/spike evidence, and records a
current-head Chrome DevTools virtual-authenticator browser rerun, removes the
temporary PHPInsights exclusions, and adds a DI-wired test command-bus
decorator with direct unit coverage without changing production runtime
services.

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
  -> challenge claim
  -> credential repository
  -> issued session or existing 2FA pending-session behavior
- GraphQL passkey mutations use the same passkey application command handlers
  after API Platform resolver dispatch. The new rate-limit AST inspection runs
  before resolver dispatch and only determines limiter target keys.
- The split `PasskeyGraphQLAuth*` / `PasskeyGraphQLCompletion*` integration
  tests exercise the GraphQL passkey mutation envelope through `/api/graphql`;
  the test-only `ControllableCommandBus` delegates to the real command bus
  unless a specific test injects deterministic completion results for WebAuthn
  browser-only response-shape coverage.

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
  integration coverage.
- Architecture and layer boundaries: all changed rate-limit collaborators are
  in `Shared/Application/Resolver/RateLimit`; Domain remains framework-free.
- Domain model: no passkey Domain entity/value-object behavior changed after
  the browser evidence bridge.
- Persistence and database: no new MongoDB mapping/index change after the stale
  graph artifact; passkey challenge TTL and credential indexes remain the
  existing PR changes.
- Public API and schema: current-head OpenAPI updates document passkey success
  and conflict responses. The strict FR/NFR remediation does not change API
  contracts, but verifies the existing GraphQL contract at runtime.
- Async events and queues: no new async event or queue edge in the post-graph
  rate-limit changes.
- Configuration and environment: no new environment variable in the suppression
  remediation. Existing passkey env documentation remains in
  `docs/passkey-authentication.md`.
- Dependencies and lockfiles: `composer.lock` changed before current head for
  dependency evidence. The suppression remediation adds no dependency.
- CI and workflows: automated evidence in `run-summary.md` includes focused
  rate-limit tests, Infection slices, PHP Insights, Psalm, and diff hygiene.
- Tests and fixtures: unit and integration coverage covers selected operation
  scoping, fragment traversal, aliases, repeated auth fields, default variables,
  invalid GraphQL fallback, deterministic recovery-code biased-byte rejection,
  and API-level passkey GraphQL mutation execution.
- Documentation: `docs/passkey-authentication.md`, `docs/performance.md`,
  `nfr-catalog-evidence.md`, this impact context, and `run-summary.md` carry
  the current-head evidence and remaining manual actions.
- Operations and observability: global limiter consumption now happens before
  endpoint-specific limiter resolution, which protects GraphQL AST target
  parsing with the cheap global throttle. This changes limiter consumption
  order but not EMF metric shape or alert contracts.
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

Current-head browser/WebAuthn evidence was rerun on 2026-06-01 UTC against
`http://localhost:19081` with Google Chrome headless through Chrome DevTools
Protocol and virtual CTAP2 authenticators. The durable sanitized artifact is
`specs/passkey-authentication/manual-browser-run-1780280604724-451d4e.sanitized.md`.

## Quality Remediation

The forbidden PHPMD static-access suppression was removed from
`ApiRateLimitGraphQlQueryInspector`. GraphQL document parsing is now a separate
injected collaborator, `ApiRateLimitGraphQlDocumentResolver`, so the inspector
no longer owns Webonyx parser access or parse-failure fallback.
