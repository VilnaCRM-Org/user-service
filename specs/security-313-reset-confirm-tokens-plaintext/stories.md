# Stories — Security #313: Hash password-reset tokens at rest

Verification commands (one-off containers; do not start the shared stack):

```sh
# Unit tests (security-critical + regression)
docker run --rm -v /home/kravtsov/Projects/secfix-313:/app \
  -v /home/kravtsov/Projects/user-service/vendor:/app/vendor:ro -w /app \
  -e APP_ENV=test --entrypoint sh secfix-312-php:latest -lc \
  'php -d memory_limit=-1 vendor/bin/phpunit --testsuite=Unit \
   --filter "PasswordResetToken|ConfirmationToken|Repository|SeedSchemathesisData" --no-coverage'

# Architecture boundaries
docker run --rm ... -lc 'vendor/bin/deptrac analyse --config-file=deptrac.yaml --no-progress'
# Static analysis (changed files)
docker run --rm ... -lc 'vendor/bin/psalm --no-cache --no-progress <changed .php files>'
# Style (changed files)
docker run --rm ... -lc 'PHP_CS_FIXER_IGNORE_ENV=1 vendor/bin/php-cs-fixer fix --dry-run \
   --allow-risky=yes --config=.php-cs-fixer.dist.php --path-mode=intersection <changed .php files>'
```

---

## Story 1 — Persist only a SHA-256 hash of the reset token (FR-1, FR-4, NFR-Security)

Change `PasswordResetToken` so the constructor receives the plaintext, stores
`hash('sha256', $plain)` as `tokenValue` (the document id), keeps a transient
`plainToken`, and adds `hashToken()` / `matchesToken()` (constant-time).

Tests: `tests/Unit/User/Domain/Entity/PasswordResetTokenHashingTest.php`

- **Positive** — `testStoredValueIsHashNotPlaintext`, `testMatchesTokenAcceptsCorrectPlaintext`:
  stored value equals the SHA-256 of the plaintext and the correct plaintext matches.
- **Negative** — `testMatchesTokenRejectsWrongPlaintext`,
  `testMatchesTokenRejectsStoredHashSubmittedAsToken`: a wrong plaintext and the
  stored hash itself (what a DB-read attacker sees) both fail to match.
- **Edge** — `testHashTokenIsDeterministicAndSha256` (64-char deterministic hex),
  `testAttachPlainTokenRestoresDeliveryValue` (transient re-attachment).

## Story 2 — Look up reset tokens by hash, never by raw plaintext (FR-3, NFR-Security)

Change `MongoDBPasswordResetTokenRepository::findByToken()` to query
`['tokenValue' => PasswordResetToken::hashToken($token)]`.

Tests: `tests/Unit/User/Infrastructure/Repository/MongoDBPasswordResetTokenRepositoryTest.php`

- **Positive** — `testFindByTokenLooksUpByHashNotPlaintext`: `findOneBy` is called
  with the hashed criterion and returns the token.
- **Negative/Edge** — `testFindByTokenDoesNotQueryWithRawPlaintext`: asserts the
  query criterion is NOT the raw value and IS its SHA-256 hash.

## Story 3 — Deliver the usable plaintext token through the request → e-mail flow (FR-2, FR-5, NFR-Compatibility)

`RequestPasswordResetCommandHandler` emits `getPlainToken()` in the event;
`PasswordResetRequestedEventSubscriber` re-attaches `event->token` to the
reloaded entity; `PasswordResetEmailSendEventFactory` emits the plaintext.

Tests:
- `tests/Unit/User/Application/CommandHandler/RequestPasswordResetCommandHandlerTest.php`
  — **Positive**: event factory receives the plaintext token; **Negative**:
  unknown user publishes nothing.
- `tests/Unit/User/Application/EventSubscriber/PasswordResetRequestedEventSubscriberTest.php`
  — **Positive**: `attachPlainToken($event->token)` is invoked then the e-mail is
  dispatched; **Negative**: missing token short-circuits.
- `tests/Unit/User/Domain/Factory/Event/PasswordResetEmailSendEventFactoryTest.php`
  — **Positive/Edge**: emitted `tokenValue` equals the plaintext and differs from
  the stored hash.
- `tests/Unit/User/Domain/Factory/PasswordResetTokenFactoryTest.php`
  — **Edge**: plaintext length stays `tokenLength * 2`; stored value is 64 hex
  chars and matches its plaintext.

## Story 4 — Keep seeders, fixtures, and E2E flows working with hashed storage (NFR-Compatibility)

`InMemoryPasswordResetTokenRepository::findByToken()` hashes the candidate; the
Schemathesis/seeder tests assert by hashed key; E2E/Memory helpers replay the
plaintext captured at creation (`getPlainToken()` / `getLastPasswordResetToken()`).

Tests:
- `tests/Unit/DataFixtures/Seeder/PasswordResetTokenSeederTest.php` — **Positive**:
  seeded tokens are keyed by hash and resolvable via `findByToken(plain)`;
  **Edge**: existing-token refresh/extend/reset-usage paths still match by hash.
- `tests/Unit/DataFixtures/Command/SeedSchemathesisDataCommandTest.php` —
  **Positive/Edge**: stored tokens keyed by hashed fixture constants; existing
  token removal still counted.
