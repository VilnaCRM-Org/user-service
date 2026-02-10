Feature: Input Validation for Authentication Endpoints
  In order to protect against malformed and malicious input
  As the system
  I want strict input validation on all authentication endpoints

  # FR-01, NFR-01: Sign-in input validation edge cases

  Scenario: Sign-in with email exceeding 255 characters is rejected
    Given signing in with an email longer than 255 characters and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 422

  Scenario: Sign-in with empty email string is rejected
    Given signing in with email "" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 422
    And violation should be "This value should not be blank."

  Scenario: Sign-in with empty password string is rejected
    Given signing in with email "empty-pw@test.com" and password ""
    When POST request is send to "/api/signin"
    Then the response status code should be 422
    And violation should be "This value should not be blank."

  Scenario: Sign-in with whitespace-only email is rejected
    Given signing in with email "   " and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 422

  Scenario: Sign-in with null email is rejected
    Given signing in with null email and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 422

  Scenario: Sign-in with null password is rejected
    Given signing in with email "null-pw@test.com" and null password
    When POST request is send to "/api/signin"
    Then the response status code should be 422

  Scenario: Sign-in with numeric email value is rejected
    Given signing in with email as integer 12345 and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 422

  Scenario: Sign-in with boolean remember_me false works
    Given user with email "bool-remember@test.com" and password "passWORD1" exists
    And signing in with email "bool-remember@test.com", password "passWORD1" and remember_me false
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the Set-Cookie header should contain "Max-Age=900"

  Scenario: Sign-in with non-boolean remember_me is handled gracefully
    Given user with email "str-remember@test.com" and password "passWORD1" exists
    And signing in with email "str-remember@test.com", password "passWORD1" and remember_me "yes"
    When POST request is send to "/api/signin"
    Then the response status code should not be 500

  Scenario: Sign-in with extra unexpected fields is handled gracefully
    Given user with email "extra-fields@test.com" and password "passWORD1" exists
    And signing in with email "extra-fields@test.com", password "passWORD1" and extra field "malicious" = "payload"
    When POST request is send to "/api/signin"
    Then the response status code should not be 500

  Scenario: Sign-in with SQL injection in email is not vulnerable
    Given signing in with email "admin'--@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401
    And the response should not contain "syntax error"
    And the response should not contain "query"

  Scenario: Sign-in with XSS payload in email is not reflected
    Given signing in with email "<script>alert(1)</script>@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response should not contain "<script>"

  Scenario: Sign-in with very long password is handled gracefully
    Given signing in with email "longpw@test.com" and a password of 10000 characters
    When POST request is send to "/api/signin"
    Then the response status code should not be 500

  # FR-03, NFR-07: 2FA completion input validation

  Scenario: 2FA completion with empty code is rejected
    Given completing 2FA with pending_session_id "some-session" and code ""
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 422
    And violation should be "This value should not be blank."

  Scenario: 2FA completion with null code is rejected
    Given completing 2FA with pending_session_id "some-session" and null code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 422

  Scenario: 2FA completion with empty pending_session_id is rejected
    Given completing 2FA with pending_session_id "" and code "123456"
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 422
    And violation should be "This value should not be blank."

  Scenario: 2FA completion with SQL injection in pending_session_id is handled
    Given completing 2FA with pending_session_id "'; DROP TABLE users;--" and code "123456"
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 401
    And the response should not contain "syntax error"

  Scenario: 2FA completion with code containing letters when TOTP expected
    Given user with email "2fa-letters@test.com" and password "passWORD1" exists
    And user with email "2fa-letters@test.com" has 2FA enabled
    And signing in with email "2fa-letters@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and code "abcdef"
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 401

  Scenario: 2FA completion with very long code is handled gracefully
    Given completing 2FA with pending_session_id "some-session" and a code of 10000 characters
    When POST request is send to "/api/signin/2fa"
    Then the response status code should not be 500

  # FR-04: Token refresh input validation

  Scenario: Token refresh with empty refresh_token is rejected
    Given submitting refresh token ""
    When POST request is send to "/api/token"
    Then the response status code should be 422
    And violation should be "This value should not be blank."

  Scenario: Token refresh with null refresh_token is rejected
    Given submitting null refresh token
    When POST request is send to "/api/token"
    Then the response status code should be 422

  Scenario: Token refresh with very long token is handled gracefully
    Given submitting a refresh token of 10000 characters
    When POST request is send to "/api/token"
    Then the response status code should not be 500

  Scenario: Token refresh with SQL injection in token is handled
    Given submitting refresh token "'; DROP TABLE auth_sessions;--"
    When POST request is send to "/api/token"
    Then the response status code should be 401
    And the response should not contain "syntax error"

  # FR-15: 2FA disable input validation

  Scenario: 2FA disable with empty code is rejected
    Given I am authenticated as user "2fa-dis-empty@test.com"
    And user "2fa-dis-empty@test.com" has 2FA enabled
    And disabling 2FA with code ""
    When POST request is send to "/api/users/2fa/disable"
    Then the response status code should be 422
    And violation should be "This value should not be blank."

  # FR-08: 2FA confirm input validation

  Scenario: 2FA confirm with null code is rejected
    Given I am authenticated as user "2fa-conf-null@test.com"
    And I have completed 2FA setup
    And confirming 2FA with null code
    When POST request is send to "/api/users/2fa/confirm"
    Then the response status code should be 422

  # Content-Type validation

  Scenario: Sign-in with wrong Content-Type is rejected
    Given signing in with email "ct-wrong@test.com" and password "passWORD1" with Content-Type "text/plain"
    When POST request is send to "/api/signin"
    Then the response status code should be 415

  Scenario: Sign-in with form-encoded data is rejected
    Given signing in with email "ct-form@test.com" and password "passWORD1" with Content-Type "application/x-www-form-urlencoded"
    When POST request is send to "/api/signin"
    Then the response status code should be 415

  Scenario: 2FA completion with wrong Content-Type is rejected
    Given completing 2FA with pending_session_id "some-session" and code "123456" with Content-Type "text/plain"
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 415

  # JSON parsing edge cases

  Scenario: Sign-in with malformed JSON body returns 400
    Given sending malformed JSON body to sign-in
    When POST request is send to "/api/signin"
    Then the response status code should be 400

  Scenario: 2FA completion with malformed JSON body returns 400
    Given sending malformed JSON body to 2FA completion
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 400

  Scenario: Token refresh with malformed JSON body returns 400
    Given sending malformed JSON body to token refresh
    When POST request is send to "/api/token"
    Then the response status code should be 400
