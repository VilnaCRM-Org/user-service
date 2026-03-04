---
stepsCompleted: []
workflowType: 'greenfield-fullstack'
inputDocuments: ['oauth-social-signin-prd.md']
version: 3
date: 2026-03-05
authors: [Winston (Architect)]
---

# Architecture Decision Record: OAuth Social Sign-In / Sign-Up

## 1. Context

This ADR defines how social OAuth (GitHub, Google, Facebook, and Twitter/X) fits into the DDD/CQRS architecture with security-first behavior.

**Email policy**: All providers require a verified email for account resolution and creation. Providers that do not supply a verified email are rejected. `provider_email_unavailable` covers absence; `unverified_provider_email` covers unverified presence. Email-optional OAuth is explicitly out of scope for this phase.

**Provider capability model**: Providers differ in PKCE support, email guarantee, and whether a separate profile API call is required. These differences are expressed through a capability interface on each adapter — not through branching in core handlers.

Prerequisite: the target branch must contain the baseline sign-in/session/2FA components referenced here (`SessionIssuer`, `PendingTwoFactorRepository`, `CompleteTwoFactorCommandHandler`). If not, this work is blocked until that baseline is merged.

---

## 2. Architecture Overview

```text
Browser
  |
  |- GET /api/auth/social/{provider}
  |    |- AuthController::initiateOAuth()
  |    |    |- InitiateOAuthCommand -> InitiateOAuthCommandHandler
  |    |         |- OAuthProviderRegistry resolves provider adapter
  |    |         |- generate state + PKCE verifier/challenge + flow binding
  |    |         |- RedisOAuthStateRepository::save(payload, ttl=10m)
  |    |         |- provider.getAuthorizationUrl(state, code_challenge)
  |    |- set Secure/HttpOnly/SameSite=Lax flow cookie
  |    |- 302 redirect to provider
  |
  |- GET /api/auth/social/{provider}/callback?code=&state=
  |    |- AuthController::handleOAuthCallback()
  |    |    |- HandleOAuthCallbackCommand -> HandleOAuthCallbackCommandHandler
  |    |         |- validate required params + flow cookie
  |    |         |- RedisOAuthStateRepository::validateAndConsume(state, provider, flow_binding)
  |    |         |- provider.exchangeCode(code, code_verifier)
  |    |         |- provider.fetchProfile(access_token)
  |    |         |- OAuthUserResolver::resolve(profile, provider)
  |    |              |- SocialIdentityRepository::findByProviderAndProviderId(...)
  |    |              |- [if missing] UserRepository::findByEmail(email)
  |    |              |- [if existing user] throw SocialIdentityNotLinkedException (no auto-link)
  |    |              |- [if missing user] create user + SocialIdentity
  |    |         |- 2FA gate:
  |    |              |- has 2FA: create PendingTwoFactor -> response(twoFactorEnabled=true)
  |    |              |- no 2FA: SessionIssuer::issue() -> response(twoFactorEnabled=false)+cookies
  |
  |- POST /api/auth/2fa/complete (unchanged)
```

---

## 3. Bounded Context: OAuth

`src/OAuth/` owns social identity mapping. It does not own the User aggregate.

```text
src/OAuth/
|- Domain/
|  |- Entity/SocialIdentity.php
|  |- ValueObject/OAuthProvider.php
|  |- ValueObject/OAuthUserProfile.php
|  |- ValueObject/OAuthStatePayload.php
|  |- Repository/SocialIdentityRepositoryInterface.php
|  |- Event/OAuthUserCreatedEvent.php
|  |- Event/OAuthUserSignedInEvent.php
|  |- Exception/InvalidStateException.php
|  |- Exception/ProviderMismatchException.php
|  |- Exception/SocialIdentityNotLinkedException.php
|  |- Exception/UnverifiedProviderEmailException.php
|  |- Exception/OAuthProviderException.php
|- Application/
|  |- Command/InitiateOAuthCommand.php
|  |- Command/HandleOAuthCallbackCommand.php
|  |- CommandHandler/InitiateOAuthCommandHandler.php
|  |- CommandHandler/HandleOAuthCallbackCommandHandler.php
|  |- Provider/OAuthProviderInterface.php
|  |- Provider/OAuthProviderRegistry.php
|  |- Resolver/OAuthUserResolver.php
|- Infrastructure/
   |- Provider/GitHubOAuthProvider.php
   |- Provider/GoogleOAuthProvider.php
   |- Provider/FacebookOAuthProvider.php
   |- Provider/TwitterOAuthProvider.php
   |- Repository/MongoDBSocialIdentityRepository.php
   |- Repository/RedisOAuthStateRepository.php
```

---

## 4. Key Design Decisions

### 4.1 OAuth Client Libraries

Use:

```json
"league/oauth2-client": "^2.7",
"league/oauth2-github": "^3.0",
"league/oauth2-google": "^1.0",
"league/oauth2-facebook": "^2.0",
"league/oauth2-twitter": "^1.0"
```

