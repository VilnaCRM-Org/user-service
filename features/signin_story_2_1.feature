Feature: Story 2.1 Complete 2FA sign-in (TOTP)
  In order to finish authentication when 2FA is enabled
  As a user with a pending 2FA session
  I want to submit a valid TOTP code and receive tokens

  Scenario: Valid pending session and TOTP code returns tokens and auth cookie
    Given user with email "story2fa-success@test.com" and password "passWORD1" exists
    And user with email "story2fa-success@test.com" has two-factor enabled with secret "JBSWY3DPEHPK3PXP"
    And signing in with email "story2fa-success@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the response should contain "pending_session_id"
    And completing 2FA with stored pending session and secret "JBSWY3DPEHPK3PXP"
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 200
    And the response should contain "access_token"
    And the response should contain "refresh_token"
    And the response should set auth cookie

  Scenario: Invalid TOTP code returns 401 but pending session stays valid for retry
    Given user with email "story2fa-retry@test.com" and password "passWORD1" exists
    And user with email "story2fa-retry@test.com" has two-factor enabled with secret "JBSWY3DPEHPK3PXP"
    And signing in with email "story2fa-retry@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And the response status code should be 200
    And I store the pending_session_id from the response
    And completing 2FA with stored pending session and code "111111"
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 401
    And completing 2FA with stored pending session and secret "JBSWY3DPEHPK3PXP"
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 200
    And the response should contain "access_token"
