Feature: Account Lockout
  In order to protect accounts from brute-force attacks
  As the system
  I want to lock accounts after repeated failed sign-in attempts

  # Story 5.2: Account lockout (NFR-55, ADR-10)

  Scenario: Account is not locked below threshold
    Given user with email "lockout-below@test.com" and password "passWORD1" exists
    And 19 failed sign-in attempts have been recorded for email "lockout-below@test.com"
    And signing in with email "lockout-below@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200

  Scenario: Account is locked at exactly 20 failures
    Given user with email "lockout-exact@test.com" and password "passWORD1" exists
    And 20 failed sign-in attempts have been recorded for email "lockout-exact@test.com"
    And signing in with email "lockout-exact@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 423
    And the response should have header "Retry-After"
    And the error message should be "Account temporarily locked"

  Scenario: Account lockout blocks even correct credentials
    Given user with email "lockout-correct@test.com" and password "passWORD1" exists
    And 20 failed sign-in attempts have been recorded for email "lockout-correct@test.com"
    And signing in with email "lockout-correct@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 423

  Scenario: Account lockout resets after 1 hour
    Given user with email "lockout-1h@test.com" and password "passWORD1" exists
    And 20 failed sign-in attempts have been recorded for email "lockout-1h@test.com"
    And 1 hour has passed since the first failed attempt
    And signing in with email "lockout-1h@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200

  Scenario: Account lockout expires after 15 minutes
    Given user with email "lockout-15m@test.com" and password "passWORD1" exists
    And 20 failed sign-in attempts have been recorded for email "lockout-15m@test.com"
    And 15 minutes have passed since the lockout
    And signing in with email "lockout-15m@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200

  Scenario: Account lockout does not expire before 15 minutes
    Given user with email "lockout-early@test.com" and password "passWORD1" exists
    And 20 failed sign-in attempts have been recorded for email "lockout-early@test.com"
    And 10 minutes have passed since the lockout
    And signing in with email "lockout-early@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 423

  Scenario: Successful sign-in resets failure counter
    Given user with email "lockout-reset-counter@test.com" and password "passWORD1" exists
    And 15 failed sign-in attempts have been recorded for email "lockout-reset-counter@test.com"
    And signing in with email "lockout-reset-counter@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And the response status code should be 200
    And 10 failed sign-in attempts are recorded for email "lockout-reset-counter@test.com"
    And signing in with email "lockout-reset-counter@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200

  Scenario: Account lockout applies per email not globally
    Given user with email "lockout-per-email@test.com" and password "passWORD1" exists
    And user with email "lockout-other@test.com" and password "passWORD1" exists
    And 20 failed sign-in attempts have been recorded for email "lockout-per-email@test.com"
    And signing in with email "lockout-other@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200

  Scenario: Account lockout Retry-After is 900 seconds
    Given user with email "lockout-retry-value@test.com" and password "passWORD1" exists
    And 20 failed sign-in attempts have been recorded for email "lockout-retry-value@test.com"
    And signing in with email "lockout-retry-value@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 423
    And the "Retry-After" header value should be "900"

  Scenario: Lockout with 2FA-enabled user
    Given user with email "lockout-2fa@test.com" and password "passWORD1" exists
    And user with email "lockout-2fa@test.com" has 2FA enabled
    And 20 failed sign-in attempts have been recorded for email "lockout-2fa@test.com"
    And signing in with email "lockout-2fa@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 423

  # Lockout counter TTL (ADR-10, NFR-55)

  Scenario: Account lockout counter TTL is exactly 1 hour
    Given user with email "lockout-counter-ttl@test.com" and password "passWORD1" exists
    And 19 failed sign-in attempts have been recorded for email "lockout-counter-ttl@test.com"
    And 59 minutes have passed since the first failed attempt
    And signing in with email "lockout-counter-ttl@test.com" and password "wrongPassword1"
    When POST request is send to "/api/signin"
    Then the response status code should be 423

  Scenario: Account lockout counter resets after exactly 1 hour
    Given user with email "lockout-counter-reset@test.com" and password "passWORD1" exists
    And 19 failed sign-in attempts have been recorded for email "lockout-counter-reset@test.com"
    And 61 minutes have passed since the first failed attempt
    And signing in with email "lockout-counter-reset@test.com" and password "wrongPassword1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401

  # Lockout error response format (NFR-25)

  Scenario: Account lockout error response is RFC 7807
    Given user with email "lockout-rfc@test.com" and password "passWORD1" exists
    And 20 failed sign-in attempts have been recorded for email "lockout-rfc@test.com"
    And signing in with email "lockout-rfc@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 423
    And the response should be RFC 7807 problem+json
    And the response should contain "type"
    And the response should contain "title"
    And the response should contain "detail"

  # Lockout with password change (FR-19)

  Scenario: Account lockout does not prevent password reset
    Given user with email "lockout-reset@test.com" and password "passWORD1" exists
    And 20 failed sign-in attempts have been recorded for email "lockout-reset@test.com"
    And requesting password reset for email "lockout-reset@test.com"
    When POST request is send to "/api/reset-password"
    Then the response status code should not be 423

  # Lockout with non-existent account (NFR-01)

  Scenario: Failed sign-in for non-existent account does not reveal account existence
    Given user with email "lockout-nonexist@test.com" does not exist
    And 20 failed sign-in attempts have been recorded for email "lockout-nonexist@test.com"
    And signing in with email "lockout-nonexist@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 423
    And the error message should be "Account temporarily locked"

  # Lockout incrementing on different failure types

  Scenario: Wrong password attempts accumulate toward lockout
    Given user with email "lockout-accum@test.com" and password "passWORD1" exists
    And 18 failed sign-in attempts have been recorded for email "lockout-accum@test.com"
    And signing in with email "lockout-accum@test.com" and password "wrongPassword1"
    And POST request is send to "/api/signin"
    And the response status code should be 401
    And signing in with email "lockout-accum@test.com" and password "wrongPassword2"
    When POST request is send to "/api/signin"
    Then the response status code should be 423
