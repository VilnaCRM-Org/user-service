# PRD — Atomic Account-Lockout Failure Counter (#324)

## Problem

`RedisAccountLockoutProvider::recordFailure()` implemented the per-account
failed-login counter as a non-atomic read-modify-write:

1. `getItem($attemptsKey)->get()` reads the current count.
2. `+1` is computed in PHP.
3. `set()` / `expiresAfter()` / `save()` persists the new value.

Under concurrency this is a lost-update race (CWE-362). Multiple parallel
failed-login requests for the same email read the same starting value and each
persist `value + 1`, so N concurrent requests advance the counter by far fewer
than N. Because this email-keyed lockout (`MAX_ATTEMPTS = 20` within a 1h
window) is the primary per-account brute-force control on the GraphQL `signIn`
path — where the `signin_email` / `signin_ip` rate limiters are bypassed — the
undercount lets an attacker fire concurrent requests and make materially more
than 20 password guesses per window against a single account before the lock is
ever applied.

The codebase already established the correct pattern for atomic Redis security
operations: `RedisOAuthStateRepository::validateAndConsume()` uses a
server-side Lua script (`Redis::eval`) to GET+DEL OAuth state atomically.

## Functional Requirements

- **FR-1**: `recordFailure()` MUST increment the failure counter atomically on
  the Redis server (single round trip), eliminating the PHP-side
  read-modify-write window.
- **FR-2**: On the first failure within a window the attempts key MUST receive
  the 3600s expiry; subsequent increments MUST NOT reset that expiry.
- **FR-3**: When the increment crosses `MAX_ATTEMPTS` (20), the lock key MUST be
  set atomically with the `LOCKOUT_SECONDS` (900s) TTL, set exactly once on the
  threshold-crossing request.
- **FR-4**: `recordFailure()` MUST return `true` when the account is locked
  (attempts at or above the threshold) and `false` otherwise, preserving the
  `UserCredentialValidator` contract (locked → `LockedHttpException`, otherwise
  → `UnauthorizedHttpException`).
- **FR-5**: `isLocked()` and `clearFailures()` MUST operate on the same raw
  Redis keys used by `recordFailure()` so lock detection and reset stay
  consistent.

## Non-Functional Requirements

### NFR-Security

- The failure counter and lock transition MUST be free of lost-update / TOCTOU
  races (CWE-362). The increment and threshold lock MUST execute as one atomic
  server-side operation. No PHP-side `get` then `set` for the security counter.
- Email keys MUST remain hashed (SHA-256) before use as Redis keys (no PII in
  key names) — unchanged from the prior behaviour.
- The Lua script body MUST be a hardcoded constant; only bound `KEYS`/`ARGV`
  parameters are passed (no user input interpolated into the script), matching
  the `RedisOAuthStateRepository` precedent.

### NFR-Compatibility

- Public method signatures of `AccountLockoutProviderInterface` are unchanged;
  `UserCredentialValidator` and all callers are unaffected.
- Redis key names (`signin_lockout_<sha256>`, `signin_lock_<sha256>`) and the
  20-attempt / 900s lock semantics are preserved.
- The provider now binds the existing `app.account_lockout_redis_connection`
  `\Redis` service directly instead of the `cache.account_lockout` PSR-6 pool;
  the orphaned pool definition is removed. Behat lockout helpers operate on the
  same raw connection / DB.

### NFR-Maintainability

- The fix reuses the established atomic-Lua pattern (`Redis::eval`) already
  present in `RedisOAuthStateRepository`, keeping one idiom for atomic Redis
  security primitives.
- Hexagonal/DDD boundaries are respected: the provider stays in
  `User/Infrastructure/Provider`; no Domain change; no threshold/Deptrac config
  weakened.
- PHPInsights complexity ≥ 94% and quality/style 100% are preserved (clean,
  low-branch implementation).

## Out of Scope

- The separate finding that `signin_email` / `signin_ip` rate limiters are
  bypassed on the GraphQL `signIn` path (tracked independently).
- Changing the 20-attempt threshold or 900s lockout duration.
- Distributed-lock or sliding-window redesign of the lockout policy.
- Migrating other PSR-6 cache usages to raw Redis.
