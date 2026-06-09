# Stories — Security #323: OAuth provider exception message leak

## Story 1 — Return a static, provider-agnostic `detail` to clients

**As** an unauthenticated client of `/api/auth/social/*`
**I want** OAuth error responses to contain only a stable, generic message
**so that** the API never discloses upstream provider error text, endpoints, client_id,
or internal hostnames (CWE-209).

**Change**: `src/OAuth/Application/EventListener/OAuthExceptionListener.php`
- Add a static `detail` string to every entry in `ERROR_CODE_MAP`.
- Build the JSON response `detail` from `$mapping['detail']` instead of
  `$exception->getMessage()`.

**Covers**: FR-1, FR-2, NFR-Security, NFR-Compatibility, NFR-Maintainability.

**Test mapping** (`tests/Unit/OAuth/Application/EventListener/OAuthExceptionListenerTest.php`):
- Positive: `testProviderExceptionReturns503` — status `503` + `error_code`
  `provider_unavailable` still returned; `testResponseBodyContainsRequiredFields` and
  `testResponseContentTypeIsProblemJson` confirm contract/Content-Type unchanged.
- Negative: `testProviderExceptionDoesNotLeakRawUpstreamMessage` — a realistic raw Guzzle
  message containing a `client_id`, secret, `github.com` URL, internal hostname, and `401`
  status line is fed in; asserts NONE of those substrings appear in `detail` and that
  `detail` equals the exact static string. FAILS before the fix (old code echoed the raw
  message), PASSES after.
- Edge: `testHandledExceptionsNeverLeakTheirRawMessage` — iterates every handled exception
  type with a unique secret token embedded in its constructor message; asserts the secret
  never appears in `detail` for any of them (defense-in-depth across all map entries).

**Verification commands**:
```
docker run --rm -v /home/kravtsov/Projects/secfix-323:/app \
  -v /home/kravtsov/Projects/user-service/vendor:/app/vendor:ro -w /app -e APP_ENV=test \
  --entrypoint sh secfix-312-php:latest -lc \
  'php -d memory_limit=-1 vendor/bin/phpunit --testsuite=Unit \
   --filter "OAuthException|ExceptionListener" --no-coverage'
```

## Story 2 — Log the full exception server-side

**As** an operator
**I want** the full OAuth exception message and trace recorded in server logs
**so that** removing the message from the client response does not reduce diagnosability.

**Change**: `src/OAuth/Application/EventListener/OAuthExceptionListener.php`
- Inject `Psr\Log\LoggerInterface` (autowired via existing `_defaults`).
- On a handled exception, call `logger->error()` with the full message and a context array
  carrying `error_code` and the `exception` object.

**Covers**: FR-3, FR-4.

**Test mapping** (same test file):
- Positive: `testFullExceptionMessageIsLoggedServerSide` — asserts `logger->error()` is
  called once with a message containing the raw upstream text and a context array holding
  the same exception instance plus `error_code = provider_unavailable`.
- Negative/edge: `testIgnoredExceptionIsNotLogged` — an unmapped `RuntimeException` causes
  `logger->error()` to be called zero times and no response to be set
  (`testNonOAuthExceptionIsIgnored`).
- Construction edge: all existing tests now build the listener with a mocked
  `LoggerInterface`, proving the new constructor signature is wired correctly.

**Verification commands**:
```
# Full Unit suite (no regressions)
docker run --rm -v /home/kravtsov/Projects/secfix-323:/app \
  -v /home/kravtsov/Projects/user-service/vendor:/app/vendor:ro -w /app -e APP_ENV=test \
  --entrypoint sh secfix-312-php:latest -lc \
  'php -d memory_limit=-1 vendor/bin/phpunit --testsuite=Unit --no-coverage'

# Deptrac (architecture boundaries)
docker run --rm -v /home/kravtsov/Projects/secfix-323:/app \
  -v /home/kravtsov/Projects/user-service/vendor:/app/vendor:ro -w /app -e APP_ENV=test \
  --entrypoint sh secfix-312-php:latest -lc \
  'php -d memory_limit=-1 vendor/bin/deptrac analyse --config-file=deptrac.yaml --no-progress'

# Psalm (static analysis on changed files)
docker run --rm -v /home/kravtsov/Projects/secfix-323:/app \
  -v /home/kravtsov/Projects/user-service/vendor:/app/vendor:ro -w /app -e APP_ENV=test \
  --entrypoint sh secfix-312-php:latest -lc \
  'php -d memory_limit=-1 vendor/bin/psalm --no-cache --no-progress \
   src/OAuth/Application/EventListener/OAuthExceptionListener.php \
   tests/Unit/OAuth/Application/EventListener/OAuthExceptionListenerTest.php'

# PHP CS Fixer (style on changed files)
docker run --rm -v /home/kravtsov/Projects/secfix-323:/app \
  -v /home/kravtsov/Projects/user-service/vendor:/app/vendor:ro -w /app -e APP_ENV=test \
  --entrypoint sh secfix-312-php:latest -lc \
  'PHP_CS_FIXER_IGNORE_ENV=1 vendor/bin/php-cs-fixer fix --dry-run --allow-risky=yes \
   --config=.php-cs-fixer.dist.php --path-mode=intersection \
   src/OAuth/Application/EventListener/OAuthExceptionListener.php \
   tests/Unit/OAuth/Application/EventListener/OAuthExceptionListenerTest.php'
```

## Local verification results (this branch)

- PHPUnit (filtered `OAuthException|ExceptionListener`): `OK (20 tests, 92 assertions)`
- PHPUnit (full Unit suite): `OK (2262 tests, 6252 assertions)`
- Deptrac: `Violations 0`
- Psalm: `No errors found!`
- PHP CS Fixer: `Found 0 of 2 files that can be fixed`
