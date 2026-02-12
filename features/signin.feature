Feature: Sign-In Flow
  In order to access protected API resources
  As a registered user
  I want to sign in with email and password

  # Story 1.1: Sign-in without 2FA (FR-01, NFR-01, NFR-50, NFR-53, NFR-54, NFR-55, NFR-56)

  Scenario: Successful sign-in without 2FA
    Given user with email "signin@test.com" and password "passWORD1" exists
    And signing in with email "signin@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the response should contain "access_token"
    And the response should contain "refresh_token"
    And the response field "2fa_enabled" should be false
    And the response should have a Set-Cookie header for "__Host-auth_token"
    And the Set-Cookie header should contain "HttpOnly"
    And the Set-Cookie header should contain "Secure"
    And the Set-Cookie header should contain "SameSite=Lax"
    And the Set-Cookie header should contain "Path=/"
    And the Set-Cookie header should not contain "Domain="

  Scenario: Sign-in returns JWT with required claims
    Given user with email "claims@test.com" and password "passWORD1" exists
    And signing in with email "claims@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the access token should be a valid JWT signed with RS256
    And the JWT should contain claim "sub"
    And the JWT should contain claim "iss" with value "vilnacrm-user-service"
    And the JWT should contain claim "aud" with value "vilnacrm-api"
    And the JWT should contain claim "exp"
    And the JWT should contain claim "iat"
    And the JWT should contain claim "nbf"
    And the JWT should contain claim "jti"
    And the JWT should contain claim "sid"
    And the JWT should contain claim "roles"
    And the JWT "exp" should be approximately 15 minutes after "iat"

  Scenario: Sign-in with remember me flag
    Given user with email "remember@test.com" and password "passWORD1" exists
    And signing in with email "remember@test.com", password "passWORD1" and remember me
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the Set-Cookie header should contain "Max-Age=2592000"

  Scenario: Sign-in without remember me flag
    Given user with email "short-session@test.com" and password "passWORD1" exists
    And signing in with email "short-session@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the Set-Cookie header should contain "Max-Age=900"

  Scenario: Sign-in with invalid password
    Given user with email "wrongpw@test.com" and password "passWORD1" exists
    And signing in with email "wrongpw@test.com" and password "wrongPassword1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401
    And the error message should be "Invalid credentials"
    And the response should be RFC 7807 problem+json
    And the response should have header "WWW-Authenticate" with value "Bearer"

  Scenario: Sign-in with non-existent email
    Given signing in with email "nonexistent@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401
    And the error message should be "Invalid credentials"
    And the response should be RFC 7807 problem+json

  Scenario: Sign-in error does not distinguish between wrong email and wrong password
    Given user with email "existing@test.com" and password "passWORD1" exists
    And signing in with email "existing@test.com" and password "wrongPassword1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401
    And the error message should be "Invalid credentials"
    And signing in with email "definitely-not-existing@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401
    And the error message should be "Invalid credentials"

  Scenario: Sign-in with empty body
    Given sending empty body
    When POST request is send to "/api/signin"
    Then the response status code should be 422
    And violation should be "This value should not be blank."

  Scenario: Sign-in with invalid email format
    Given signing in with email "not-an-email" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 422
    And violation should be "This value is not a valid email address."

  # Story 1.1 AC #8: Account lockout (NFR-55)

  Scenario: Account locked after 20 failed sign-in attempts
    Given user with email "lockout@test.com" and password "passWORD1" exists
    And 20 failed sign-in attempts have been recorded for email "lockout@test.com"
    And signing in with email "lockout@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 423
    And the response should have header "Retry-After"
    And the error message should be "Account temporarily locked"

  Scenario: Successful sign-in resets lockout counter
    Given user with email "lockout-reset@test.com" and password "passWORD1" exists
    And 5 failed sign-in attempts have been recorded for email "lockout-reset@test.com"
    And signing in with email "lockout-reset@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200

  # Story 1.2: Sign-in with 2FA detection (FR-02)

  Scenario: Sign-in with 2FA enabled returns pending session
    Given user with email "2fa-user@test.com" and password "passWORD1" exists
    And user with email "2fa-user@test.com" has 2FA enabled
    And signing in with email "2fa-user@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the response field "2fa_enabled" should be true
    And the response should contain "pending_session_id"
    And the response should not contain "access_token"
    And the response should not contain "refresh_token"
    And the response should not have a Set-Cookie header for "__Host-auth_token"

  Scenario: Sign-in with 2FA and invalid password
    Given user with email "2fa-badpw@test.com" and password "passWORD1" exists
    And user with email "2fa-badpw@test.com" has 2FA enabled
    And signing in with email "2fa-badpw@test.com" and password "wrongPassword1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401
    And the error message should be "Invalid credentials"

  # Additional sign-in edge cases (FR-01, NFR-50, NFR-54, NFR-56)

  Scenario: Sign-in response content type is application/json
    Given user with email "content-type@test.com" and password "passWORD1" exists
    And signing in with email "content-type@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the response should have header "Content-Type" containing "application/json"

  Scenario: Sign-in cookie value contains a valid JWT
    Given user with email "cookie-jwt@test.com" and password "passWORD1" exists
    And signing in with email "cookie-jwt@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the Set-Cookie header value for "__Host-auth_token" should be a valid JWT

  Scenario: Sign-in JWT sub claim matches user ID
    Given user with email "jwt-sub@test.com" and password "passWORD1" exists
    And signing in with email "jwt-sub@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the JWT "sub" claim should match the user's ID

  Scenario: Sign-in JWT roles claim contains ROLE_USER
    Given user with email "jwt-roles@test.com" and password "passWORD1" exists
    And signing in with email "jwt-roles@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the JWT "roles" claim should contain "ROLE_USER"

  Scenario: Sign-in with only email field (missing password)
    Given signing in with email "only-email@test.com" and no password
    When POST request is send to "/api/signin"
    Then the response status code should be 422
    And violation should be "This value should not be blank."

  Scenario: Sign-in with only password field (missing email)
    Given signing in with no email and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 422
    And violation should be "This value should not be blank."

  Scenario: Sign-in response does not expose password hash
    Given user with email "no-hash@test.com" and password "passWORD1" exists
    And signing in with email "no-hash@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the response should not contain "password"

  Scenario: Sign-in error response does not expose password
    Given signing in with email "error-nopw@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401
    And the response should not contain "passWORD1"

  Scenario: Sign-in 401 response includes WWW-Authenticate header
    Given signing in with email "www-auth@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401
    And the response should have header "WWW-Authenticate" with value "Bearer"

  Scenario: Sign-in after password change with new password
    Given user with email "pw-changed@test.com" and password "passWORD1" exists
    And user "pw-changed@test.com" has changed password to "passWORD2"
    And signing in with email "pw-changed@test.com" and password "passWORD2"
    When POST request is send to "/api/signin"
    Then the response status code should be 200

  Scenario: Sign-in after password change with old password fails
    Given user with email "pw-changed-old@test.com" and password "passWORD1" exists
    And user "pw-changed-old@test.com" has changed password to "passWORD2"
    And signing in with email "pw-changed-old@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401
    And the error message should be "Invalid credentials"

  Scenario: Multiple sign-ins create separate sessions
    Given user with email "multi-session@test.com" and password "passWORD1" exists
    And signing in with email "multi-session@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And the response status code should be 200
    And I store the access token from the response as "session1_token"
    And signing in with email "multi-session@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the access token should differ from "session1_token"

  Scenario: Sign-in creates session with IP and user-agent
    Given user with email "session-meta@test.com" and password "passWORD1" exists
    And signing in with email "session-meta@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the JWT should contain claim "sid"

  Scenario: Account lockout Retry-After value is 900 seconds
    Given user with email "lockout-retry@test.com" and password "passWORD1" exists
    And 20 failed sign-in attempts have been recorded for email "lockout-retry@test.com"
    And signing in with email "lockout-retry@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 423
    And the "Retry-After" header value should be "900"

  Scenario: Account lockout returns RFC 7807 problem+json
    Given user with email "lockout-rfc@test.com" and password "passWORD1" exists
    And 20 failed sign-in attempts have been recorded for email "lockout-rfc@test.com"
    And signing in with email "lockout-rfc@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 423
    And the response should be RFC 7807 problem+json

  Scenario: Account lockout expires after 15 minutes
    Given user with email "lockout-expire@test.com" and password "passWORD1" exists
    And 20 failed sign-in attempts have been recorded for email "lockout-expire@test.com"
    And 15 minutes have passed since the lockout
    And signing in with email "lockout-expire@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200

  # Story 1.2 additional: Pending session edge cases (FR-02)

  Scenario: Pending session expires after 5 minutes
    Given user with email "pending-expire@test.com" and password "passWORD1" exists
    And user with email "pending-expire@test.com" has 2FA enabled
    And signing in with email "pending-expire@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And 5 minutes have passed
    And completing 2FA with the stored pending_session_id and a valid TOTP code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 401

  Scenario: Sign-in with 2FA does not create AuthSession until 2FA is complete
    Given user with email "2fa-no-session@test.com" and password "passWORD1" exists
    And user with email "2fa-no-session@test.com" has 2FA enabled
    And signing in with email "2fa-no-session@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And no active AuthSession should exist for user "2fa-no-session@test.com"

  Scenario: Sign-in with 2FA and non-existent email returns same error
    Given user with email "2fa-nonexist@test.com" does not exist
    And signing in with email "2fa-nonexist@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401
    And the error message should be "Invalid credentials"

  # Constant-time credential validation (NFR-01)

  Scenario: Sign-in for non-existent email takes similar time to existing email
    Given user with email "timing-exists@test.com" and password "passWORD1" exists
    And signing in with email "timing-exists@test.com" and password "wrongPassword1"
    And POST request is send to "/api/signin"
    And I store the response time as "existing_email_time"
    And signing in with email "timing-not-exists@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401
    And the response time should be within acceptable range of "existing_email_time"

  # Sign-in creates AuthSession with correct metadata (ADR-01)

  Scenario: Sign-in creates AuthSession with IP address
    Given user with email "session-ip@test.com" and password "passWORD1" exists
    And signing in with email "session-ip@test.com" and password "passWORD1" from IP "192.168.1.100"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the AuthSession should have IP address "192.168.1.100"

  Scenario: Sign-in creates AuthSession with User-Agent
    Given user with email "session-ua@test.com" and password "passWORD1" exists
    And signing in with email "session-ua@test.com" and password "passWORD1" with User-Agent "TestAgent/1.0"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the AuthSession should have User-Agent "TestAgent/1.0"

  # Sign-in with various HTTP method errors

  Scenario: GET request to sign-in endpoint is rejected
    When GET request is send to "/api/signin"
    Then the response status code should be 405

  Scenario: PUT request to sign-in endpoint is rejected
    Given signing in with email "put-signin@test.com" and password "passWORD1"
    When PUT request is send to "/api/signin"
    Then the response status code should be 405

  Scenario: DELETE request to sign-in endpoint is rejected
    When DELETE request is send to "/api/signin"
    Then the response status code should be 405

  # Sign-in concurrent access

  Scenario: Simultaneous sign-ins from same user create independent sessions
    Given user with email "concurrent-signin@test.com" and password "passWORD1" exists
    And signing in with email "concurrent-signin@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And the response status code should be 200
    And I store the JWT "sid" claim as "sid1"
    And signing in with email "concurrent-signin@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the JWT "sid" claim should differ from "sid1"

  # Sign-in response body structure

  Scenario: Sign-in response body contains exactly the expected fields
    Given user with email "resp-fields@test.com" and password "passWORD1" exists
    And signing in with email "resp-fields@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the response should contain "access_token"
    And the response should contain "refresh_token"
    And the response should contain "2fa_enabled"
    And the response should not contain "pending_session_id"

  Scenario: Sign-in with 2FA response body contains exactly the expected fields
    Given user with email "resp-fields-2fa@test.com" and password "passWORD1" exists
    And user with email "resp-fields-2fa@test.com" has 2FA enabled
    And signing in with email "resp-fields-2fa@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the response should contain "pending_session_id"
    And the response should contain "2fa_enabled"
    And the response should not contain "access_token"
    And the response should not contain "refresh_token"
