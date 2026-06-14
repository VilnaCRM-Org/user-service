# Stories — Security #315: GraphQL endpoint bypasses per-endpoint rate limiters

## Verification commands (one-off containers)

```bash
# Unit (RateLimit filter)
docker run --rm -v $PWD:/app -v /home/kravtsov/Projects/user-service/vendor:/app/vendor:ro \
  -w /app -e APP_ENV=test --entrypoint sh secfix-312-php:latest -lc \
  'php -d memory_limit=-1 vendor/bin/phpunit --testsuite=Unit --filter "RateLimit" --no-coverage'

# Deptrac
docker run --rm -v $PWD:/app -v /home/kravtsov/Projects/user-service/vendor:/app/vendor:ro \
  -w /app -e APP_ENV=test --entrypoint sh secfix-312-php:latest -lc \
  'php -d memory_limit=-1 vendor/bin/deptrac analyse --config-file=deptrac.yaml --no-progress'

# Psalm (changed files)
docker run --rm -v $PWD:/app -v /home/kravtsov/Projects/user-service/vendor:/app/vendor:ro \
  -w /app -e APP_ENV=test --entrypoint sh secfix-312-php:latest -lc \
  'php -d memory_limit=-1 vendor/bin/psalm --no-cache --no-progress \
   src/Shared/Application/Resolver/RateLimit/ApiRateLimitGraphQlResolver.php \
   src/Shared/Application/Resolver/RateLimit/ApiRateLimitRequestResolver.php'

# php-cs-fixer (changed files)
docker run --rm -v $PWD:/app -v /home/kravtsov/Projects/user-service/vendor:/app/vendor:ro \
  -w /app -e APP_ENV=test --entrypoint sh secfix-312-php:latest -lc \
  'PHP_CS_FIXER_IGNORE_ENV=1 vendor/bin/php-cs-fixer fix --dry-run --allow-risky=yes \
   --config=.php-cs-fixer.dist.php --path-mode=intersection \
   src/Shared/Application/Resolver/RateLimit/ApiRateLimitGraphQlResolver.php'
```

## Story 1 — Map sensitive GraphQL mutations to REST limiter targets (FR-1..FR-6, NFR-Security)

**Change:** New `App\Shared\Application\Resolver\RateLimit\ApiRateLimitGraphQlResolver`.
For `POST /api/graphql` it decodes the JSON body, detects sensitive mutation field
names in the `query`, and returns the same `{name, key}` limiter targets the REST
resolvers would return — extracting `email` / `pendingSessionId` from
`variables.input` and the JWT subject for sign-out mutations.

**Tests** (`tests/Unit/.../ApiRateLimitGraphQlResolverTest.php`):

- Positive:
  - `testSignInMutationProducesIpAndEmailLimiters` — `signin_ip` (IP) + `signin_email` (email).
  - `testRefreshTokenMutationProducesRefreshLimiter` — `refresh_token` (IP).
  - `testConfirmPasswordResetMutationProducesConfirmLimiter` — `password_reset_confirm` (IP).
  - `testCompleteTwoFactorMutationProducesIpAndUserLimiters` — `twofa_verification_ip` + `twofa_verification_user`.
  - `testSignOutMutationProducesUserLimiterForAuthenticatedRequest` — `signout` keyed by JWT subject.
  - `testMultipleSensitiveMutationsAreAllThrottled` — multiple fields each throttled.
- Negative:
  - `testNonGraphQlPathReturnsNoTargets`, `testGraphQlGetRequestReturnsNoTargets`,
    `testNonSensitiveMutationReturnsNoTargets`, `testInvalidJsonBodyReturnsNoTargets`,
    `testMissingQueryFieldReturnsNoTargets`, `testSignOutAllMutationSkippedWhenUnauthenticated`.
- Edge:
  - `testSignInMutationWithoutEmailStillThrottlesByIp` — IP floor with no email.
  - `testCompleteTwoFactorWithoutPendingSessionOnlyThrottlesByIp` — IP floor, no resolvable user.

**Fails without fix:** the class does not exist (the bypass is the absence of any
GraphQL throttling), so these assertions cannot hold pre-fix.

## Story 2 — Wire the GraphQL resolver into endpoint limiter resolution (FR-1..FR-5, NFR-Maintainability)

**Change:** `ApiRateLimitRequestResolver` gains an `ApiRateLimitGraphQlResolver`
constructor dependency (autowired) and appends its targets in
`resolveEndpointLimiters`, so the existing `ApiRateLimitListener` consumes the
GraphQL-derived limiters with no listener change.

**Tests** (`tests/Unit/.../ApiRateLimitRequestResolverLimitersTest.php`):

- Positive: `testResolveEndpointLimitersForGraphQlSignInMutation` (signin_ip + signin_email),
  `testResolveEndpointLimitersForGraphQlRefreshTokenMutation` (refresh_token).
- Regression: full existing REST limiter suite still green
  (`testResolveEndpointLimitersForSignIn`, `...ForOauthTokenPath`, etc.).
- Negative coverage inherited: `testResolveEndpointLimitersReturnsEmptyForUnrecognizedApiPath`.

**Fails without fix:** before wiring, `resolveEndpointLimiters` returns `[]` for
`/api/graphql`, so `signin_ip`/`signin_email`/`refresh_token` keys are absent.

## Story 3 — Keep existing rate-limit tests constructing the resolver valid (NFR-Compatibility)

**Change:** Update test factories `RateLimitClientTestCase::createRequestResolver`
and `ApiRateLimitListenerTest` helpers to pass the new third constructor argument
(via `createGraphQlResolver`).

**Tests:** Entire `--filter RateLimit` Unit set (155 tests) and full Unit suite
(2274 tests) pass; deptrac 0, psalm clean, php-cs-fixer clean.
