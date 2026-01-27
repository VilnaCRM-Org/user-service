# OAuth Coverage Matrix

## Overview

This matrix documents all OAuth 2.0 endpoints, flows, and test coverage for the user-service. It maps Behat scenarios and Schemathesis validation to specific OAuth operations and edge cases.

**Test Coverage Tools:**
- **Behat**: End-to-end functional tests in `features/oauth.feature`
- **Schemathesis**: OpenAPI spec validation (`make schemathesis-validate`)
- **PHPUnit**: Unit and integration tests for OAuth managers and types

---

## Endpoint Coverage

### POST /api/oauth/token

| Flow / Case | Status | Covered By | Scenario/Test | Priority |
|-------------|--------|------------|---------------|----------|
| **client_credentials grant - success** | ✅ | Behat | "Obtaining access token with client-credentials grant" | P0 |
| **client_credentials grant - invalid credentials** | ✅ | Behat | "Obtaining access token with invalid credentials" | P0 |
| **authorization_code grant - success** | ✅ | Behat | "Obtaining access token with authorization-code grant" | P0 |
| **authorization_code grant - invalid code** | ✅ | Behat | "Obtaining access token with invalid authorization code" | P0 |
| **password grant - success** | ✅ | Behat | "Obtaining access token with password grant" | P0 |
| **password grant - invalid user credentials** | ✅ | Behat | "Obtaining access token with invalid user credentials" | P0 |
| **password grant - missing password** | ✅ | Behat | "Obtaining access token with missing password" | P1 |
| **refresh_token grant - success** | ✅ | Behat | "Obtaining access token with refresh token grant" | P0 |
| **refresh_token grant - invalid token** | ✅ | Behat | "Obtaining access token with invalid refresh token" | P1 |
| **invalid grant type** | ✅ | Behat | "Obtaining access token with invalid grant" | P1 |
| **missing grant_type parameter** | ✅ | Behat | "Obtaining access token without grant type" | P1 |
| **OpenAPI spec validation** | ✅ | Schemathesis | Automated OpenAPI examples | P1 |
| **authorization_code + PKCE - success** | ❌ | *MISSING* | Public client PKCE flow end-to-end | **P0** |
| **authorization_code reuse - rejected** | ❌ | *MISSING* | Auth code can only be used once | **P0** |
| **expired authorization_code** | ❌ | *MISSING* | Auth code expires after timeout | P1 |
| **expired refresh_token** | ❌ | *MISSING* | Refresh token expiry validation | P1 |
| **revoked access_token usage** | ❌ | *MISSING* | Using revoked token returns 401 | P2 |

### GET /api/oauth/authorize

| Flow / Case | Status | Covered By | Scenario/Test | Priority |
|-------------|--------|------------|---------------|----------|
| **authorization_code - success** | ✅ | Behat | "Obtaining access token with authorization-code grant" (includes authorize step) | P0 |
| **authorization_code - no authentication** | ✅ | Behat | "Failing to obtain authorization code without authentication" | P0 |
| **authorization_code - denied by user** | ✅ | Behat | "Denying authorization request" | P0 |
| **implicit grant - success** | ✅ | Behat | "Obtaining access token with implicit grant" | P0 |
| **invalid client_id** | ✅ | Behat | "Failing to obtain authorization code with invalid client" | P0 |
| **invalid redirect_uri** | ✅ | Behat | "Failing to obtain authorization code with invalid redirect uri" | P0 |
| **invalid scope** | ✅ | Behat | "Failing to obtain authorization code with invalid scope" | P1 |
| **unsupported response_type** | ✅ | Behat | "Failing to obtain authorization code with unsupported response type" | P1 |
| **public client without code_challenge (PKCE)** | ✅ | Behat | "Failing to obtain authorization code for public client without code challenge" | P0 |
| **invalid code_challenge** | ✅ | Behat | "Failing to obtain authorization code with invalid code challenge" | P1 |
| **invalid code_challenge_method** | ✅ | Behat | "Failing to obtain authorization code with invalid code challenge method" | P1 |
| **OpenAPI spec validation** | ✅ | Schemathesis | Automated OpenAPI examples | P1 |
| **PKCE plain method - success** | ❌ | *MISSING* | Plain PKCE method validation | P2 |
| **PKCE S256 method - success** | ❌ | *MISSING* | SHA256 PKCE method validation | **P0** |
| **missing redirect_uri parameter** | ⚠️ | Partial | Covered by invalid credentials error, but not explicit | P1 |
| **state parameter roundtrip** | ❌ | *MISSING* | CSRF protection via state parameter | P2 |

