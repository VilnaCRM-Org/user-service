Feature: Password Reset Operations
  In order to allow users to reset their passwords
  As a system
  I want to provide password reset functionality

  Scenario: Requesting password reset for existing user
    Given user with email "reset@test.com" exists
    And requesting password reset for email "reset@test.com"
    When POST request is send to "/api/reset-password"
    Then the response status code should be 204

  Scenario: Requesting password reset for non-existing user
    Given requesting password reset for email "nonexistent@test.com"
    When POST request is send to "/api/reset-password"
    Then the response status code should be 204

  Scenario: Confirming password reset with valid token
    Given user with email "reset2@test.com" exists
    And password reset token exists for user "reset2@test.com"
    And confirming password reset with valid token and password "newPassWORD1"
    When POST request is send to "/api/reset-password/confirm"
    Then the response status code should be 204

  Scenario: Confirming password reset with invalid token
    Given confirming password reset with token "invalid-token" and password "newPassWORD1"
    When POST request is send to "/api/reset-password/confirm"
    Then the response status code should be 404