# PRD — Security #318: Facebook OAuth account takeover via forged `emailVerified=true`

## Problem

The Facebook social-login adapter
(`src/OAuth/Infrastructure/Provider/FacebookOAuthProvider.php`) hardcoded
`emailVerified: true` in the `OAuthUserProfile` it returned, after only
checking that `getEmail() !== null`.

This contradicts:

- The adapter's own capability flag `emailAlwaysVerified()` which returns
  `false` (Facebook cannot guarantee a verified email).
- The OAuth Social Sign-In ADR
  (`specs/oauth-social-signin-architecture.md`, sections 3, 4.3, 4.6), which
  marks Facebook as `emailAlwaysVerified() = false` and requires every provider
  to supply a **verified** email before the resolver may auto-link or create an
  account.

The Facebook Graph API exposes no email-verification field
(`vendor/league/oauth2-facebook/src/Provider/FacebookUser.php`) and never proves
the user owns the mailbox. Facebook allows a user to set a profile/login email
without verifying ownership.

### Exploit (CWE-287 — Improper Authentication)

1. Victim has a local account `user@example.com`.
2. Attacker creates a Facebook account and sets its email to
   `user@example.com` (Facebook does not verify ownership).
3. Attacker signs in via "Sign in with Facebook".
4. The adapter returned `OAuthUserProfile(email=user@example.com,
   emailVerified=true)`.
5. `OAuthUserResolver` found no existing Facebook `SocialIdentity`, passed the
   `emailVerified` gate, found the victim by email, and `handleAutoLink()`
   bound the attacker's Facebook identity to the victim's account — even
   marking an unconfirmed victim account `confirmed=true`.
6. `HandleOAuthCallbackCommandHandler` issued a session/JWT for the victim.
   The attacker is authenticated as the victim.

GitHub (verified primary email via `/user/emails`), Google
(`getEmailVerified() === true`) and Twitter (`emailVerified: false`) already
handle this correctly. Only Facebook was broken.

## Functional Requirements

- **FR-1**: `FacebookOAuthProvider::buildProfile()` MUST return an
  `OAuthUserProfile` with `emailVerified === false`, consistent with
  `emailAlwaysVerified() === false`. It MUST NOT hardcode `emailVerified:
  true`.
- **FR-2**: When a Facebook profile reaches `OAuthUserResolver::resolve()` with
  no pre-existing `SocialIdentity`, the resolver MUST reject it with
  `UnverifiedProviderEmailException` (existing behaviour, now actually
  exercised for Facebook). No auto-link, no account creation, no confirmation
  flip, no session issuance.
- **FR-3**: Existing Facebook `SocialIdentity` records (already deliberately
  linked) continue to resolve to their user — the verified-email gate is only
  reached for *new* identities, so already-linked sign-ins are unaffected.

## Non-Functional Requirements

- **NFR-Security**: A single mis-coded adapter must not be able to assert a
  verified email it cannot prove. Facebook emails are treated as unverified
  (fail-closed) until a dedicated post-OAuth email-verification flow exists
  (out of scope). Eliminates the CWE-287 account-takeover vector.
- **NFR-Compatibility**: No public API, DTO, route, or storage schema change.
  `OAuthUserProfile` shape is unchanged. The `provider_email_unavailable`
  (null email) path is preserved. Behaviour for GitHub/Google/Twitter is
  untouched.
- **NFR-Maintainability**: Change is a single boolean literal plus an
  explanatory comment, keeping the adapter aligned with the Twitter adapter.
  No new classes, directories, or dependencies; hexagonal/DDD boundaries and
  Deptrac layers unchanged. PHPInsights complexity/quality/style thresholds
  preserved.

## Out of Scope

- Building a post-OAuth email-verification flow that would let Facebook users
  auto-link safely.
- Refactoring `OAuthUserResolver::resolve()` to additionally consume the
  provider's `emailAlwaysVerified()` capability for defense-in-depth — the
  resolver already correctly gates on `profile->emailVerified`, and the
  takeover is fully closed by FR-1 without expanding the resolver signature.
- Changes to GitHub, Google, or Twitter adapters.
