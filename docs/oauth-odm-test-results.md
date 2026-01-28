# OAuth ODM Migration - Test Results

**Date**: 2026-01-27 (Updated)
**Branch**: 237-migration-switch-database-from-mariadb-to-mongodb-doctrine-orm-to-odm

## Summary

✅ **Migration Complete** - OAuth2 fully operational on MongoDB ODM with League bundle direct persistence

## Architecture Implementation

### Direct League Model Persistence ✅

The migration uses **League bundle models directly** with Doctrine ODM XML mappings (no DTO layer):

- **What**: OAuth models (`Client`, `AccessToken`, `AuthorizationCode`, `RefreshToken`) are persisted directly
- **Why**: League bundle already provides DI-friendly managers; mapping models directly avoids unnecessary DTO conversion
- **How**: ODM XML mappings in `config/doctrine/OAuth/` + custom ODM types for value objects

### Files Changed

**Added**:

- ✅ ODM XML mappings for League models:
  - `AbstractClient.mongodb.xml` (mapped-superclass)
  - `Client.mongodb.xml`
  - `AccessToken.mongodb.xml`
  - `AuthorizationCode.mongodb.xml`
  - `RefreshToken.mongodb.xml`
- ✅ Custom Doctrine ODM types in `src/OAuth/Infrastructure/DoctrineType/`:
  - `oauth2_scope` - Serializes `Scope` value object to string
  - `oauth2_grant` - Serializes `Grant` value object to string
  - `oauth2_redirect_uri` - Serializes `RedirectUri` value object to string
- ✅ Unit tests for ODM types and managers in `tests/Unit/OAuth/Infrastructure/`
- ✅ 3 new Behat scenarios covering critical PKCE flows

**Updated**:

- ✅ OAuth managers to persist League models directly:
  - `AccessTokenManager` - includes `OAUTH_PERSIST_ACCESS_TOKEN` flag support
  - `AuthorizationCodeManager`
  - `RefreshTokenManager`
  - `ClientManager`
- ✅ `CredentialsRevoker` - queries using ODM references

**Removed**:

- ❌ `src/OAuth/Domain/Entity/*Document.php` (old DTO entities)
- ❌ Old XML mappings for DTO entities

### Relationships (ODM References)

✅ **Correctly implemented with `reference-one`**:

- `AccessToken.client` → `Client` (replaces old `clientIdentifier` string)
- `AuthorizationCode.client` → `Client` (replaces old `clientIdentifier` string)
- `RefreshToken.accessToken` → `AccessToken` (replaces old `accessTokenIdentifier` string)

**Key Benefit**: ODM handles reference resolution automatically; no manual identifier lookups needed

### Value Objects

✅ **Custom ODM types handle League value objects**:

- `Scope`, `Grant`, `RedirectUri` are serialized to strings for MongoDB storage
- Types registered in `config/packages/doctrine_mongodb.yaml`
- Bidirectional conversion transparent to application logic

## Test Results

### Unit Tests (1000 tests)

```
PHPUnit 10.5.60 by Sebastian Bergmann and contributors.
Runtime: PHP 8.3.17

Tests: 1000, Assertions: 2508
✅ ALL PASSED (0 errors, 0 failures)
Time: 00:01.328s, Memory: 56.00 MB
```

**Result**: ✅ **100% Pass Rate** (1000/1000)

**Coverage includes**:

- OAuth managers (AccessToken, AuthorizationCode, RefreshToken, Client)
- OAuth ODM types (Scope, Grant, RedirectUri)
- Value object serialization/deserialization
- Manager clearExpired() methods with correct return values

### Integration Tests (30 tests)

```
PHPUnit 10.5.60 by Sebastian Bergmann and contributors.
Runtime: PHP 8.3.17

Tests: 30, Assertions: 49
✅ ALL PASSED
Time: 00:02.039s, Memory: 60.50 MB
```

**Result**: ✅ **100% Pass Rate** (30/30)

### End-to-End Tests (Behat)

#### OAuth Feature Tests (24 scenarios)

```
24 scenarios (24 passed)
124 steps (124 passed)
Time: 00:08.47s, Memory: 54.32 MB
```

**Result**: ✅ **100% Pass Rate** (24/24 scenarios)

**Coverage includes**:

- All OAuth 2.0 grant types (client_credentials, authorization_code, password, refresh_token, implicit)
- PKCE flows (S256 method, verifier validation, mismatch rejection)
- Authorization code reuse prevention
- Invalid credentials, invalid grants, missing parameters
- Scope validation, redirect URI validation
- Public client PKCE enforcement

**New P0 scenarios added**:

1. ✅ Public client PKCE S256 authorization code flow with valid code verifier
2. ✅ Authorization code reuse is prevented
3. ✅ PKCE code verifier mismatch is rejected

#### Full Behat Suite (153 scenarios)

```
153 scenarios (153 passed)
733 steps (733 passed)
Time: 00:19.50s, Memory: 86.49 MB
```

**Result**: ✅ **100% Pass Rate** (153/153 scenarios)

**Coverage includes**:

- OAuth flows (24 scenarios)
- User operations (REST + GraphQL)
- Localization
- Health checks (cache, database, message broker failure scenarios)

**Note**: Log entries showing exceptions are **expected** - they're from intentional error scenario tests verifying correct error handling.

## OAuth Grant Types Tested

