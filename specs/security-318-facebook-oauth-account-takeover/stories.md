# Stories — Security #318: Facebook OAuth account takeover

## Story 1 — Facebook adapter must not forge a verified email (FR-1, FR-2)

**As** the OAuth resolver
**I want** the Facebook adapter to report its email as unverified
**So that** an attacker-controlled, Facebook-unverified email cannot auto-link
to or confirm a victim's local account.

### Change

`src/OAuth/Infrastructure/Provider/FacebookOAuthProvider.php`
— `buildProfile()` returns `OAuthUserProfile(..., emailVerified: false)`
instead of `true`, with a comment documenting why (Graph API cannot prove
email ownership; aligns with `emailAlwaysVerified() === false`).

### Test mapping

`tests/Unit/OAuth/Infrastructure/Provider/FacebookOAuthProviderTest.php`

- **Positive**: `testFetchProfileReturnsUnverifiedProfileWhenEmailIsPresent`
  — a present email yields a profile carrying email/name/providerId
  (email is still surfaced; the flow does not regress to email-unavailable).
- **Negative (regression for #318)**:
  `testFetchProfileMarksEmailUnverifiedToPreventAutoLinkTakeover` — asserts
  `$profile->emailVerified === false`. This FAILS on the old hardcoded `true`
  and PASSES with the fix; it is the direct takeover regression guard.
- **Edge**: `testFetchProfileThrowsEmailUnavailableWhenEmailIsNull` (unchanged)
  — a null email still throws `OAuthEmailUnavailableException`
  (`provider_email_unavailable`), proving the null path is preserved.
- **Edge**: `testFetchProfileUsesEmptyStringWhenNameIsNull` (unchanged) —
  a null name still maps to `''`.

### End-to-end gate already enforced by the resolver (FR-2)

`tests/Unit/OAuth/Application/Resolver/OAuthUserResolverTest.php` (unchanged,
pre-existing) proves the consequence of `emailVerified === false`:

- `testResolveRejectsUnverifiedEmailBeforeAutoLink` — no auto-link, no
  `findByEmail`, no `save`; throws `UnverifiedProviderEmailException`.
- `testResolveRejectsUnverifiedEmailBeforeNewUserCreation` — no account
  creation; throws `UnverifiedProviderEmailException`.

## Story 2 — Already-linked Facebook identities keep working (FR-3)

**As** a user who previously linked Facebook
**I want** my existing `SocialIdentity` to keep resolving
**So that** the security fix does not lock out legitimate linked accounts.

### Test mapping

`tests/Unit/OAuth/Application/Resolver/OAuthUserResolverTest.php`
(unchanged, pre-existing):

- **Positive**: `testResolveReturnsExistingUserWhenIdentityExists` — when a
  `SocialIdentity(provider, providerId)` already exists, resolution returns the
  linked user _before_ the `emailVerified` gate, so unverified Facebook emails
  do not break already-linked sign-ins.

## Verification commands

Run from `/home/kravtsov/Projects/secfix-318` via one-off containers
(image `secfix-312-php:latest`, read-only shared vendor):

```bash
# Unit (OAuth/Facebook/resolver)
docker run --rm -v /home/kravtsov/Projects/secfix-318:/app \
  -v /home/kravtsov/Projects/user-service/vendor:/app/vendor:ro \
  -w /app -e APP_ENV=test --entrypoint sh secfix-312-php:latest -lc \
  'php -d memory_limit=-1 vendor/bin/phpunit --testsuite=Unit \
   --filter "OAuthUser|Facebook|OAuth" --no-coverage'

# Architecture boundaries
docker run --rm ... --entrypoint sh secfix-312-php:latest -lc \
  'php -d memory_limit=-1 vendor/bin/deptrac analyse \
   --config-file=deptrac.yaml --no-progress'

# Static analysis (changed files)
docker run --rm ... --entrypoint sh secfix-312-php:latest -lc \
  'php -d memory_limit=-1 vendor/bin/psalm --no-cache --no-progress \
   src/OAuth/Infrastructure/Provider/FacebookOAuthProvider.php \
   tests/Unit/OAuth/Infrastructure/Provider/FacebookOAuthProviderTest.php'

# Code style (changed files)
docker run --rm ... --entrypoint sh secfix-312-php:latest -lc \
  'PHP_CS_FIXER_IGNORE_ENV=1 vendor/bin/php-cs-fixer fix --dry-run \
   --allow-risky=yes --config=.php-cs-fixer.dist.php \
   --path-mode=intersection \
   src/OAuth/Infrastructure/Provider/FacebookOAuthProvider.php \
   tests/Unit/OAuth/Infrastructure/Provider/FacebookOAuthProviderTest.php'
```

**Pass criteria**: phpunit OK, deptrac `Violations 0`, psalm `No errors`,
php-cs-fixer `0 of N files`.
