Feature: Story 2.2 Two-factor setup for current user
  In order to configure two-factor authentication
  As an authenticated user
  I want to receive a TOTP secret and otpauth URI

  Scenario: Authenticated user can generate 2FA setup payload
    Given user with email "story2fa-setup@test.com" and password "passWORD1" exists
    And I am authenticated as user "story2fa-setup@test.com"
    When POST request is send to "/api/users/2fa/setup"
    Then the response status code should be 200
    And the response should contain "otpauth_uri"
    And the response should contain "secret"
    And the response should contain "issuer=VilnaCRM"

  Scenario: 2FA setup does not enable two-factor immediately
    Given user with email "story2fa-setup-state@test.com" and password "passWORD1" exists
    And I am authenticated as user "story2fa-setup-state@test.com"
    When POST request is send to "/api/users/2fa/setup"
    Then the response status code should be 200
    And user with email "story2fa-setup-state@test.com" should have two-factor disabled

  Scenario: Unauthenticated request cannot generate 2FA setup payload
    When POST request is send to "/api/users/2fa/setup"
    Then the response status code should be 401
    And the error message should be "Authentication required."
