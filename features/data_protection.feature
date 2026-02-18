Feature: Data Protection and Sensitive Data Handling
  In order to protect user data from exposure
  As the system
  I want to ensure no sensitive data leaks in API responses

  # DR-06, DR-07, DR-09: No plaintext secrets in responses

  Scenario: Sign-in response does not contain password
    Given user with email "dp-signin@test.com" and password "passWORD1" exists
    And signing in with email "dp-signin@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the response should not contain "password"
    And the response should not contain "passWORD1"

  Scenario: Sign-in error response does not contain password
    Given user with email "dp-error@test.com" and password "passWORD1" exists
    And signing in with email "dp-error@test.com" and password "wrongPassword1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401
    And the response should not contain "wrongPassword1"
    And the response should not contain "passWORD1"

  Scenario: Token refresh response does not contain refresh token hash
    Given user with email "dp-refresh@test.com" and password "passWORD1" exists
    And user "dp-refresh@test.com" has signed in and received tokens
    And submitting the refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 200
    And the response should not contain "tokenHash"

  Scenario: User GET response does not expose 2FA secret
    Given I am authenticated as user "dp-2fa-get@test.com"
    And user "dp-2fa-get@test.com" has 2FA enabled
    When GET request is send to the current user endpoint
    Then the response should not contain "twoFactorSecret"
    And the response should not contain "secret"

  Scenario: User collection response does not expose 2FA secrets
    Given I am authenticated as user "dp-collection@test.com"
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 200
    And the response should not contain "twoFactorSecret"
    And the response should not contain "password"

  Scenario: 2FA setup response does not contain encrypted secret
    Given I am authenticated as user "dp-2fa-setup@test.com"
    When POST request is send to "/api/users/2fa/setup"
    Then the response status code should be 200
    And the response should contain "secret"
    And the response should not contain "twoFactorSecret"

  Scenario: 2FA confirm response does not expose recovery code hashes
    Given I am authenticated as user "dp-2fa-confirm@test.com"
    And I have completed 2FA setup
    And confirming 2FA with a valid TOTP code
    When POST request is send to "/api/users/2fa/confirm"
    Then the response status code should be 200
    And the response should contain "recovery_codes"
    And the response should not contain "codeHash"

  Scenario: Sign-in error does not reveal whether email exists
    Given user with email "dp-exists@test.com" and password "passWORD1" exists
    And signing in with email "dp-exists@test.com" and password "wrongPassword1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401
    And the error message should be "Invalid credentials"
    And signing in with email "dp-not-exists@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401
    And the error message should be "Invalid credentials"

  Scenario: 2FA sign-in error does not expose TOTP secret
    Given user with email "dp-2fa-error@test.com" and password "passWORD1" exists
    And user with email "dp-2fa-error@test.com" has 2FA enabled
    And signing in with email "dp-2fa-error@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and code "000000"
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 401
    And the response should not contain "twoFactorSecret"
    And the response should not contain "secret"

  Scenario: Recovery code exhaustion response does not expose code hashes
    Given user with email "dp-recovery@test.com" and password "passWORD1" exists
    And user with email "dp-recovery@test.com" has 2FA enabled with recovery codes
    And 6 of 8 recovery codes for user "dp-recovery@test.com" have been used
    And signing in with email "dp-recovery@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and a valid recovery code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 200
    And the response should not contain "codeHash"
    And the response should not contain "usedAt"

  Scenario: Sign-out response does not expose session details
    Given I am authenticated as user "dp-signout@test.com"
    When POST request is send to "/api/signout"
    Then the response status code should be 204

  Scenario: User update response does not expose password hash
    Given I am authenticated as user "dp-update@test.com" with id "8be90127-9840-4235-a6da-39b8debfb292"
    And user with id "8be90127-9840-4235-a6da-39b8debfb292" and password "passWORD1" exists
    And updating user with email "dp-updated@test.com", initials "name", oldPassword "passWORD1", newPassword "passWORD2"
    When PATCH request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb292"
    Then the response status code should be 200
    And the response should not contain "password"
    And the response should not contain "passWORD2"

  # DR-06: No internal IDs or implementation details leaked

  Scenario: Sign-in response does not expose session internal ID
    Given user with email "dp-no-internal@test.com" and password "passWORD1" exists
    And signing in with email "dp-no-internal@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the response should not contain "sessionId"
    And the response should not contain "_id"

  Scenario: Token refresh response does not expose session details
    Given user with email "dp-refresh-session@test.com" and password "passWORD1" exists
    And user "dp-refresh-session@test.com" has signed in and received tokens
    And submitting the refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 200
    And the response should not contain "sessionId"
    And the response should not contain "ipAddress"
    And the response should not contain "userAgent"

  # DR-09: Bcrypt hash not in responses

  Scenario: User list response does not expose bcrypt hash
    Given I am authenticated as user "dp-bcrypt@test.com"
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 200
    And the response should not contain "$2y$"
    And the response should not contain "$2a$"
    And the response should not contain "$2b$"

  # DR-07: No internal MongoDB IDs leaked

  Scenario: User response does not expose MongoDB internal ID
    Given I am authenticated as user "dp-mongo-id@test.com"
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 200
    And the response should not contain "ObjectId"

  # Data protection on GraphQL responses

  Scenario: GraphQL user query does not expose password
    Given I am authenticated as user "dp-gql@test.com"
    When I send a GraphQL query for user collection
    Then the response status code should be 200
    And the response should not contain "password"
    And the response should not contain "twoFactorSecret"

  # Account lockout response data protection

  Scenario: Account lockout response does not reveal attempt count
    Given user with email "dp-lockout@test.com" and password "passWORD1" exists
    And 20 failed sign-in attempts have been recorded for email "dp-lockout@test.com"
    And signing in with email "dp-lockout@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 423
    And the response should not contain "attemptCount"
    And the response should not contain "failedAttempts"

  # Refresh token value protection

  Scenario: Previous refresh token value not in rotation response
    Given user with email "dp-rotate@test.com" and password "passWORD1" exists
    And user "dp-rotate@test.com" has signed in and received tokens
    And I store the refresh token as "original_token"
    And submitting the stored refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 200
    And the response should not contain the stored "original_token" value

  # 2FA pending session data protection

  Scenario: 2FA pending session response does not expose user details
    Given user with email "dp-pending@test.com" and password "passWORD1" exists
    And user with email "dp-pending@test.com" has 2FA enabled
    And signing in with email "dp-pending@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the response should not contain "dp-pending@test.com"
    And the response should not contain "twoFactorSecret"