| Grant Type             | Status  | Scenarios | Notes                                                        |
| ---------------------- | ------- | --------- | ------------------------------------------------------------ |
| **client_credentials** | ✅ Pass | 2         | Success + invalid credentials                                |
| **authorization_code** | ✅ Pass | 8         | Success, PKCE (S256), code reuse prevention, invalid params  |
| **password**           | ✅ Pass | 4         | Success, invalid credentials, missing password, refresh flow |
| **refresh_token**      | ✅ Pass | 2         | Success + invalid token                                      |
| **implicit**           | ✅ Pass | 1         | Deprecated but functional                                    |

**PKCE Coverage**:

- ✅ Public client enforcement (requires code_challenge)
- ✅ S256 method validation
- ✅ Code verifier matching
- ✅ Invalid challenge/method rejection
- ✅ Complete authorize → token exchange flow

**Security Coverage**:

- ✅ Authorization code reuse prevention (single-use codes)
- ✅ PKCE verifier mismatch rejection
- ✅ Invalid client/credentials rejection
- ✅ Scope validation
- ✅ Redirect URI validation
- ✅ User authorization denial

## Performance

| Test Suite        | Duration | Tests/Scenarios | Throughput        |
| ----------------- | -------- | --------------- | ----------------- |
| Unit tests        | 1.328s   | 1000 tests      | 753 tests/sec     |
| Integration tests | 2.039s   | 30 tests        | 14.7 tests/sec    |
| OAuth E2E tests   | 8.47s    | 24 scenarios    | 2.8 scenarios/sec |
| Full E2E suite    | 19.50s   | 153 scenarios   | 7.8 scenarios/sec |

**Assessment**: ✅ Excellent performance - unit tests <2s, full E2E <20s

## Verification Checks

### ✅ ODM Mappings

```bash
$ docker compose exec php bin/console doctrine:mongodb:mapping:info
Found 9 documents mapped:
- App\User\Domain\Entity\User [OK]
- App\User\Domain\Entity\PasswordResetToken [OK]
- League\Bundle\OAuth2ServerBundle\Model\AbstractClient [mapped-superclass]
- League\Bundle\OAuth2ServerBundle\Model\Client [OK]
- League\Bundle\OAuth2ServerBundle\Model\AccessToken [OK]
- League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode [OK]
- League\Bundle\OAuth2ServerBundle\Model\RefreshToken [OK]
```

### ✅ OAuth Services

OAuth managers properly registered with Symfony DI:

- `AccessTokenManagerInterface` → `AccessTokenManager`
- `AuthorizationCodeManagerInterface` → `AuthorizationCodeManager`
- `RefreshTokenManagerInterface` → `RefreshTokenManager`
- `ClientManagerInterface` → `ClientManager`

### ✅ MongoDB Collections

- `oauth2_clients` - OAuth clients (indexed on `active`)
- `oauth2_access_tokens` - Access tokens with client references
- `oauth2_authorization_codes` - Authorization codes with client references
- `oauth2_refresh_tokens` - Refresh tokens with access token references

**Index Strategy**:

- Client `identifier` (unique)
- Client `active` (performance)
- Token expiry fields (cleanup queries)

## Data Migration Strategy

**Decision**: ✅ **Wipe + Reseed** (documented in `docs/oauth-coverage-matrix.md`)

**Rationale**:

- Development/test environment
- OAuth seeders already use new League models
- All tests pass with fresh fixtures
- Lower risk than migration command

**Reset Process**:

```bash
# Test environment
make setup-test-db
docker compose exec -e APP_ENV=test php bin/console app:seed-schemathesis-data

# Dev environment
docker compose exec php php bin/console doctrine:mongodb:schema:drop
docker compose exec php php bin/console doctrine:mongodb:schema:create
```

## Coverage Matrix

See `docs/oauth-coverage-matrix.md` for comprehensive coverage analysis including:

- Endpoint coverage (token, authorize)
- Grant type coverage summary
- PKCE coverage details
- Token lifecycle coverage
- Edge cases & security scenarios
- Missing coverage (P1/P2 items for future work)

## Conclusion

**Status**: ✅ **COMPLETE AND VERIFIED**

The OAuth2 ODM migration is fully functional with production-quality coverage:

✅ **Architecture**: Direct League model persistence with ODM (no DTO layer)
✅ **Unit Tests**: 1000/1000 passing (100%)
✅ **Integration Tests**: 30/30 passing (100%)
✅ **OAuth E2E**: 24/24 scenarios passing (100%)
✅ **Full E2E**: 153/153 scenarios passing (100%)
✅ **PKCE**: Complete S256 flow + security validation
✅ **Security**: Code reuse prevention, verifier validation, credential checks
✅ **Performance**: <2s unit, <20s full E2E
✅ **Data Strategy**: Wipe+reseed documented and tested

**Critical P0 Gaps Addressed**:

1. ✅ PKCE S256 complete success flow
2. ✅ Authorization code reuse prevention
3. ✅ PKCE code verifier mismatch validation

**Key Fixes Applied**:

- `clearExpired()` return values use `getDeletedCount()` correctly
- `oauth2_clients` collection mapping with `active` index
- Health-check reflection property made accessible for PHP 8.3

## Next Steps (Optional Enhancements)

**P1 - Important**:

- Authorization code expiry enforcement test
- Refresh token expiry enforcement test
- Access token expiry enforcement test

**P2 - Nice to Have**:

- Token revocation scenarios
- PKCE plain method validation
- State parameter CSRF protection

**Maintenance**:

- Mutation testing (Infection) for OAuth managers
- Consider integration tests for ODM reference queries
- Production migration command if deploying to existing environment
