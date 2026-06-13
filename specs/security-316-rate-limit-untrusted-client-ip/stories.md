# Stories — Rate limiters keyed on untrusted client IP (#316)

## Story 1 — Configure trusted proxies and headers in production framework config

**As** the platform operator, **I want** the framework to trust only the
directly-connected reverse proxy and only the `X-Forwarded-For` header **so that**
`getClientIp()` returns the real client IP for IP-keyed rate limiters and
forged `X-Forwarded-For` is ignored.

**Changes**

- `config/packages/framework.yaml`: add
  `trusted_proxies: '%env(TRUSTED_PROXIES)%'` and
  `trusted_headers: '%env(TRUSTED_HEADERS)%'`.
- `.env`: add prod-safe defaults `TRUSTED_PROXIES=REMOTE_ADDR` (single trusted
  hop) and `TRUSTED_HEADERS=x-forwarded-for`.
- `config/packages/test/framework.yaml`: pin `trusted_headers: 'x-forwarded-for'`
  alongside the existing `trusted_proxies` so the test env applies the same
  spoof-resistant header set.

**Maps to**: FR-1, FR-2, FR-3, NFR-Security, NFR-Maintainability.

**Test mapping**

- Positive: `ApiRateLimitTrustedProxyIpKeyTest::testSignInIpKeyUsesRealClientIpWhenProxyIsTrusted`
  and `::testRegistrationIpKeyUsesRealClientIpWhenProxyIsTrusted` — trusted proxy
  - `X-Forwarded-For` ⇒ key uses the real client IP.
- Negative (spoof): `::testForgedForwardedForFromUntrustedClientIsIgnored` —
  untrusted client's forged `X-Forwarded-For` is ignored; key falls back to
  `REMOTE_ADDR`.
- Edge: `::testForwardedForIsIgnoredWhenNoProxyIsTrusted` — no trusted proxy ⇒
  client-supplied `X-Forwarded-For` is ignored entirely (no blanket trust).
- Regression: existing `ApiRateLimitListenerIntegrationTest` and resolver suites
  keep `ip:127.0.0.1` for local requests with no XFF (NFR-Compatibility).

**Verification commands** (one-off containers; vendor mounted read-only):

```bash
docker run --rm -v /home/kravtsov/Projects/secfix-316:/app \
  -v /home/kravtsov/Projects/user-service/vendor:/app/vendor:ro -w /app \
  -e APP_ENV=test --entrypoint sh secfix-316-php:latest -lc \
  'php -d memory_limit=-1 vendor/bin/phpunit --testsuite=Unit --filter "RateLimit" --no-coverage'
```

## Story 2 — Regression tests proving the spoof-resistant IP key

**As** a maintainer, **I want** unit tests that fail when IP-keyed limiters trust
an untrusted client IP **so that** the fix cannot silently regress.

**Changes**

- `tests/Unit/Shared/Application/Resolver/RateLimit/ApiRateLimitTrustedProxyIpKeyTest.php`
  (new): exercises both `ApiRateLimitAuthTargetResolver` (`signin_ip`) and
  `ApiRateLimitRequestResolver` (`registration`) `buildIpKey()` paths under
  `Request::setTrustedProxies()`, restoring global trusted-proxy state in
  `tearDown()`.

**Maps to**: FR-4, FR-5, NFR-Security.

**Test mapping**

- Positive / Negative / Edge as listed in Story 1.

**Verification commands**

```bash
# Unit (full suite — no regressions)
docker run --rm ... -e APP_ENV=test --entrypoint sh secfix-316-php:latest -lc \
  'php -d memory_limit=-1 vendor/bin/phpunit --testsuite=Unit --no-coverage'

# Deptrac (0 violations)
docker run --rm ... --entrypoint sh secfix-316-php:latest -lc \
  'php -d memory_limit=-1 vendor/bin/deptrac analyse --config-file=deptrac.yaml --no-progress'

# Psalm (no errors)
docker run --rm ... --entrypoint sh secfix-316-php:latest -lc \
  'php -d memory_limit=-1 vendor/bin/psalm --no-cache --no-progress \
   tests/Unit/Shared/Application/Resolver/RateLimit/ApiRateLimitTrustedProxyIpKeyTest.php'

# PHP CS Fixer (0 of N files)
docker run --rm ... --entrypoint sh secfix-316-php:latest -lc \
  'PHP_CS_FIXER_IGNORE_ENV=1 vendor/bin/php-cs-fixer fix --dry-run --allow-risky=yes \
   --config=.php-cs-fixer.dist.php --path-mode=intersection \
   tests/Unit/Shared/Application/Resolver/RateLimit/ApiRateLimitTrustedProxyIpKeyTest.php'
```

## Local verification results

- PHPUnit Unit (filter RateLimit): OK (143 tests, 258 assertions)
- PHPUnit Unit (full): OK (2262 tests, 6215 assertions)
- Deptrac: Errors 0
- Psalm: No errors found
- PHP CS Fixer: Found 0 of 1 files that can be fixed
