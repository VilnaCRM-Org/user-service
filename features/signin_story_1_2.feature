Feature: Story 1.2 Sign-In With 2FA Detection
  In order to enforce second-factor verification
  As a user with 2FA enabled
  I want sign-in to return a pending session instead of tokens

  Scenario: 2FA-enabled user receives pending session without tokens or auth cookie
    Given user with email "story1-2fa@test.com" and password "passWORD1" exists
    And user with email "story1-2fa@test.com" has two-factor enabled
    And signing in with email "story1-2fa@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the response should contain "2fa_enabled"
    And the response should contain "pending_session_id"
    And the response should not contain "access_token"
    And the response should not contain "refresh_token"
    And the response should not set auth cookie
