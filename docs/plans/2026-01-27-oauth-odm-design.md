# OAuth ODM Persistence Design

**Goal:** Replace OAuth bundle ORM persistence with ODM-backed custom persistence while keeping `league/oauth2-server-bundle` unchanged.

**Architecture:** Use the bundle’s custom persistence hook (`persistence: custom`) and implement ODM-backed managers for all bundle storage interfaces. Map the bundle’s model classes as MongoDB documents via ODM XML mappings. Remove ORM dependency/config entirely for OAuth.

**Tech Stack:** Symfony 7.2, Doctrine MongoDB ODM, league/oauth2-server-bundle v1.1, league/oauth2-server v9.x, Behat acceptance tests.

## Architecture & Scope (ODM-only)
- Keep `league/oauth2-server-bundle` intact.
- Switch to custom persistence in `config/packages/league_oauth2_server.yaml`.
- Implement ODM-backed services for:
  - `ClientManagerInterface`
  - `AccessTokenManagerInterface`
  - `RefreshTokenManagerInterface`
  - `AuthorizationCodeManagerInterface`
  - `CredentialsRevokerInterface`
- Remove ORM dependency and config (no `doctrine/doctrine-bundle`, no `config/packages/doctrine.yaml`).
- OAuth endpoints remain `/api/oauth/authorize` and `/api/oauth/token`.

## Components & Data Flow
- Bundle controllers → bundle repositories → **ODM managers** (custom persistence).
- Managers use `Doctrine\ODM\MongoDB\DocumentManager` to read/write OAuth data.
- No bundle code changes required; only DI wiring + ODM mappings.

## ODM Data Model & Mappings
Map bundle model classes as ODM documents in `config/doctrine/`:

### Client (`League\Bundle\OAuth2ServerBundle\Model\Client`)
- `identifier` as `<id strategy="NONE">` (unique).
- `name`, `secret` (nullable).
- `redirectUris`, `grants`, `scopes` as array collections of strings.
- `active`, `allowPlainTextPkce` as booleans.

### AccessToken (`Model\AccessToken`)
- `identifier` as id (NONE).
- `expiry` (date), `userIdentifier` (nullable string), `revoked` (bool).
- `scopes` as array of strings.
- `client` as reference-one to Client.
- Indexes on `expiry`, `userIdentifier`, `client`.

### AuthorizationCode (`Model\AuthorizationCode`)
- `identifier` as id (NONE).
- `expiry`, `userIdentifier`, `revoked`.
- `scopes` as array of strings.
- `client` as reference-one to Client.

### RefreshToken (`Model\RefreshToken`)
- `identifier` as id (NONE).
- `expiry`, `revoked`.
- `accessToken` as reference-one to AccessToken (nullable if access tokens aren’t persisted).

**Value objects** (`Grant`, `Scope`, `RedirectUri`) are stored as plain strings; managers convert to/from value objects as needed.

## Error Handling & Compatibility
- Managers follow ORM manager behavior: return `null` for missing entities, no framework exceptions.
- Honor `persist_access_token` (no-op when false).
- `ClientManager::list()` uses ODM `$in`/`$all` filters instead of ORM `LIKE` on string fields.
- Credentials revocation updates tokens by `userIdentifier`/`client` consistently.
- Keep error payloads (`error`, `error_description`) to match existing Behat/Schemathesis expectations.

## Testing Strategy
- Behat: expanded OAuth edge-case scenarios already added under `features/oauth.feature`.
- Schemathesis: rely on existing fixtures; adjust only if examples/seeded data mismatch.
- Run `make behat` once ODM persistence is wired (not before).

## Implementation Outline
1) Switch to `persistence: custom` in `config/packages/league_oauth2_server.yaml`.
2) Add ODM XML mappings for bundle model classes in `config/doctrine/`.
3) Implement ODM manager classes and credentials revoker.
4) Wire services in `config/services.yaml`.
5) Remove ORM dependency and `config/packages/doctrine.yaml`.
6) Validate with `make behat` and Schemathesis as needed.
