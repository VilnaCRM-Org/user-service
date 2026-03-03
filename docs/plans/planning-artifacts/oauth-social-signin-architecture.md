---
stepsCompleted: []
workflowType: 'greenfield-fullstack'
inputDocuments: ['oauth-social-signin-prd.md']
version: 2
date: 2026-03-03
authors: [Winston (Architect)]
---

# Architecture Decision Record: OAuth Social Sign-In / Sign-Up

## 1. Context

This ADR defines how social OAuth (GitHub + Google) fits into the DDD/CQRS architecture with security-first behavior.

Prerequisite: the target branch must contain the baseline sign-in/session/2FA components referenced here (`SessionIssuer`, `PendingTwoFactorRepository`, `CompleteTwoFactorCommandHandler`). If not, this work is blocked until that baseline is merged.

---

## 2. Architecture Overview

```
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
  |- POST /auth/2fa/complete (unchanged)
```

---

## 3. Bounded Context: OAuth

`src/OAuth/` owns social identity mapping. It does not own the User aggregate.

```
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
"league/oauth2-google": "^1.0"
```

No Symfony OAuth client bundle is required.

### 4.2 SocialIdentity Model and Indexes

`SocialIdentity` fields:

- `id` (ULID)
- `provider` (`github` | `google`)
- `providerId` (opaque string)
- `userId` (UUID string)
- `createdAt`, `lastUsedAt`

MongoDB indexes:

- unique `(provider, provider_id)`
- unique `(user_id, provider)`
- non-unique `(user_id)`

### 4.3 Provider Interface Includes PKCE

```php
interface OAuthProviderInterface
{
    public function getAuthorizationUrl(string $state, string $codeChallenge): string;
    public function exchangeCode(string $code, string $codeVerifier): string;
    public function fetchProfile(string $accessToken): OAuthUserProfile;
    public function getProvider(): OAuthProvider;
}
```

### 4.4 State + Flow Binding Storage

Redis key pattern: `oauth_state:{state}`

Value payload:

- provider
- codeVerifier
- flowBindingHash
- redirectUri
- createdAt

Validation is atomic and one-time (consume on read). Provider mismatch or flow mismatch is rejected.

### 4.5 User Resolution Policy

Resolution order:

1. Find `SocialIdentity(provider, providerId)` -> return linked user
2. If not found and local user exists by email -> reject (`SocialIdentityNotLinkedException`, HTTP 409)
3. If no local user -> create user + social identity

No auto-linking by email in this phase.

### 4.6 OAuth User Password Strategy

Do not store empty or plaintext sentinel values.

For newly provisioned OAuth users:

- generate random high-entropy secret in application layer
- hash with `PasswordHasherInterface`
- persist only hashed value in `User.password`

### 4.7 2FA Reuse

After successful user resolution:

- if user has local 2FA enabled: create `PendingTwoFactor`
- otherwise: issue session directly

`CompleteTwoFactorCommandHandler` remains unchanged.

### 4.8 HTTP Contract and Routes

Routes:

- `GET /api/auth/social/{provider}`
- `GET /api/auth/social/{provider}/callback`

Errors are RFC 7807 with stable `error_code` values.

### 4.9 Outbound HTTP Resilience

Provider adapters must enforce:

- explicit connect/read timeouts
- bounded retries only for transient failures
- normalized `OAuthProviderException` mapping

---

## 5. MongoDB Schema

### SocialIdentity Collection

```
Collection: social_identities
{
  _id:          ULID,
  provider:     "github" | "google",
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

---

## 6. Configuration (Environment Variables)

```env
OAUTH_GITHUB_CLIENT_ID=
OAUTH_GITHUB_CLIENT_SECRET=
OAUTH_GITHUB_REDIRECT_URI=https://your-domain/api/auth/social/github/callback

OAUTH_GOOGLE_CLIENT_ID=
OAUTH_GOOGLE_CLIENT_SECRET=
OAUTH_GOOGLE_REDIRECT_URI=https://your-domain/api/auth/social/google/callback

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

| Risk                                 | Likelihood | Mitigation                                                      |
| ------------------------------------ | ---------- | --------------------------------------------------------------- |
| Provider outage during callback      | Medium     | Timeout + bounded retries + map to `provider_unavailable` (503) |
| Replay/double callback submission    | Low        | Atomic one-time `validateAndConsume` in Redis                   |
| Provider route/state mix-up          | Low        | Validate route provider equals stored provider                  |
| Email ownership drift takeover       | Medium     | No auto-linking by email in callback                            |
| Sensitive values in logs             | Medium     | Mandatory redaction of code/state/token/cookies                 |
| Duplicate identity writes under race | Low        | Unique indexes + idempotent duplicate-key handling              |