No Symfony OAuth client bundle is required.

> **Note on Twitter/X**: The `league/oauth2-twitter` package wraps Twitter API v2 with OAuth 2.0 PKCE. Verify the package's maintenance status before pinning; if unmaintained, implement the adapter directly against Twitter API v2 using `league/oauth2-client` base classes.

### 4.2 SocialIdentity Model and Indexes

`SocialIdentity` fields:

- `id` (ULID)
- `provider` (string — validated against allowlist registry; stored as-is in persistence)
- `providerId` (opaque string)
- `userId` (UUID string)
- `createdAt`, `lastUsedAt`

MongoDB indexes:

- unique `(provider, provider_id)`
- unique `(user_id, provider)`
- non-unique `(user_id)`

### 4.3 Provider Interface with Capability Model

Providers differ in PKCE support, email guarantee, and profile fetch mechanics. These differences are declared through capability methods — not branched on in the handler. The handler calls capability methods to decide whether to generate PKCE params and which exceptions to expect.

```php
interface OAuthProviderInterface
{
    public function getProvider(): OAuthProvider;

    /**
     * True if this provider supports PKCE (code_challenge / code_verifier).
     * When false, the handler passes null for PKCE params.
     */
    public function supportsPkce(): bool;

    /**
     * True if this provider ALWAYS returns a verified email in the profile.
     * False means the adapter MUST raise OAuthEmailUnavailableException or
     * UnverifiedProviderEmailException when email is absent or unverified.
     * GitHub and Google: true. Facebook and Twitter/X: false.
     */
    public function emailAlwaysVerified(): bool;

    /**
     * True if fetching the full profile requires a separate provider API call
     * beyond the token endpoint (e.g. Facebook Graph API /me, Twitter v2 Users API).
     */
    public function requiresExtraProfileCall(): bool;

    /**
     * @param string|null $codeChallenge null when supportsPkce() === false
     */
    public function getAuthorizationUrl(string $state, ?string $codeChallenge): string;

    /**
     * @param string|null $codeVerifier null when supportsPkce() === false
     */
    public function exchangeCode(string $code, ?string $codeVerifier): string;

    public function fetchProfile(string $accessToken): OAuthUserProfile;
}
```

Provider capability matrix:

| Provider   | `supportsPkce()` | `emailAlwaysVerified()` | `requiresExtraProfileCall()` |
| ---------- | ---------------- | ----------------------- | ---------------------------- |
| GitHub     | true             | true                    | false                        |
| Google     | true             | true                    | false                        |
| Facebook   | true             | false                   | true (Graph API `/me`)       |
| Twitter/X  | true             | false                   | true (v2 Users API)          |

### 4.4 Provider Registry as Allowlist

`OAuthProvider` is a string-backed value object (not a closed PHP enum). The `OAuthProviderRegistry` is the allowlist authority:

```php
final class OAuthProviderRegistry
{
    /** @param array<string, OAuthProviderInterface> $providers */
    public function __construct(private array $providers) {}

    public function get(string $provider): OAuthProviderInterface
    {
        return $this->providers[$provider]
            ?? throw new UnsupportedProviderException($provider);
    }

    /** @return string[] */
    public function supportedProviders(): array
    {
        return array_keys($this->providers);
    }
}
```

Provider adapters are registered as tagged services in `services.yaml`. Adding a new provider requires only: writing an adapter and registering it — zero domain changes.

### 4.5 State + Flow Binding Storage

Redis key pattern: `oauth_state:{state}`

Value payload (PHP camelCase in domain; serialised to snake_case in Redis):

- `provider`
- `codeVerifier` / `code_verifier`
- `flowBindingHash` / `flow_binding_hash`
- `redirectUri` / `redirect_uri`
- `createdAt` / `created_at`

Validation is atomic and one-time (consume on read) — implemented as a single Lua script or `WATCH`+`MULTI` transaction. Provider mismatch or flow mismatch is rejected.

### 4.6 User Resolution Policy

Resolution order:

1. Find `SocialIdentity(provider, providerId)` -> return linked user
2. If not found and local user exists by email -> reject (`SocialIdentityNotLinkedException`, HTTP 409)
3. If no local user -> create user + social identity

No auto-linking by email in this phase. Email is always present at this point — adapters that do not supply a verified email throw before reaching the resolver.

### 4.7 OAuth User Password Strategy

Do not store empty or plaintext sentinel values.

For newly provisioned OAuth users:

- generate random high-entropy secret in application layer
- hash with `PasswordHasherInterface`
- persist only hashed value in `User.password`

### 4.8 2FA Reuse

After successful user resolution:

- if user has local 2FA enabled: create `PendingTwoFactor`
- otherwise: pass the already-resolved `User` object directly to `SessionIssuer::issue()` — do not re-fetch the user from the repository

`CompleteTwoFactorCommandHandler` remains unchanged.

### 4.9 HTTP Contract and Routes

Routes:

