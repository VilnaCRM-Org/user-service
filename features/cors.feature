Feature: CORS Configuration
  In order to support cookie-based authentication for web clients
  As the system
  I want CORS properly configured with credentials support

  # NFR-40, NFR-65: CORS with credentials and explicit origin

  Scenario: CORS preflight includes credentials support
    When an OPTIONS request is send to "/api/users" with Origin header
    Then the response should have header "Access-Control-Allow-Credentials" with value "true"

  Scenario: CORS response does not use wildcard origin with credentials
    When an OPTIONS request is send to "/api/users" with Origin header
    Then the response should have header "Access-Control-Allow-Origin"
    And the "Access-Control-Allow-Origin" header should not be "*"

  Scenario: CORS preflight includes allowed methods
    When an OPTIONS request is send to "/api/users" with Origin header
    Then the response should have header "Access-Control-Allow-Methods"

  Scenario: CORS preflight includes allowed headers
    When an OPTIONS request is send to "/api/users" with Origin header
    Then the response should have header "Access-Control-Allow-Headers"
    And the "Access-Control-Allow-Headers" header should contain "Authorization"

  Scenario: CORS credentials header present on sign-in response
    Given user with email "cors-signin@test.com" and password "passWORD1" exists
    And signing in with email "cors-signin@test.com" and password "passWORD1" with Origin header
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the response should have header "Access-Control-Allow-Credentials" with value "true"

  Scenario: CORS credentials header present on authenticated response
    Given I am authenticated as user "cors-auth@test.com"
    When GET request is send to "/api/users?page=1&itemsPerPage=10" with Origin header
    Then the response status code should be 200
    And the response should have header "Access-Control-Allow-Credentials" with value "true"

  Scenario: CORS credentials header present on error response
    When GET request is send to "/api/users?page=1&itemsPerPage=10" with Origin header
    Then the response status code should be 401
    And the response should have header "Access-Control-Allow-Credentials" with value "true"

  # CORS edge cases (NFR-40, NFR-65)

  Scenario: CORS preflight response has correct status code
    When an OPTIONS request is send to "/api/users" with Origin header
    Then the response status code should be 204

  Scenario: CORS preflight with unknown origin is rejected or ignored
    When an OPTIONS request is send to "/api/users" with Origin "https://malicious-site.com"
    Then the response should not have header "Access-Control-Allow-Origin" with value "https://malicious-site.com"

  Scenario: CORS preflight exposes required headers
    When an OPTIONS request is send to "/api/users" with Origin header
    Then the response should have header "Access-Control-Expose-Headers"

  Scenario: CORS credentials header present on 2FA response
    Given user with email "cors-2fa@test.com" and password "passWORD1" exists
    And user with email "cors-2fa@test.com" has 2FA enabled
    And signing in with email "cors-2fa@test.com" and password "passWORD1" with Origin header
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the response should have header "Access-Control-Allow-Credentials" with value "true"

  Scenario: CORS credentials header present on token refresh response
    Given user with email "cors-refresh@test.com" and password "passWORD1" exists
    And user "cors-refresh@test.com" has signed in and received tokens
    And submitting the refresh token to exchange with Origin header
    When POST request is send to "/api/token"
    Then the response status code should be 200
    And the response should have header "Access-Control-Allow-Credentials" with value "true"

  Scenario: CORS credentials header present on logout response
    Given I am authenticated as user "cors-logout@test.com"
    When POST request is send to "/api/signout" with Origin header
    Then the response status code should be 204
    And the response should have header "Access-Control-Allow-Credentials" with value "true"

  Scenario: CORS credentials header present on rate limit response
    Given 10 sign-in requests from the same IP have been sent within 1 minute
    And signing in with email "cors-429@test.com" and password "passWORD1" with Origin header
    When POST request is send to "/api/signin"
    Then the response status code should be 429
    And the response should have header "Access-Control-Allow-Credentials" with value "true"

  Scenario: CORS request without Origin header does not include CORS headers
    When GET request is send to "/api/health" without Origin header
    Then the response status code should be 200
    And the response should not have header "Access-Control-Allow-Origin"
