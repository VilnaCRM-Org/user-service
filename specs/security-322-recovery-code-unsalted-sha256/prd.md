# PRD — Security #322: Recovery-code verification falls back to unsalted SHA-256

## Problem

`RecoveryCode::matchesCode()` selected the verification algorithm dynamically from the
stored hash. When the stored value was a recognizable `password_hash()` output it used
Argon2id via `password_verify()` (good), but otherwise it fell back to
`legacyHash()` = `hash('sha256', strtolower($code))` compared with `hash_equals()`.

That fallback is a **single-iteration, unsalted, GPU-friendly SHA-256** over a very small
keyspace. Recovery codes are 8 chars from a 36-char alphabet and are lowercased before
hashing, leaving ~41 bits of entropy (36^8 candidates). An attacker with read access to the
`recovery_codes` collection (backup leak, read-only NoSQL injection, compromised replica,
insider) can exhaustively crack any legacy-stored code in seconds-to-minutes with hashcat,
and because the digest is unsalted a single precomputed pass attacks every user at once.
A recovered code satisfies the second factor in
`TwoFactorCodeValidator` / `CompleteTwoFactorCommandHandler`, bypassing 2FA.

Separately, the Argon2id path used `memory_cost = 8192` KiB (8 MiB), below OWASP's
recommended minimum of 19 MiB for Argon2id, weakening the primary path against
brute-forcing these low-entropy secrets.

CWE-916 (Use of Password Hash With Insufficient Computational Effort). Severity: MEDIUM.

## Functional Requirements

- **FR-1**: `RecoveryCode::matchesCode()` MUST verify a candidate code only against a
  modern, salted, adaptive password hash (Argon2id via `password_verify()`).
- **FR-2**: A stored value that is NOT a valid `password_hash()` output (e.g. a bare 64-hex
  SHA-256 digest) MUST never match any candidate code; `matchesCode()` returns `false`.
- **FR-3**: Recovery codes MUST continue to match case-insensitively (codes are normalized
  with `strtolower()` before hashing and before verification), preserving existing UX.
- **FR-4**: Newly issued recovery codes MUST be stored with Argon2id using a memory cost of
  at least 19456 KiB (19 MiB).

## Non-Functional Requirements

- **NFR-Security**: Remove the unsalted SHA-256 verification branch entirely so a database
  read alone is insufficient to recover plaintext recovery codes. Raise Argon2id
  `memory_cost` to the OWASP-recommended 19 MiB minimum. No secrets logged.
- **NFR-Compatibility**: Public API of `RecoveryCode` is unchanged (`matchesCode`,
  `getCodeHash`, `markAsUsed`, `isUsed`, `isValidFormat`, constructor signature). Doctrine
  ODM mapping (`codeHash` string field) is unchanged. Legacy SHA-256 rows simply stop
  authenticating and must be re-issued via the existing
  `RegenerateRecoveryCodesCommandHandler` flow — an intentional, documented behavior change.
- **NFR-Maintainability**: Solution stays inside the Domain layer with no new framework
  dependencies, no new directories, and no lowered quality thresholds (PHPInsights 94/100,
  Deptrac 0, Psalm 0). Dead code (`legacyHash`, `isPasswordHash`, `LEGACY_HASH_ALGORITHM`)
  is removed, reducing complexity.

## Out of Scope

- Data migration / re-hashing of existing stored SHA-256 rows (none are written by current
  code; the factory always stores Argon2id). Operationally such rows are re-issued via
  recovery-code regeneration.
- Widening the recovery-code alphabet/length or removing lowercasing (entropy increase) —
  a larger, behavior-changing effort tracked separately.
- Introducing a server-side pepper/HMAC keyed secret (optional hardening, not required once
  the fast unsalted path is removed).
