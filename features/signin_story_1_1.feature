Feature: Story 1.1 Sign-In
  In order to access protected API resources
  As a registered user
  I want to sign in and receive API tokens

  Scenario: Successful sign-in returns access and refresh tokens
    Given user with email "story1-signin@test.com" and password "passWORD1" exists
    And signing in with email "story1-signin@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the response should contain "access_token"
    And the response should contain "refresh_token"
    And the response should contain "2fa_enabled"

  Scenario: Invalid credentials return unauthorized response
    Given user with email "story1-invalid@test.com" and password "passWORD1" exists
    And signing in with email "story1-invalid@test.com" and password "wrongPassword1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401

  Scenario: Account lockout after 20 failed attempts returns 423
    Given user with email "story1-lockout@test.com" and password "passWORD1" exists
    And 20 failed sign-in attempts have been recorded for email "story1-lockout@test.com"
    And signing in with email "story1-lockout@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 423

  Scenario: Non-existent email response time stays close to wrong-password response time
    Given user with email "story1-timing@test.com" and password "passWORD1" exists
    And signing in with email "story1-timing@test.com" and password "wrongPassword1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401
    And I store the response time as "existing_email_time"
    And signing in with email "story1-timing-missing@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401
    And the response time should be within acceptable range of "existing_email_time"
