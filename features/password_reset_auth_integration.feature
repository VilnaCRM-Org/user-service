Feature: Password Reset Integration with Auth System
  In order to recover access to my account securely
  As a registered user
  I want password reset to properly interact with sessions and 2FA

  # FR-11, FR-19: Password reset integration with sessions

  Scenario: Password reset invalidates all active sessions
    Given user with email "reset-sessions@test.com" and password "passWORD1" exists
    And user "reset-sessions@test.com" has 3 active sessions
    And requesting password reset for email "reset-sessions@test.com"
    And POST request is send to "/api/reset-password"
    And the response status code should be 200
    And I confirm the password reset with the received token and new password "passWORD2"
    When POST request is send to "/api/reset-password/confirm"
    Then the response status code should be 200
    And all 3 sessions for user "reset-sessions@test.com" should be revoked

  Scenario: After password reset, old password no longer works
    Given user with email "reset-oldpw@test.com" and password "passWORD1" exists
    And requesting password reset for email "reset-oldpw@test.com"
    And POST request is send to "/api/reset-password"
    And I confirm the password reset with the received token and new password "passWORD2"
    And POST request is send to "/api/reset-password/confirm"
    And the response status code should be 200
    And signing in with email "reset-oldpw@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401

  Scenario: After password reset, new password works
    Given user with email "reset-newpw@test.com" and password "passWORD1" exists
    And requesting password reset for email "reset-newpw@test.com"
    And POST request is send to "/api/reset-password"
    And I confirm the password reset with the received token and new password "passWORD2"
    And POST request is send to "/api/reset-password/confirm"
    And the response status code should be 200
    And signing in with email "reset-newpw@test.com" and password "passWORD2"
    When POST request is send to "/api/signin"
    Then the response status code should be 200

  # Password reset with 2FA-enabled account

  Scenario: Password reset works for 2FA-enabled user
    Given user with email "reset-2fa@test.com" and password "passWORD1" exists
    And user with email "reset-2fa@test.com" has 2FA enabled
    And requesting password reset for email "reset-2fa@test.com"
    When POST request is send to "/api/reset-password"
    Then the response status code should be 200

  Scenario: After password reset 2FA-enabled user still needs 2FA
    Given user with email "reset-2fa-flow@test.com" and password "passWORD1" exists
    And user with email "reset-2fa-flow@test.com" has 2FA enabled
    And requesting password reset for email "reset-2fa-flow@test.com"
    And POST request is send to "/api/reset-password"
    And I confirm the password reset with the received token and new password "passWORD2"
    And POST request is send to "/api/reset-password/confirm"
    And the response status code should be 200
    And signing in with email "reset-2fa-flow@test.com" and password "passWORD2"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the response field "2fa_enabled" should be true
    And the response should contain "pending_session_id"

  # Password reset token reuse prevention

  Scenario: Password reset token cannot be reused
    Given user with email "reset-reuse@test.com" and password "passWORD1" exists
    And requesting password reset for email "reset-reuse@test.com"
    And POST request is send to "/api/reset-password"
    And I store the reset token
    And I confirm the password reset with the stored token and new password "passWORD2"
    And POST request is send to "/api/reset-password/confirm"
    And the response status code should be 200
    And I confirm the password reset with the stored token and new password "passWORD3"
    When POST request is send to "/api/reset-password/confirm"
    Then the response status code should be 401

  # Password reset with locked account

  Scenario: Password reset is allowed for locked account
    Given user with email "reset-locked@test.com" and password "passWORD1" exists
    And 20 failed sign-in attempts have been recorded for email "reset-locked@test.com"
    And requesting password reset for email "reset-locked@test.com"
    When POST request is send to "/api/reset-password"
    Then the response status code should not be 423

  Scenario: After password reset, locked account can sign in
    Given user with email "reset-unlock@test.com" and password "passWORD1" exists
    And 20 failed sign-in attempts have been recorded for email "reset-unlock@test.com"
    And requesting password reset for email "reset-unlock@test.com"
    And POST request is send to "/api/reset-password"
    And I confirm the password reset with the received token and new password "passWORD2"
    And POST request is send to "/api/reset-password/confirm"
    And the response status code should be 200
    And signing in with email "reset-unlock@test.com" and password "passWORD2"
    When POST request is send to "/api/signin"
    Then the response status code should be 200

  # Password reset email enumeration prevention

  Scenario: Password reset for non-existent email returns same response
    Given user with email "reset-exists@test.com" and password "passWORD1" exists
    And requesting password reset for email "reset-exists@test.com"
    And POST request is send to "/api/reset-password"
    And I store the response status as "existing_status"
    And requesting password reset for email "reset-not-exists@test.com"
    When POST request is send to "/api/reset-password"
    Then the response status code should match "existing_status"

  # Password reset refresh token invalidation

  Scenario: Password reset invalidates all refresh tokens
    Given user with email "reset-refresh@test.com" and password "passWORD1" exists
    And user "reset-refresh@test.com" has signed in and received tokens
    And I store the refresh token
    And requesting password reset for email "reset-refresh@test.com"
    And POST request is send to "/api/reset-password"
    And I confirm the password reset with the received token and new password "passWORD2"
    And POST request is send to "/api/reset-password/confirm"
    And the response status code should be 200
    And submitting the stored refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 401

  # Password reset data protection

  Scenario: Password reset request response does not confirm email exists
    Given requesting password reset for email "reset-dp@test.com"
    When POST request is send to "/api/reset-password"
    Then the response should not contain "reset-dp@test.com"
    And the response should not contain "not found"
    And the response should not contain "does not exist"

  Scenario: Password reset confirm response does not expose password hash
    Given user with email "reset-dp-confirm@test.com" and password "passWORD1" exists
    And requesting password reset for email "reset-dp-confirm@test.com"
    And POST request is send to "/api/reset-password"
    And I confirm the password reset with the received token and new password "passWORD2"
    When POST request is send to "/api/reset-password/confirm"
    Then the response should not contain "password"
    And the response should not contain "passWORD2"
    And the response should not contain "$2y$"
