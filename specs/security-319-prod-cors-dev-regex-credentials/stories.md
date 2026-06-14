# Stories — Security #319

Verification commands (one-off containers; never `make start`):

```bash
# Run from the repository root. APP_IMAGE defaults to the project's PHP image;
# override it if your local build is tagged differently.
APP_ROOT="$(git rev-parse --show-toplevel)"
APP_IMAGE="${APP_IMAGE:-user-service-php:latest}"

# Unit (Cors filter)
docker run --rm -v "$APP_ROOT":/app \
  -w /app -e APP_ENV=test --entrypoint sh "$APP_IMAGE" \
  -lc 'php -d memory_limit=-1 vendor/bin/phpunit --testsuite=Unit --filter "Cors" --no-coverage 2>&1 | tail -25'

# Deptrac
docker run --rm -v "$APP_ROOT":/app \
  -w /app -e APP_ENV=test --entrypoint sh "$APP_IMAGE" \
  -lc 'php -d memory_limit=-1 vendor/bin/deptrac analyse --config-file=deptrac.yaml --no-progress 2>&1 | tail -6'

# Psalm (changed file)
docker run --rm -v "$APP_ROOT":/app \
  -w /app -e APP_ENV=test --entrypoint sh "$APP_IMAGE" \
  -lc 'php -d memory_limit=-1 vendor/bin/psalm --no-cache --no-progress tests/Unit/Config/CorsAllowOriginDefaultTest.php 2>&1 | tail -15'

# php-cs-fixer (changed file)
docker run --rm -v "$APP_ROOT":/app \
  -w /app -e APP_ENV=test --entrypoint sh "$APP_IMAGE" \
  -lc 'PHP_CS_FIXER_IGNORE_ENV=1 vendor/bin/php-cs-fixer fix --dry-run --allow-risky=yes --config=.php-cs-fixer.dist.php --path-mode=intersection tests/Unit/Config/CorsAllowOriginDefaultTest.php 2>&1 | tail -15'
```

---

## Story 1 — Fail-closed CORS default in the production artifact (FR-1, FR-2, FR-6, NFR-Security)

**Change**: `.env` — replace the localhost regex default with the deny-all regex
`CORS_ALLOW_ORIGIN='(?!)'` and document that the variable is required at deploy
time with an HTTPS prod example.

Test mapping (`tests/Unit/Config/CorsAllowOriginDefaultTest.php`):

- **Positive**: `testCommittedEnvShipsFailClosedDenyAllDefault` — `.env` default
  equals `(?!)`.
- **Negative**: `testCommittedEnvDefaultDoesNotAllowLocalhostOrAnyOrigin` — the
  compiled regex `{(?!)}i` matches none of `http://localhost`,
  `http://localhost:3000`, `http://127.0.0.1`, `http://127.0.0.1:8080`,
  `https://app.vilnacrm.com`, `http://evil.example.com`.
- **Edge**: same test asserts the default is NOT the empty string (which would
  compile to the fail-open `{}i` matching every origin).

Proof it is a real regression test: with the original `.env`
(`^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$`) these two tests FAIL
(`Failed asserting that 1 is identical to 0` for `http://localhost`); with the
fix all 5 tests pass.

---

## Story 2 — Preserve dev allow-list (FR-3, NFR-Compatibility)

**Change**: `.env.dev` — add `CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'`
so local dev keeps reflecting localhost origins.

Test mapping:

- **Positive**: `testDevEnvKeepsLocalhostAllowList` — `.env.dev` value equals the
  localhost regex and matches `http://localhost:3000`, `http://127.0.0.1`.
- **Negative**: same test asserts `http://evil.example.com` is NOT matched.
- **Edge**: dev override is read from `.env.dev` only, never from `.env`
  (verified implicitly: `.env` default differs, Story 1).

---

## Story 3 — Preserve test allow-list (FR-4, NFR-Compatibility)

**Change**: `.env.test` — keep the localhost regex (documented as a test-only
override of the fail-closed `.env` default).

Test mapping:

- **Positive**: `testTestEnvKeepsLocalhostAllowList` — `.env.test` value equals the
  localhost regex.
- **Negative/Edge**: covered transitively — if `.env.test` lost the override the
  fail-closed `.env` default would break existing CORS-dependent tests.

---

## Story 4 — nelmio binding unchanged (FR-5, NFR-Maintainability)

**Change**: none to `config/packages/nelmio_cors.yaml`; pinned by test so a future
edit cannot silently hardcode origins.

Test mapping:

- **Positive**: `testNelmioCorsBindsOriginToEnvVarInEveryEnvironment` — `when@dev`,
  `when@test`, `when@prod` each set `allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']`.
- **Negative**: assertion fails if any env block hardcodes an origin instead of
  the env binding.
- **Edge**: all three environment blocks asserted, not just one.
