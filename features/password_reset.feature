Feature: Password Reset Operations
  In order to allow users to reset their passwords
  As a system
  I want to provide password reset functionality

  Scenario: Requesting password reset for existing user
    Given user with email "reset@test.com" exists
    And requesting password reset for email "reset@test.com"
    When POST request is send to "/api/users/{id}/reset-password"
    Then the response status code should be 200
    And the response should contain "If valid, you will receive a password reset link"

  Scenario: Requesting password reset for non-existing user
    Given requesting password reset for email "nonexistent@test.com"
    When POST request is send to "/api/users/{id}/reset-password"
    Then the response status code should be 200
    And the response should contain "If valid, you will receive a password reset link"

  Scenario: Confirming password reset with valid token
    Given user with email "reset2@test.com" exists
    And password reset token exists for user "reset2@test.com"
    And confirming password reset with valid token and password "newPassWORD1"
    When POST request is send to "/api/users/{id}/reset-password/confirm"
    Then the response status code should be 200
    And the response should contain "Password has been reset successfully"

  Scenario: Confirming password reset with invalid token
    Given confirming password reset with token "invalid-token" and password "newPassWORD1"
    When POST request is send to "/api/users/{id}/reset-password/confirm"
    Then the response status code should be 404