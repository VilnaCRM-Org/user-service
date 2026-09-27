# PRD — Security #313: Password-reset tokens stored in plaintext at rest

## Problem

Password-reset tokens were persisted verbatim in the `password_reset_tokens`
MongoDB collection. `PasswordResetTokenFactory::create()` generated a
high-entropy raw token (`bin2hex(random_bytes($tokenLength))`) and that raw value
was both:

1. **Stored** as the document identifier (`<id field-name="tokenValue"
strategy="NONE">`), and
2. **Emailed** to the user as the reset link credential.

`MongoDBPasswordResetTokenRepository::findByToken()` looked the token up by exact
equality on the raw value (`findOneBy(['tokenValue' => $token])`). Because the
stored value equalled the emailed bearer credential, any read access to the
collection (DB backup, replica snapshot, query log, read-only breach, or a
NoSQL-injection sink) yielded directly usable, unexpired reset tokens for every
account with a pending reset, enabling account takeover without further
interaction (CWE-256, HIGH).

The refresh-token subsystem already demonstrates the correct pattern
(`AuthRefreshToken` stores `hash('sha256', $plainToken)` and looks up by hash);
the password-reset path did not follow it.

## Functional Requirements

- **FR-1** — `PasswordResetToken` MUST persist only a SHA-256 hash of the token
  (`hash('sha256', $plainToken)`) as its stored value / document identifier. The
  plaintext token MUST NOT be persisted.
- **FR-2** — `PasswordResetToken` MUST expose the plaintext token transiently for
  e-mail delivery (`getPlainToken()`), populated at creation and re-attachable
  for delivery, never written to storage.
- **FR-3** — `MongoDBPasswordResetTokenRepository::findByToken()` MUST hash the
  incoming candidate (`PasswordResetToken::hashToken()`) before querying, so the
  lookup is by stored hash, never by raw plaintext.
- **FR-4** — `PasswordResetToken::matchesToken()` MUST compare a candidate
  plaintext against the stored hash using a constant-time comparison
  (`hash_equals`).
- **FR-5** — The password-reset request → e-mail flow MUST continue to deliver the
  usable plaintext token in the reset link: the request command handler carries
  the plaintext in the domain event, and the request subscriber re-attaches it to
  the reconstituted entity before the e-mail send event is built.

## Non-Functional Requirements

- **NFR-Security** — A read-only exposure of `password_reset_tokens` MUST NOT
  yield usable reset credentials. Submitting a stored hash to the confirm
  endpoint MUST fail (the hash of a hash does not match). Token generation
  continues to use `random_bytes` (256-bit entropy at the default length 32) and
  single-use / short expiry semantics are unchanged.
- **NFR-Compatibility** — The public reset/confirm HTTP and GraphQL contracts are
  unchanged. The Doctrine document identifier remains `tokenValue` (now a 64-char
  SHA-256 hex string); no migration of mapping strategy is required. Existing
  seeders, fixtures, and E2E flows continue to function by replaying the
  plaintext captured at creation time.
- **NFR-Maintainability** — The fix mirrors the established `AuthRefreshToken`
  hash-at-rest precedent, stays within hexagonal/DDD boundaries (hashing lives in
  the framework-free Domain entity, lookup hashing in Infrastructure), introduces
  no new directories or `*Service` suffixes, and keeps PHPInsights complexity and
  quality/style thresholds intact (Deptrac 0, Psalm clean, CS-Fixer clean).

## Out of Scope

- **Email-confirmation tokens** (`ConfirmationToken` via `RedisTokenRepository`).
  These are stored in an ephemeral Redis cache (24h TTL), keyed by both raw token
  value and user id, with the serialized document carrying the raw value. The
  brief grades this path "low"; hashing it would change the cache key/serialization
  contract and the user-id reverse lookup, a materially different and broader
  change than the HIGH MongoDB at-rest finding. Tracked separately to keep this
  remediation minimal and focused.
- Re-keying / re-hashing of tokens already persisted before deployment (short
  1-hour expiry drains them quickly).
- Adding a server-side pepper/HMAC. The token has 256 bits of entropy, so SHA-256
  is sufficient and consistent with the existing refresh-token precedent.
