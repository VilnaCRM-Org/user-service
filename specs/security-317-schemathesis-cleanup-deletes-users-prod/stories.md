# Stories — Security #317

## Story 1 — Confine the Schemathesis cleanup listener to the schemathesis environment (FR-1)

**Change**
- `config/services.yaml`: remove the `SchemathesisCleanupListener` service+`kernel.terminate`
  registration from the base file, and add the class to the `App\:` autowiring `exclude` list so
  it is never auto-defined in `dev`/`test`/`load_test`/`prod`.
- `config/services_schemathesis.yaml`: declare the listener here (loaded only in the
  `schemathesis` env by `MicroKernelTrait`), injecting `$appEnv: '%kernel.environment%'` and
  `$cache: '@cache.user'`, tagged `kernel.terminate`.

**Test mapping**
- Positive: in the `schemathesis` env the listener is wired and deletes — covered by
  `SchemathesisCleanupListenerEnvironmentGuardTest::testListenerDeletesWhenEnvironmentIsSchemathesis`
  and the existing `SchemathesisCleanupListenerCleanupTest` suite (built with the default
  `schemathesis` env).
- Negative: container compilation must not define/instantiate the listener outside
  `schemathesis` — enforced by the exclude (no `string $appEnv` autowiring failure) and verified
  by the full Unit suite passing plus Psalm/Deptrac.
- Edge: load_test env (which also relaxes rate limiting) must not pick up the listener — covered
  by the `load_test` data-provider case in the guard test.

**Verify**
```
docker run --rm -v /home/kravtsov/Projects/secfix-317:/app \
  -v /home/kravtsov/Projects/user-service/vendor:/app/vendor:ro -w /app -e APP_ENV=test \
  --entrypoint sh secfix-312-php:latest -lc \
  'php -d memory_limit=-1 vendor/bin/phpunit --testsuite=Unit --filter "Schemathesis|Cleanup" --no-coverage 2>&1 | tail -25'
```

## Story 2 — Hard runtime environment guard in the listener (FR-2, FR-3)

**Change**
- `src/User/Infrastructure/EventListener/SchemathesisCleanupListener.php`: add
  `public const ENVIRONMENT = 'schemathesis'` and a `private readonly string $appEnv`
  constructor argument (first parameter). `__invoke` returns early when
  `$this->appEnv !== self::ENVIRONMENT`, before any matcher/extractor/repository work.

**Test mapping**
- Positive: deletion still happens when `appEnv === 'schemathesis'` —
  `testListenerDeletesWhenEnvironmentIsSchemathesis`.
- Negative: a fully matching malicious batch request (cleanup header, 201, `/api/users/batch`,
  victim emails) triggers ZERO repository/delete/event/cache calls when `appEnv` is
  `prod`/`dev`/`test`/`load_test` — `testListenerNeverDeletesOutsideSchemathesisEnvironment`
  (data provider). This FAILS without the guard and PASSES with it.
- Edge: existing matcher skip cases (missing header, non-2xx status, unhandled path, malformed
  payload) remain green in the `schemathesis` env — existing
  `SchemathesisCleanupListenerHeaderStatusSkipTest` / `...PayloadSkipTest`.

**Verify**
```
# Psalm on changed PHP files
docker run --rm -v /home/kravtsov/Projects/secfix-317:/app \
  -v /home/kravtsov/Projects/user-service/vendor:/app/vendor:ro -w /app -e APP_ENV=test \
  --entrypoint sh secfix-312-php:latest -lc \
  'php -d memory_limit=-1 vendor/bin/psalm --no-cache --no-progress \
   src/User/Infrastructure/EventListener/SchemathesisCleanupListener.php \
   tests/Unit/User/Infrastructure/EventListener/SchemathesisCleanupListenerTestCase.php \
   tests/Unit/User/Infrastructure/EventListener/SchemathesisCleanupListenerEnvironmentGuardTest.php 2>&1 | tail -15'

# Deptrac (architecture boundaries)
docker run --rm -v /home/kravtsov/Projects/secfix-317:/app \
  -v /home/kravtsov/Projects/user-service/vendor:/app/vendor:ro -w /app -e APP_ENV=test \
  --entrypoint sh secfix-312-php:latest -lc \
  'php -d memory_limit=-1 vendor/bin/deptrac analyse --config-file=deptrac.yaml --no-progress 2>&1 | tail -6'

# php-cs-fixer (style)
docker run --rm -v /home/kravtsov/Projects/secfix-317:/app \
  -v /home/kravtsov/Projects/user-service/vendor:/app/vendor:ro -w /app -e APP_ENV=test \
  --entrypoint sh secfix-312-php:latest -lc \
  'PHP_CS_FIXER_IGNORE_ENV=1 vendor/bin/php-cs-fixer fix --dry-run --allow-risky=yes \
   --config=.php-cs-fixer.dist.php --path-mode=intersection \
   src/User/Infrastructure/EventListener/SchemathesisCleanupListener.php 2>&1 | tail -15'
```