---

## Grant Type Coverage Summary

| Grant Type | Success Flow | Failure Paths | PKCE Support | Token Refresh | Revocation |
|------------|--------------|---------------|--------------|---------------|------------|
| **client_credentials** | ✅ | ✅ (invalid creds, invalid grant) | N/A | ❌ (no refresh) | ⚠️ (no scenario) |
| **authorization_code** | ✅ | ✅ (invalid code, no auth, denied) | ⚠️ (partial) | ✅ | ⚠️ (no scenario) |
| **password** | ✅ | ✅ (invalid creds, missing pwd) | N/A | ✅ | ⚠️ (no scenario) |
| **refresh_token** | ✅ | ✅ (invalid token) | N/A | N/A | ⚠️ (no scenario) |
| **implicit** | ✅ | ✅ (invalid client, redirect) | N/A | ❌ (no refresh) | ⚠️ (no scenario) |

---

## PKCE (Proof Key for Code Exchange) Coverage

| Case | Status | Covered By | Priority |
|------|--------|------------|----------|
| Public client without PKCE - rejected | ✅ | Behat | P0 |
| Invalid code_challenge format | ✅ | Behat | P1 |
| Invalid code_challenge_method | ✅ | Behat | P1 |
| **PKCE S256 success (authorize + token)** | ❌ | *MISSING* | **P0** |
| **PKCE plain method success** | ❌ | *MISSING* | P2 |
| **code_verifier mismatch - rejected** | ❌ | *MISSING* | **P0** |
| **missing code_verifier on token request** | ❌ | *MISSING* | P1 |

---

## Token Lifecycle Coverage

| Operation | Status | Covered By | Priority |
|-----------|--------|------------|----------|
| Token issuance (all grant types) | ✅ | Behat | P0 |
| Token refresh (password, auth_code grants) | ✅ | Behat | P0 |
| **Token expiry enforcement** | ❌ | *MISSING* | P1 |
| **Token revocation** | ❌ | *MISSING* | P2 |
| **Refresh token expiry** | ❌ | *MISSING* | P1 |
| **Authorization code expiry** | ❌ | *MISSING* | P1 |
| **Authorization code reuse prevention** | ❌ | *MISSING* | **P0** |

---

## Edge Cases & Security

| Case | Status | Covered By | Priority |
|------|--------|------------|----------|
| Invalid client credentials | ✅ | Behat | P0 |
| Missing required parameters | ✅ | Behat (grant_type, password) | P1 |
| Unsupported grant type | ✅ | Behat | P1 |
| Invalid redirect_uri | ✅ | Behat | P0 |
| Invalid scope | ✅ | Behat | P1 |
| Public client without PKCE | ✅ | Behat | P0 |
| User denies authorization | ✅ | Behat | P0 |
| Unauthenticated authorization request | ✅ | Behat | P0 |
| **CSRF protection (state parameter)** | ❌ | *MISSING* | P2 |
| **Authorization code replay attack** | ❌ | *MISSING* | **P0** |
| **Token binding / client validation** | ⚠️ | Implicit in grant logic | P1 |

---

## Schemathesis Coverage

Schemathesis validates:
- Request/response schema compliance with OpenAPI spec
- Required parameters enforcement
- Data type validation
- Response status codes
- Example payloads from OpenAPI spec

**Fixtures**: Seeded via `app:seed-schemathesis-data` command

**Note**: Schemathesis provides **schema-level validation** but does NOT cover:
- Business logic edge cases (e.g., auth code reuse)
- Multi-step flows (e.g., authorize → token exchange)
- Security scenarios (e.g., CSRF, replay attacks)