- `GET /api/auth/social/{provider}`
- `GET /api/auth/social/{provider}/callback`

Errors are RFC 7807 (`application/problem+json`) with stable `error_code` values:

| `error_code`                 | HTTP | Trigger                                                                    |
| ---------------------------- | ---- | -------------------------------------------------------------------------- |
| `unsupported_provider`       | 400  | `{provider}` is not in the supported allowlist                             |
| `missing_oauth_parameters`   | 400  | `code`, `state`, or flow-binding cookie absent                             |
| `provider_mismatch`          | 400  | Route provider ≠ stored provider in state                                  |
| `invalid_state`              | 422  | State unknown, already consumed, or binding fail                           |
| `state_expired`              | 422  | State TTL elapsed                                                          |
| `provider_email_unavailable` | 422  | Provider returned no email address (Facebook/Twitter/X without email set)  |
| `unverified_provider_email`  | 422  | Provider returned email but it is not marked as verified                   |
| `social_identity_not_linked` | 409  | Local user exists by email but has no social link                          |
| `provider_unavailable`       | 503  | Provider HTTP call timed out or returned error                             |

### 4.10 Outbound HTTP Resilience

Provider adapters must enforce:

- explicit connect/read timeouts
- bounded retries only for transient failures
- normalized `OAuthProviderException` mapping

---

## 5. MongoDB Schema

### SocialIdentity Collection

```text
Collection: social_identities
{
  _id:          ULID,
  provider:     "github" | "google" | "facebook" | "twitter",
  provider_id:  string,
  user_id:      UUID string,
  created_at:   ISODate,
  last_used_at: ISODate
}
Indexes:
  { provider: 1, provider_id: 1 } UNIQUE
  { user_id: 1, provider: 1 } UNIQUE
  { user_id: 1 }
```

The `provider` field is stored as a plain string. The allowed values are enforced at application layer by the registry — not by a MongoDB enum constraint — so adding future providers requires no schema migration.

---

## 6. Configuration (Environment Variables)

```env
OAUTH_GITHUB_CLIENT_ID=
OAUTH_GITHUB_CLIENT_SECRET=
OAUTH_GITHUB_REDIRECT_URI=https://your-domain/api/auth/social/github/callback

OAUTH_GOOGLE_CLIENT_ID=
OAUTH_GOOGLE_CLIENT_SECRET=
OAUTH_GOOGLE_REDIRECT_URI=https://your-domain/api/auth/social/google/callback

OAUTH_FACEBOOK_CLIENT_ID=
OAUTH_FACEBOOK_CLIENT_SECRET=
OAUTH_FACEBOOK_REDIRECT_URI=https://your-domain/api/auth/social/facebook/callback

OAUTH_TWITTER_CLIENT_ID=
OAUTH_TWITTER_CLIENT_SECRET=
OAUTH_TWITTER_REDIRECT_URI=https://your-domain/api/auth/social/twitter/callback

OAUTH_STATE_TTL_SECONDS=600
OAUTH_PROVIDER_HTTP_CONNECT_TIMEOUT_MS=1500
OAUTH_PROVIDER_HTTP_TIMEOUT_MS=5000
OAUTH_PROVIDER_HTTP_MAX_RETRIES=1
```

---

## 7. Dependency and Integration Notes

- Add OAuth client libraries to `composer.json`.
- Register provider adapters explicitly in `services.yaml`.
- Keep OAuth domain framework-free (Deptrac boundaries preserved).

---

## 8. Deptrac / Quality Impact

- `OAuth` application may depend on `User` domain interfaces only.
- No OAuth infrastructure dependency may leak into domain.
- All project quality gates apply unchanged.

---

## 9. Risks and Mitigations

| Risk                                        | Likelihood | Mitigation                                                                                                           |
| ------------------------------------------- | ---------- | -------------------------------------------------------------------------------------------------------------------- |
| Provider outage during callback             | Medium     | Timeout + bounded retries + map to `provider_unavailable` (503)                                                     |
| Replay/double callback submission           | Low        | Atomic one-time `validateAndConsume` in Redis                                                                        |
| Provider route/state mix-up                 | Low        | Validate route provider equals stored provider                                                                       |
| Email ownership drift takeover              | Medium     | No auto-linking by email in callback                                                                                 |
| Sensitive values in logs                    | Medium     | Mandatory redaction of code/state/token/cookies                                                                      |
| Duplicate identity writes under race        | Low        | Unique indexes + idempotent duplicate-key handling                                                                   |
| Facebook/Twitter profile missing email      | Medium     | Adapters raise `OAuthEmailUnavailableException`; resolver never reached without verified email                       |
| Twitter/X persistent API policy changes     | Medium     | Adapter isolated behind `OAuthProviderInterface`; version-pin league package; monitor Twitter API changelog actively |
| Facebook Graph API token validation quirks  | Low        | Adapter validates token via `/debug_token` before trusting profile; maps failures to `provider_unavailable`          |
