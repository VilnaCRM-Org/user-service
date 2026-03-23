Feature: Token Refresh and Rotation
  In order to maintain my session without re-authenticating
  As an authenticated client
  I want to exchange a refresh token for a new JWT

  # Story 3.1: Refresh JWT using refresh token (FR-04, NFR-02)

  Scenario: Refresh token with valid refresh token
    Given user with email "refresh@test.com" and password "passWORD1" exists
    And user "refresh@test.com" has signed in and received tokens
    And submitting the refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 200
    And the response should contain "access_token"
    And the response should contain "refresh_token"
    And the new access token should be a valid JWT
    And the new refresh token should differ from the original

  Scenario: Refresh token with expired refresh token
    Given user with email "refresh-exp@test.com" and password "passWORD1" exists
    And user "refresh-exp@test.com" has an expired refresh token
    And submitting the expired refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 401

  Scenario: Refresh token with revoked refresh token
    Given user with email "refresh-rev@test.com" and password "passWORD1" exists
    And user "refresh-rev@test.com" has a revoked refresh token
    And submitting the revoked refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 401

  Scenario: Refresh token with invalid token string
    Given submitting refresh token "invalid-token-string"
    When POST request is send to "/api/token"
    Then the response status code should be 401

  Scenario: Refresh token with empty body
    Given sending empty body
    When POST request is send to "/api/token"
    Then the response status code should be 422
    And violation should be "This value should not be blank."

  # Story 3.2: Refresh token rotation grace window (FR-05, FR-06, NFR-34)

  Scenario: Normal token rotation marks old token as rotated
    Given user with email "rotate@test.com" and password "passWORD1" exists
    And user "rotate@test.com" has signed in and received tokens
    And submitting the refresh token to exchange
    And POST request is send to "/api/token"
    And the response status code should be 200
    Then the old refresh token should be marked as rotated

  Scenario: Grace window allows single reuse of rotated token
    Given user with email "grace@test.com" and password "passWORD1" exists
    And user "grace@test.com" has signed in and received tokens
    And the refresh token has been rotated within the grace window
    And submitting the rotated refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 200
    And the response should contain "access_token"
    And the response should contain "refresh_token"

  Scenario: Second reuse of rotated token within grace window triggers theft detection
    Given user with email "grace-theft@test.com" and password "passWORD1" exists
    And user "grace-theft@test.com" has signed in and received tokens
    And the refresh token has been rotated and grace reuse has been consumed
    And submitting the rotated refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 401
    And the entire session should be revoked
    And a CRITICAL-level audit log should be emitted for refresh token theft

  Scenario: Reuse of rotated token after grace window triggers theft detection
    Given user with email "grace-expired@test.com" and password "passWORD1" exists
    And user "grace-expired@test.com" has signed in and received tokens
    And the refresh token has been rotated and the grace window has expired
    And submitting the rotated refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 401
    And the entire session should be revoked
    And a CRITICAL-level audit log should be emitted for refresh token theft

  Scenario: Token refresh preserves JWT claims
    Given user with email "refresh-claims@test.com" and password "passWORD1" exists
    And user "refresh-claims@test.com" has signed in and received tokens
    And submitting the refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 200
    And the new access token JWT should contain claim "sub"
    And the new access token JWT should contain claim "iss" with value "vilnacrm-user-service"
    And the new access token JWT should contain claim "aud" with value "vilnacrm-api"
    And the new access token JWT should contain claim "sid"

  # Additional token refresh edge cases (FR-04, FR-05, FR-06, NFR-02, NFR-34)

  Scenario: Token refresh returns new jti but same sid
    Given user with email "refresh-jti@test.com" and password "passWORD1" exists
    And user "refresh-jti@test.com" has signed in and received tokens
    And I store the original JWT claims
    And submitting the refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 200
    And the new access token JWT "jti" should differ from the original
    And the new access token JWT "sid" should match the original

  Scenario: Token refresh does not set session cookie
    Given user with email "refresh-nocookie@test.com" and password "passWORD1" exists
    And user "refresh-nocookie@test.com" has signed in and received tokens
    And submitting the refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 200
    And the response should not have a Set-Cookie header for "__Host-auth_token"

  Scenario: Refresh token after logout fails
    Given user with email "refresh-logout@test.com" and password "passWORD1" exists
    And user "refresh-logout@test.com" has signed in and received tokens
    And I store the refresh token
    And I am authenticated with the access token
    And POST request is send to "/api/signout"
    And the response status code should be 204
    And submitting the stored refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 401

  Scenario: Refresh token after sign-out-all fails
    Given user with email "refresh-signout-all@test.com" and password "passWORD1" exists
    And user "refresh-signout-all@test.com" has signed in and received tokens
    And I store the refresh token
    And I am authenticated with the access token
    And POST request is send to "/api/signout/all"
    And the response status code should be 204
    And submitting the stored refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 401

  Scenario: Refresh token from other session fails after password change
    Given user with id "8be90127-9840-4235-a6da-39b8debfb260" and password "passWORD1" exists
    And user "8be90127-9840-4235-a6da-39b8debfb260" has 2 active sessions with refresh tokens
    And I am authenticated on session 1 for user "8be90127-9840-4235-a6da-39b8debfb260"
    And I store session 2's refresh token
    And updating user with email "pwchange-ref@test.com", initials "name", oldPassword "passWORD1", newPassword "passWORD2"
    And PATCH request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb260"
    And the response status code should be 200
    And submitting session 2's stored refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 401

  Scenario: Token refresh preserves roles claim
    Given user with email "refresh-roles@test.com" and password "passWORD1" exists
    And user "refresh-roles@test.com" has signed in and received tokens
    And submitting the refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 200
    And the new access token JWT should contain claim "roles"
    And the new access token JWT "roles" should contain "ROLE_USER"

  Scenario: Token refresh with missing refresh_token field
    Given sending empty body
    When POST request is send to "/api/token"
    Then the response status code should be 422
    And violation should be "This value should not be blank."

  Scenario: Token refresh response is application/json
    Given user with email "refresh-content@test.com" and password "passWORD1" exists
    And user "refresh-content@test.com" has signed in and received tokens
    And submitting the refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 200
    And the response should have header "Content-Type" containing "application/json"

  Scenario: Token refresh new access token has fresh expiration
    Given user with email "refresh-exp@test.com" and password "passWORD1" exists
    And user "refresh-exp@test.com" has signed in and received tokens
    And submitting the refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 200
    And the new access token JWT "exp" should be approximately 15 minutes after "iat"

  # Refresh token family tracking (ADR-05, FR-05, FR-06)

  Scenario: All tokens in rotation chain share the same session ID
    Given user with email "refresh-family@test.com" and password "passWORD1" exists
    And user "refresh-family@test.com" has signed in and received tokens
    And I store the original JWT "sid" claim
    And submitting the refresh token to exchange
    And POST request is send to "/api/token"
    And the response status code should be 200
    And I store the new refresh token
    And submitting the new refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 200
    And the new access token JWT "sid" should match the original

  Scenario: Theft detection revokes entire token family
    Given user with email "refresh-family-revoke@test.com" and password "passWORD1" exists
    And user "refresh-family-revoke@test.com" has signed in and received tokens
    And the refresh token has been rotated to token B
    And token B has been rotated to token C
    And the original refresh token is submitted (theft attempt)
    When POST request is send to "/api/token"
    Then the response status code should be 401
    And token B should be revoked
    And token C should be revoked
    And the entire session should be revoked

  Scenario: Grace window only applies to the immediately rotated token
    Given user with email "refresh-grace-only@test.com" and password "passWORD1" exists
    And user "refresh-grace-only@test.com" has signed in and received tokens
    And the refresh token has been rotated to token B within the grace window
    And token B has been rotated to token C
    And submitting the original refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 401

  # Concurrent token refresh (NFR-58: Atomic MongoDB operations)

  Scenario: Concurrent token refresh with same token within grace window
    Given user with email "refresh-concurrent@test.com" and password "passWORD1" exists
    And user "refresh-concurrent@test.com" has signed in and received tokens
    And submitting the refresh token to exchange
    And POST request is send to "/api/token"
    And the response status code should be 200
    And submitting the same original refresh token again within the grace window
    When POST request is send to "/api/token"
    Then the response status code should be 200

  # Refresh token after 2FA session

  Scenario: Refresh token from 2FA sign-in session works
    Given user with email "refresh-2fa@test.com" and password "passWORD1" exists
    And user with email "refresh-2fa@test.com" has 2FA enabled
    And signing in with email "refresh-2fa@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and a valid TOTP code
    And POST request is send to "/api/signin/2fa"
    And the response status code should be 200
    And I store the refresh token from the response
    And submitting the stored refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 200
    And the response should contain "access_token"
    And the response should contain "refresh_token"

  # Refresh token response validation

  Scenario: Token refresh error response is RFC 7807
    Given submitting refresh token "invalid-token-rfc"
    When POST request is send to "/api/token"
    Then the response status code should be 401
    And the response should be RFC 7807 problem+json

  Scenario: Token refresh error does not expose internal details
    Given submitting refresh token "invalid-token-details"
    When POST request is send to "/api/token"
    Then the response status code should be 401
    And the response should not contain "tokenHash"
    And the response should not contain "sessionId"
    And the response should not contain "MongoDB"
