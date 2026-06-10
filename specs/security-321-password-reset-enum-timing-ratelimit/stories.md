# Stories — #321 password-reset enumeration timing & rate limiting

## Story 1 — Equalize handler work to remove the timing oracle (FR-1, FR-2)

**Change:** `src/User/Application/CommandHandler/RequestPasswordResetCommandHandler.php`
always calls `tokenFactory->create(...)` (with `''` for unknown users) before
branching, then only persists the token and publishes the event when the user
exists.

**Test mapping:**
`tests/Unit/User/Application/CommandHandler/RequestPasswordResetCommandHandlerTest.php`

- Positive: `testRequestPasswordResetForExistingUser` — token created, saved,
  event published, uniform response.
- Negative/edge: `testRequestPasswordResetForNonExistingUser` — `create('')`
  invoked exactly once (constant CSPRNG work), `save` / event factory / event
  bus never called, uniform response still returned. This FAILS pre-fix
  because the old handler never called `create()` on the not-found path.

## Story 2 — Dedicated per-IP limiter for POST /api/reset-password (FR-3)

**Change:** `ApiRateLimitRequestResolver` gains `resolvePasswordResetLimiter`
(exact path `=== /api/reset-password`, method `POST`) returning
`password_reset_ip` keyed by IP; registered in `resolveSingleEndpointLimiters`.
Wired in `config/packages/rate_limiter.yaml`, `config/services.yaml`
(`$limiterFactories` map), and env vars in `.env` / `.env.test`.

**Test mapping:**
`tests/Unit/Shared/Application/Resolver/RateLimit/ApiRateLimitRequestResolverLimitersTest.php`

- Positive: `testResolveEndpointLimitersForPasswordResetRequest` — returns
  `password_reset_ip` with `ip:<addr>` key.
- Negative: `testResolveEndpointLimitersSkipsPasswordResetIpForConfirmPath` —
  `/api/reset-password/confirm` does NOT match `password_reset_ip`, still maps
  `password_reset_confirm`. (Guards the path-prefix collision.)
- Edge: `testResolveEndpointLimitersSkipsPasswordResetIpForGetMethod` — non-POST
  is ignored.

## Story 3 — Normalize email limiter key (FR-4)

**Change:** `RateLimitedRequestPasswordResetHandlerDecorator` normalizes the
email with `strtolower(trim($email))` before `rateLimiter->create()`.

**Test mapping:**
`tests/Unit/User/Infrastructure/Decorator/RateLimitedRequestPasswordResetHandlerDecoratorTest.php`

- Positive: `testDelegatesWhenRateLimitAccepted` / `testThrowsExceptionWhenRateLimitExceeded`
  now assert the limiter is created with the normalized key.
- Edge: `testNormalizesEmailBeforeUsingItAsLimiterKey` — `"  USER@Example.COM\t"`
  produces key `user@example.com`. FAILS pre-fix (raw key was used).

## Story 4 — Validate the request DTO (FR-5)

**Change:** `RequestPasswordResetDto.email` annotated with `Assert\NotBlank`,
`Assert\Email`, `Assert\Length(max: 254)`; validated automatically by
`#[MapRequestPayload]` (→ 422 on failure).

**Test mapping:**
`tests/Unit/User/Application/DTO/RequestPasswordResetDtoTest.php`

- Positive: `testValidEmailPassesValidation` — 0 violations.
- Negative: `testBlankEmailFailsValidation`, `testMalformedEmailFailsValidation`
  — violations reported. FAIL pre-fix (no constraints).
- Edge: `testOversizedEmailFailsValidation` — >254-char email rejected.

## Story 5 — Lower per-email cap, add per-IP cap (NFR-Security)

**Change:** `.env` — `PASSWORD_RESET_RATE_LIMIT_MAX_REQUESTS` 1000 → 3;
add `PASSWORD_RESET_IP_RATE_LIMIT_MAX_REQUESTS=5`,
`PASSWORD_RESET_IP_RATE_LIMIT_INTERVAL="1 hour"`. `.env.test` mirrors the new
vars (high test values so E2E/kernel boot is unaffected). Config-only; covered
by the resolver/decorator unit tests above plus CI.

## Verification commands (one-off containers)

> Local verification examples. Replace `$REPO` with your checkout path,
> `$VENDOR` with a directory holding an installed `vendor/`, and
> `$PHP_IMAGE` with your locally built PHP image tag (e.g. the project's
> `*-php:latest` image). Adapt paths/tags to your environment.

```sh
# Unit (targeted + full)
docker run --rm -v "$REPO":/app \
  -v "$VENDOR":/app/vendor:ro -w /app \
  -e APP_ENV=test --entrypoint sh "$PHP_IMAGE" -lc \
  'php -d memory_limit=-1 vendor/bin/phpunit --testsuite=Unit \
   --filter "PasswordReset|RequestPasswordReset" --no-coverage'

# Deptrac
docker run --rm ... -lc 'php -d memory_limit=-1 vendor/bin/deptrac analyse \
  --config-file=deptrac.yaml --no-progress'

# Psalm (changed files)
docker run --rm ... -lc 'php -d memory_limit=-1 vendor/bin/psalm --no-cache \
  --no-progress <changed .php files>'

# php-cs-fixer (changed files)
docker run --rm ... -lc 'PHP_CS_FIXER_IGNORE_ENV=1 vendor/bin/php-cs-fixer fix \
  --dry-run --allow-risky=yes --config=.php-cs-fixer.dist.php \
  --path-mode=intersection <changed .php files>'
```

**Local results:** phpunit OK (151 filtered / 2266 full Unit) · deptrac
Violations 0 · psalm No errors · php-cs-fixer 0 of 8 files.
