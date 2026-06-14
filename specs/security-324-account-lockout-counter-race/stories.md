# Stories — Atomic Account-Lockout Failure Counter (#324)

## Story 1 — Atomic increment + lock in `recordFailure()`

**Change**: `src/User/Infrastructure/Provider/RedisAccountLockoutProvider.php`

Replace the PSR-6 `CacheItemPoolInterface` read-modify-write with a raw
`\Redis` connection and a single `Redis::eval` Lua script that `INCR`s the
attempts key, sets the window TTL on first increment, and `SET`s the lock key
(with lockout TTL) once the threshold is crossed. `isLocked()` uses `exists()`;
`clearFailures()` uses `del()`.

Covers FR-1, FR-2, FR-3, FR-4, FR-5, NFR-Security, NFR-Maintainability.

**Test mapping** (`tests/Unit/.../RedisAccountLockoutProviderTest.php`):

- Positive: `testRecordFailureReturnsFalseBeforeThreshold` — eval returns 0 →
  `recordFailure` returns `false`.
- Positive: `testRecordFailureReturnsTrueWhenThresholdReached` — eval returns 1
  → `recordFailure` returns `true`.
- Positive: `testIsLockedReturnsTrueWhenLockKeyExists` /
  `testIsLockedReturnsFalseWhenLockKeyMissing` — `exists()` drives lock check.
- Positive: `testClearFailuresDeletesAttemptAndLockKeys` — `del()` of both keys.
- Negative (race regression):
  `testRecordFailureUsesSingleAtomicEvalWithoutReadModifyWrite` — asserts the
  Lua script contains `INCR`/`EXPIRE`, binds 2 keys (attempts + lock), and that
  the provider NEVER calls `get`/`set`/`incr` from PHP. This FAILS against the
  old read-modify-write implementation (which used `get`+`set`+`save` and no
  `eval`) and PASSES with the atomic fix.
- Edge: `testRecordFailurePassesThresholdAndTtlConfigurationToScript` — the
  window TTL (3600), max-attempts (20) and lockout TTL (900) are forwarded to
  the script as ARGV.
- Edge: `testRecordFailureTreatsFalseEvalResultAsNotLocked` — a `false` eval
  result is treated as "not locked".
- Config guards: `testMaxAttemptsReturnsConstant`,
  `testLockoutSecondsReturnsConstant`.

**Verification commands**:

```bash
docker run --rm -v /home/kravtsov/Projects/secfix-324:/app \
  -v /home/kravtsov/Projects/user-service/vendor:/app/vendor:ro -w /app \
  -e APP_ENV=test --entrypoint sh secfix-312-php:latest -lc \
  'php -d memory_limit=-1 vendor/bin/phpunit --testsuite=Unit --filter "Lockout" --no-coverage'
```

## Story 2 — Rewire DI to the raw Redis connection

**Change**: `config/services.yaml`

Bind `$lockoutRedis: '@app.account_lockout_redis_connection'` into the provider
and remove the now-orphaned `cache.account_lockout` PSR-6 RedisAdapter
definition.

Covers FR-5, NFR-Compatibility.

**Test mapping**: container boot is exercised by the full Unit suite and Deptrac
(service graph compiles, no dependency violations).

**Verification commands**:

```bash
docker run --rm -v /home/kravtsov/Projects/secfix-324:/app \
  -v /home/kravtsov/Projects/user-service/vendor:/app/vendor:ro -w /app \
  -e APP_ENV=test --entrypoint sh secfix-312-php:latest -lc \
  'php -d memory_limit=-1 vendor/bin/deptrac analyse --config-file=deptrac.yaml --no-progress'
```

## Story 3 — Keep Behat lockout helpers consistent with raw keys

**Change**: `tests/Behat/UserContext/SignInSecurityContext.php`,
`tests/Behat/UserContext/UserContext.php`, `config/services_test.yaml`

`SignInSecurityContext` now deletes lockout keys via the same raw `\Redis`
connection (`del()`), matching the keys the provider writes. `UserContext` drops
the obsolete `accountLockoutCachePool` dependency — `REDIS_LOCKOUT_URL` points at
Redis DB 0 in tests, which `RedisDatabaseMirror::flushDefaultAndHttpDatabases()`
already flushes before each scenario.

Covers NFR-Compatibility.

**Test mapping**:

- Negative/edge: existing `signin` lockout Behat scenarios (lockout reached,
  "minutes have passed", reset) continue to pass because the helper now clears
  the exact raw keys the provider sets.

**Verification commands**:

```bash
# Unit + static gates (run in CI; Behat exercised by the full functional suite)
docker run --rm -v /home/kravtsov/Projects/secfix-324:/app \
  -v /home/kravtsov/Projects/user-service/vendor:/app/vendor:ro -w /app \
  -e APP_ENV=test --entrypoint sh secfix-312-php:latest -lc \
  'php -d memory_limit=-1 vendor/bin/psalm --no-cache --no-progress \
     src/User/Infrastructure/Provider/RedisAccountLockoutProvider.php \
     tests/Unit/User/Infrastructure/Provider/RedisAccountLockoutProviderTest.php \
     tests/Behat/UserContext/SignInSecurityContext.php \
     tests/Behat/UserContext/UserContext.php'
```

## Aggregate verification (all gates green)

- phpunit (Lockout filter): `OK (11 tests, 24 assertions)`
- phpunit (full Unit): `OK (2261 tests, 6212 assertions)`
- deptrac: `Errors 0`
- psalm (changed files): `No errors found!`
- php-cs-fixer (changed files): `Found 0 of 4 files`