These must be covered by Behat scenarios.

---

## Missing Coverage (Priority Order)

### P0 - Critical (Must Have)

1. **PKCE authorization_code flow - complete success path**
   - Description: Public client requests auth code with PKCE S256, exchanges with code_verifier
   - Endpoints: GET /authorize → POST /token
   - Test approach: Behat scenario with real code_challenge generation and verification
   - Input changes needed: Add `code_verifier` field to `AuthorizationCodeGrantInput`

2. **Authorization code reuse prevention**
   - Description: Using the same auth code twice should fail with invalid_grant
   - Endpoints: POST /token (authorization_code grant)
   - Test approach: Behat scenario that exchanges code twice, second attempt fails
   - Implementation: League bundle likely handles this; verify behavior

3. **PKCE code_verifier mismatch**
   - Description: Auth code issued with code_challenge, but wrong verifier provided
   - Endpoints: POST /token (authorization_code grant)
   - Test approach: Behat scenario with mismatched verifier
   - Input changes needed: Use `code_verifier` in `AuthorizationCodeGrantInput`

### P1 - Important (Should Have)

4. **Authorization code expiry**
   - Description: Auth codes expire after configured TTL
   - Endpoints: POST /token (authorization_code grant)
   - Test approach: May require manual sleep or time mocking

5. **Refresh token expiry**
   - Description: Refresh tokens expire after configured TTL
   - Endpoints: POST /token (refresh_token grant)
   - Test approach: May require time mocking or config adjustment

6. **Access token expiry enforcement**
   - Description: Expired access tokens should be rejected by protected resources
   - Endpoints: Protected API endpoints
   - Test approach: Integration test with time mocking

7. **Missing redirect_uri on authorize request**
   - Description: Explicit test for missing required parameter
   - Endpoints: GET /authorize
   - Test approach: Simple Behat scenario, likely fails with invalid_request

### P2 - Nice to Have

8. **Token revocation**
   - Description: Revoked tokens should not be usable
   - Endpoints: Protected API endpoints + revocation logic
   - Test approach: Integration test with `CredentialsRevoker`

9. **PKCE plain method**
   - Description: PKCE with plain (not S256) method
   - Endpoints: GET /authorize → POST /token
   - Test approach: Similar to S256 but with plain verifier

10. **State parameter CSRF protection**
    - Description: State param sent on authorize, returned in redirect
    - Endpoints: GET /authorize
    - Test approach: Behat scenario validating state roundtrip

---

## Data Migration Risk

**CRITICAL**: Old OAuth documents used `clientIdentifier`/`accessTokenIdentifier` string fields. New ODM mappings use references (`client` → `Client`, `accessToken` → `AccessToken`).

**Impact**: Existing OAuth data will NOT hydrate unless migrated.

**Decision Required**: Choose one:
- **Option A**: Migration command to convert legacy fields to references
- **Option B**: Wipe oauth2_* collections and reseed

**Related Config**:
- `OAUTH_PERSIST_ACCESS_TOKEN` flag (if false, refresh token queries by user/client will fail)

---

## Recommendations

1. **Implement P0 missing scenarios** before considering migration complete
2. **Test PKCE S256 flow end-to-end** (highest security value)
3. **Verify authorization code reuse prevention** (critical security requirement)
4. **Choose data migration strategy** and document in this file
5. **Run full Behat + Schemathesis** before finalizing
6. **Consider mutation testing** for OAuth managers (Infection)

---

## Test Execution

```bash
# Run OAuth Behat scenarios only
docker compose exec -e APP_ENV=test php ./vendor/bin/behat features/oauth.feature

# Run specific scenario
docker compose exec -e APP_ENV=test php ./vendor/bin/behat --name "Obtaining access token with client-credentials"

# Run Schemathesis validation
make schemathesis-validate

# Run OAuth manager unit tests
docker compose exec -e APP_ENV=test php ./vendor/bin/phpunit --filter OAuth

# Run all tests
make all-tests
```

---

**Last Updated**: 2026-01-27
**Status**: Matrix complete, 3 P0 gaps identified, awaiting implementation
