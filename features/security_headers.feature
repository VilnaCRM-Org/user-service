Feature: Security Headers and Hardening
  In order to pass security audits and protect against common attacks
  As the system
  I want security headers present on all API responses

  # Story 5.3: Security headers (NFR-19 through NFR-23, NFR-66)

  Scenario: HSTS header is present on API responses
    When GET request is send to "/api/health"
    Then the response should have header "Strict-Transport-Security" with value "max-age=31536000; includeSubDomains"

  Scenario: X-Content-Type-Options header is present
    When GET request is send to "/api/health"
    Then the response should have header "X-Content-Type-Options" with value "nosniff"

  Scenario: X-Frame-Options header is present
    When GET request is send to "/api/health"
    Then the response should have header "X-Frame-Options" with value "DENY"

  Scenario: Referrer-Policy header is present
    When GET request is send to "/api/health"
    Then the response should have header "Referrer-Policy" with value "strict-origin-when-cross-origin"

  Scenario: Content-Security-Policy header is present
    When GET request is send to "/api/health"
    Then the response should have header "Content-Security-Policy" with value "default-src 'none'; frame-ancestors 'none'"

  Scenario: Server header is removed
    When GET request is send to "/api/health"
    Then the response should not have header "Server"

  Scenario: Permissions-Policy header is present
    When GET request is send to "/api/health"
    Then the response should have header "Permissions-Policy" containing "camera=()"
    And the response should have header "Permissions-Policy" containing "microphone=()"
    And the response should have header "Permissions-Policy" containing "geolocation=()"
    And the response should have header "Permissions-Policy" containing "payment=()"

  # Security headers on authenticated endpoints too

  Scenario: Security headers present on authenticated response
    Given I am authenticated as user "headers-auth@test.com"
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 200
    And the response should have header "X-Content-Type-Options" with value "nosniff"
    And the response should have header "X-Frame-Options" with value "DENY"

  # Security headers on error responses

  Scenario: Security headers present on 401 error response
    When GET request is send to "/api/users"
    Then the response status code should be 401
    And the response should have header "X-Content-Type-Options" with value "nosniff"
    And the response should have header "X-Frame-Options" with value "DENY"

  # Story 5.4: GraphQL hardening (NFR-24, NFR-35, NFR-36)

  Scenario: GraphQL introspection disabled in production
    Given the application environment is "prod"
    And I am authenticated as user "gql-intro@test.com"
    When I send a GraphQL introspection query
    Then the response should contain a GraphQL error
    And the response should not contain "__schema"

  Scenario: GraphQL query depth exceeding limit is rejected
    Given I am authenticated as user "gql-depth@test.com"
    When I send a GraphQL query with depth greater than 20
    Then the response should contain a GraphQL error about depth

  Scenario: GraphQL query complexity exceeding limit is rejected
    Given I am authenticated as user "gql-complexity@test.com"
    When I send a GraphQL query with complexity greater than 500
    Then the response should contain a GraphQL error about complexity

  # Story 5.8: GraphQL batch defense (NFR-59)

  Scenario: GraphQL batch request is rejected
    Given I am authenticated as user "gql-batch@test.com"
    When I send a GraphQL batch request as JSON array
    Then the response status code should be 400

  # Story 5.8: Auth operations excluded from GraphQL (NFR-62)

  Scenario: Sign-in mutation not exposed in GraphQL
    Given I am authenticated as user "gql-nosignin@test.com"
    When I send a GraphQL introspection query for mutation types
    Then the response should not contain "signIn" mutation
    And the response should not contain "completeTwoFactor" mutation
    And the response should not contain "signOut" mutation
    And the response should not contain "signOutAll" mutation
    And the response should not contain "setupTwoFactor" mutation
    And the response should not contain "confirmTwoFactor" mutation
    And the response should not contain "disableTwoFactor" mutation
    And the response should not contain "refreshToken" mutation

  # Story 5.7: Request body size limit (NFR-39)

  Scenario: Oversized request body is rejected
    Given a request body larger than 64KB
    When POST request is send to "/api/users"
    Then the response status code should be 413

  # NFR-14, NFR-25: Error response format

  Scenario: Rate limit rejection uses RFC 7807 format
    Given user with email "ratelimit-format@test.com" exists
    And the sign-in rate limit for IP has been exceeded
    And signing in with email "ratelimit-format@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 429
    And the response should have header "Retry-After"
    And the response should be RFC 7807 problem+json
    And the response should contain "type"
    And the response should contain "title"
    And the response should contain "status"
    And the response should contain "detail"

  # Additional security header scenarios (NFR-19-23, NFR-66)

  Scenario: Security headers present on sign-in success response
    Given user with email "headers-signin@test.com" and password "passWORD1" exists
    And signing in with email "headers-signin@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the response should have header "X-Content-Type-Options" with value "nosniff"
    And the response should have header "X-Frame-Options" with value "DENY"

  Scenario: Security headers present on sign-in failure response
    Given signing in with email "headers-fail@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401
    And the response should have header "X-Content-Type-Options" with value "nosniff"
    And the response should have header "X-Frame-Options" with value "DENY"

  Scenario: Security headers present on 2FA completion response
    Given user with email "headers-2fa@test.com" and password "passWORD1" exists
    And user with email "headers-2fa@test.com" has 2FA enabled
    And signing in with email "headers-2fa@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and a valid TOTP code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 200
    And the response should have header "X-Content-Type-Options" with value "nosniff"

  Scenario: Security headers present on token refresh response
    Given user with email "headers-refresh@test.com" and password "passWORD1" exists
    And user "headers-refresh@test.com" has signed in and received tokens
    And submitting the refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 200
    And the response should have header "X-Content-Type-Options" with value "nosniff"

  Scenario: Security headers present on 422 validation error response
    Given sending empty body
    When POST request is send to "/api/signin"
    Then the response status code should be 422
    And the response should have header "X-Content-Type-Options" with value "nosniff"
    And the response should have header "X-Frame-Options" with value "DENY"

  Scenario: Security headers present on 429 rate limit response
    Given 10 sign-in requests from the same IP have been sent within 1 minute
    And signing in with email "headers-429@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 429
    And the response should have header "X-Content-Type-Options" with value "nosniff"
    And the response should have header "X-Frame-Options" with value "DENY"

  Scenario: Security headers present on 403 forbidden response
    Given I am authenticated as user "headers-403@test.com" with id "8be90127-9840-4235-a6da-39b8debfb270"
    And user with id "8be90127-9840-4235-a6da-39b8debfb271" exists
    When DELETE request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb271"
    Then the response status code should be 403
    And the response should have header "X-Content-Type-Options" with value "nosniff"

  Scenario: Security headers present on 204 logout response
    Given I am authenticated as user "headers-logout@test.com"
    When POST request is send to "/api/signout"
    Then the response status code should be 204
    And the response should have header "X-Content-Type-Options" with value "nosniff"

  Scenario: Security headers present on registration response
    Given creating user with email "headers-reg@test.com", initials "name surname", password "passWORD1"
    When POST request is send to "/api/users"
    Then the response status code should be 201
    And the response should have header "X-Content-Type-Options" with value "nosniff"
    And the response should have header "X-Frame-Options" with value "DENY"

  # Additional GraphQL hardening (NFR-24, NFR-35, NFR-36, NFR-59, NFR-62)

  Scenario: GraphQL batch request with multiple queries is rejected
    Given I am authenticated as user "gql-batch-multi@test.com"
    When I send a GraphQL batch request with 5 queries as JSON array
    Then the response status code should be 400

  Scenario: GraphQL single request still works after batch rejection
    Given I am authenticated as user "gql-single@test.com"
    When I send a single GraphQL query for user collection
    Then the response status code should be 200

  Scenario: Token exchange mutation not exposed in GraphQL
    Given I am authenticated as user "gql-notoken@test.com"
    When I send a GraphQL introspection query for mutation types
    Then the response should not contain "refreshToken" mutation
    And the response should not contain "tokenExchange" mutation

  # Security headers on all response types

  Scenario: Security headers present on 423 lockout response
    Given user with email "headers-lockout@test.com" and password "passWORD1" exists
    And 20 failed sign-in attempts have been recorded for email "headers-lockout@test.com"
    And signing in with email "headers-lockout@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 423
    And the response should have header "X-Content-Type-Options" with value "nosniff"
    And the response should have header "X-Frame-Options" with value "DENY"

  Scenario: Security headers present on CORS preflight response
    When an OPTIONS request is send to "/api/users" with Origin header
    Then the response should have header "X-Content-Type-Options" with value "nosniff"

  Scenario: Security headers present on 405 method not allowed response
    When GET request is send to "/api/signin"
    Then the response status code should be 405
    And the response should have header "X-Content-Type-Options" with value "nosniff"

  Scenario: Security headers present on GraphQL response
    Given I am authenticated as user "headers-gql@test.com"
    When I send a single GraphQL query for user collection
    Then the response status code should be 200
    And the response should have header "X-Content-Type-Options" with value "nosniff"
    And the response should have header "X-Frame-Options" with value "DENY"

  # Cache-Control headers for security (NFR-66)

  Scenario: Authentication responses include no-store Cache-Control
    Given user with email "headers-cache@test.com" and password "passWORD1" exists
    And signing in with email "headers-cache@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the response should have header "Cache-Control" containing "no-store"

  Scenario: Token refresh response includes no-store Cache-Control
    Given user with email "headers-cache-refresh@test.com" and password "passWORD1" exists
    And user "headers-cache-refresh@test.com" has signed in and received tokens
    And submitting the refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 200
    And the response should have header "Cache-Control" containing "no-store"

  Scenario: User data response includes no-store Cache-Control
    Given I am authenticated as user "headers-cache-user@test.com"
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 200
    And the response should have header "Cache-Control" containing "no-store"
