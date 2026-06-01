# Passkey Authentication Current-Head Impact Context

Generated: 2026-06-01 UTC

Base ref: `refs/remotes/origin/main`

Current PR head is recorded by the strict BMAD gate with `git rev-parse HEAD`
when the gate runs. Latest pushed current-head evidence baseline:
`2395eb1b7fef05479e53e621182697944fb814d0`.

Previous strict-gate graph artifact reported as stale before this remediation:
`/home/kravtsov/tmp/bmad-pr286-strict-20260531_182458/review-loop-final-noapproval-20260601_001216/bmad-required-impact-and-github-context.md`

This file records current-head relationship evidence for the post-graph changes.
The local Graphify artifact was regenerated with
`uvx --from graphifyy graphify update . --force --no-cluster`, producing
`graphify-out/graph.json` with 20,236 nodes and 35,311 edges. Generated
`graphify-out/` artifacts are local review evidence and are not committed. The
strict BMAD gate also generates a fresh `codebase-graph-impact-context.md` in
its log directory for the exact head it reviews.

## Post-Graph Changed Files

The stale graph artifact was generated at
`c889013e4402ab30060b2bb9dd6cb968fe96783c`. Files changed between that commit
and the current PR head under review:

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
- `tests/Unit/User/Application/Factory/PasskeyOptionsFactoryTest.php`
- `tests/Unit/User/Infrastructure/Factory/RecoveryCodeBatchFactoryTest.php`

The strict BMAD suppression-remediation delta adds the injected
`ApiRateLimitGraphQlDocumentResolver`, moves global limiter consumption before
endpoint-specific GraphQL target resolution, updates rate-limit construction
tests, and refreshes current-head BMAD evidence files.

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
  extraction.
- Architecture and layer boundaries: all changed rate-limit collaborators are
  in `Shared/Application/Resolver/RateLimit`; Domain remains framework-free.
- Domain model: no passkey Domain entity/value-object behavior changed after
  the browser evidence bridge.
- Persistence and database: no new MongoDB mapping/index change after the stale
  graph artifact; passkey challenge TTL and credential indexes remain the
  existing PR changes.
- Public API and schema: current-head OpenAPI updates document passkey success
  and conflict responses. The suppression remediation does not change API
  contracts.
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
  invalid GraphQL fallback, and deterministic recovery-code biased-byte
  rejection.
- Documentation: `docs/passkey-authentication.md`, this impact context, and
  `run-summary.md` carry the current-head evidence.
- Operations and observability: global limiter consumption now happens before
  endpoint-specific limiter resolution, which protects GraphQL AST target
  parsing with the cheap global throttle. This changes limiter consumption
  order but not EMF metric shape or alert contracts.
- Security and privacy: GraphQL sign-up/sign-in rate limits now avoid decoy JSON
  fields, unselected operations, aliases, and string-token poisoning. Passkey
  sign-in privacy still uses empty `allowCredentials`.
- Backward compatibility: public passkey APIs remain additive. Existing OAuth,
  password, and 2FA paths remain available.

## Manual Evidence Bridge

Manual browser evidence was executed at
`c0e6fe896143ecbeb26e0e54796c5eb38f3746e6` with a prior source bridge at
`b6ced150d8eacd4e2d59e099e6c72f043c8c875b`.

The current PR head under review has a bridge in
`specs/passkey-authentication/manual-browser-evidence.md`. That bridge states
that no browser ceremony was rerun on current head and explains why post-bridge
changes do not alter the REST WebAuthn ceremony behavior.

## Quality Remediation

The forbidden PHPMD static-access suppression was removed from
`ApiRateLimitGraphQlQueryInspector`. GraphQL document parsing is now a separate
injected collaborator, `ApiRateLimitGraphQlDocumentResolver`, so the inspector
no longer owns Webonyx parser access or parse-failure fallback.
