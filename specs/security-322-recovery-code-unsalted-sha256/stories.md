# Stories — Security #322

## Story 1 — Remove the unsalted SHA-256 verification fallback

**As** the user-service, **I want** recovery-code verification to rely solely on Argon2id
**so that** a leaked `recovery_codes` collection cannot be brute-forced offline.

Change: `src/User/Domain/Entity/RecoveryCode.php`

- `matchesCode()` now returns `password_verify($this->normalizeCode($plainCode), $this->codeHash)`.
- Removed `legacyHash()`, `isPasswordHash()`, and the `LEGACY_HASH_ALGORITHM` constant.

Covers: FR-1, FR-2, FR-3, NFR-Security, NFR-Maintainability.

### Test mapping (`tests/Unit/User/Domain/Entity/RecoveryCodeTest.php`)

- **Positive**: `testConstructorStoresCodeAsPasswordHash` — Argon2id-stored code still matches
  its plaintext; a different random code does not.
- **Positive (edge: case)**: `testMatchesCodeIsCaseInsensitive` — upper/lowercase variants of
  the same code both match.
- **Negative (regression — FAILS before fix)**: `testMatchesCodeRejectsLegacySha256Hashes` —
  a stored bare SHA-256 digest of the code is rejected for the correct plaintext (both case
  variants) and for an unrelated code. Before the fix the legacy branch returned `true` for
  the correct plaintext; now it returns `false`.

## Story 2 — Raise Argon2id memory cost to OWASP minimum (19 MiB)

**As** the user-service, **I want** Argon2id hashing of recovery codes to use >= 19 MiB memory
**so that** the primary hashing path resists brute force on low-entropy codes per OWASP.

Change: `src/User/Domain/Entity/RecoveryCode.php`

- `HASH_MEMORY_COST` raised from `8192` to `19456` (KiB).

Covers: FR-4, NFR-Security.

### Test mapping (`tests/Unit/User/Domain/Entity/RecoveryCodeTest.php`)

- **Positive / edge (regression — FAILS before fix)**:
  `testConstructorUsesOwaspCompliantArgon2idMemoryCost` — newly constructed code is an
  `argon2id` hash whose `memory_cost` option is `>= 19456`. Before the fix it was `8192`.

## Verification commands (one-off containers, read-only vendor)

```bash
# Unit (RecoveryCode filter)
docker run --rm -v /home/kravtsov/Projects/secfix-322:/app \
  -v /home/kravtsov/Projects/user-service/vendor:/app/vendor:ro -w /app -e APP_ENV=test \
  --entrypoint sh secfix-312-php:latest -lc \
  'php -d memory_limit=-1 vendor/bin/phpunit --testsuite=Unit --filter "RecoveryCode" --no-coverage'

# Deptrac (architecture boundaries)
docker run --rm -v /home/kravtsov/Projects/secfix-322:/app \
  -v /home/kravtsov/Projects/user-service/vendor:/app/vendor:ro -w /app -e APP_ENV=test \
  --entrypoint sh secfix-312-php:latest -lc \
  'php -d memory_limit=-1 vendor/bin/deptrac analyse --config-file=deptrac.yaml --no-progress'

# Psalm (static analysis on changed file)
docker run --rm -v /home/kravtsov/Projects/secfix-322:/app \
  -v /home/kravtsov/Projects/user-service/vendor:/app/vendor:ro -w /app -e APP_ENV=test \
  --entrypoint sh secfix-312-php:latest -lc \
  'php -d memory_limit=-1 vendor/bin/psalm --no-cache --no-progress src/User/Domain/Entity/RecoveryCode.php'

# php-cs-fixer (style)
docker run --rm -v /home/kravtsov/Projects/secfix-322:/app \
  -v /home/kravtsov/Projects/user-service/vendor:/app/vendor:ro -w /app -e APP_ENV=test \
  --entrypoint sh secfix-312-php:latest -lc \
  'PHP_CS_FIXER_IGNORE_ENV=1 vendor/bin/php-cs-fixer fix --dry-run --allow-risky=yes \
   --config=.php-cs-fixer.dist.php --path-mode=intersection \
   src/User/Domain/Entity/RecoveryCode.php tests/Unit/User/Domain/Entity/RecoveryCodeTest.php'
```

Pass criteria: phpunit "OK", deptrac "Violations 0", psalm "No errors", php-cs-fixer "0 of N".
