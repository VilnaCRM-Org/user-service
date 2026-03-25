Feature: JWT Token Validation
  In order to prevent token forgery and misuse
  As the system
  I want strict JWT validation on all protected endpoints

  # ADR-01, NFR-38, NFR-50, NFR-51: JWT claims and algorithm validation

  Scenario: JWT with correct claims is accepted
    Given I am authenticated as user "jwt-valid@test.com"
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 200

  Scenario: JWT with wrong issuer is rejected
    Given I have a JWT with issuer "wrong-issuer"
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401
    And the response should have header "WWW-Authenticate" with value "Bearer"

  Scenario: JWT with wrong audience is rejected
    Given I have a JWT with audience "wrong-audience"
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401
    And the response should have header "WWW-Authenticate" with value "Bearer"

  Scenario: JWT signed with HS256 is rejected
    Given I have a JWT signed with algorithm "HS256"
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401

  Scenario: JWT signed with none algorithm is rejected
    Given I have a JWT signed with algorithm "none"
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401

  Scenario: Expired JWT is rejected
    Given I have an expired JWT
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401

  Scenario: JWT with future nbf is rejected
    Given I have a JWT with nbf set to 1 hour in the future
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401

  Scenario: JWT with issuer as array is rejected
    Given I have a JWT with issuer as array ["vilnacrm-user-service"]
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401

  Scenario: JWT without sub claim is rejected
    Given I have a JWT without the "sub" claim
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401

  Scenario: JWT without sid claim is rejected
    Given I have a JWT without the "sid" claim
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401

  Scenario: JWT with tampered payload is rejected
    Given I have a JWT with tampered payload
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401

  Scenario: Malformed JWT string is rejected
    Given I have a malformed JWT "not.a.valid.jwt"
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401

  Scenario: Empty Authorization header is rejected
    Given I have an empty Authorization header
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401

  Scenario: Bearer token with extra spaces is handled
    Given I have a Bearer token with extra leading space
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401

  # ADR-01: Dual authentication (Bearer + Cookie)

  Scenario: Authentication via Bearer header succeeds
    Given I am authenticated via bearer token as user "bearer-auth@test.com"
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 200

  Scenario: Authentication via session cookie succeeds
    Given I am authenticated via session cookie as user "cookie-auth@test.com"
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 200

  Scenario: Bearer token takes precedence over cookie
    Given I have a valid Bearer token for user "bearer-user@test.com"
    And I have a valid session cookie for user "cookie-user@test.com"
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 200
    And the authenticated user should be "bearer-user@test.com"

  Scenario: Invalid Bearer with valid cookie falls back to cookie
    Given I have an invalid Bearer token
    And I am authenticated via session cookie as user "fallback-cookie@test.com"
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401

  # JWT expiration validation

  Scenario: JWT access token expires after 15 minutes
    Given user with email "jwt-ttl@test.com" and password "passWORD1" exists
    And signing in with email "jwt-ttl@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the JWT "exp" should be approximately 15 minutes after "iat"

  Scenario: JWT issued at sign-in with remember_me still has 15-min JWT TTL
    Given user with email "jwt-remember@test.com" and password "passWORD1" exists
    And signing in with email "jwt-remember@test.com", password "passWORD1" and remember me
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the JWT "exp" should be approximately 15 minutes after "iat"

  # JWT boundary cases

  Scenario: JWT with extra unknown claims is still accepted
    Given I have a JWT with valid claims and an extra claim "custom_field" = "value"
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 200

  Scenario: JWT with audience as array is rejected
    Given I have a JWT with audience as array ["vilnacrm-api"]
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401

  Scenario: JWT with nbf exactly equal to current time is accepted
    Given I have a JWT with nbf set to the current time
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 200

  Scenario: JWT with exp exactly equal to current time is rejected
    Given I have a JWT with exp set to the current time
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401

  # JWT algorithm enforcement (ADR-01, NFR-51)

  Scenario: JWT signed with RS384 is rejected
    Given I have a JWT signed with algorithm "RS384"
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401

  Scenario: JWT signed with RS512 is rejected
    Given I have a JWT signed with algorithm "RS512"
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401

  Scenario: JWT signed with ES256 is rejected
    Given I have a JWT signed with algorithm "ES256"
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401

  Scenario: JWT signed with PS256 is rejected
    Given I have a JWT signed with algorithm "PS256"
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401

  # JWT structure validation

  Scenario: JWT with only 2 segments is rejected
    Given I have a JWT with only header and payload "eyJhbGciOiJSUzI1NiJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0"
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401

  Scenario: JWT with empty signature segment is rejected
    Given I have a JWT with empty signature "eyJhbGciOiJSUzI1NiJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0."
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401

  # Token binding awareness (ADR-13: accepted risk for MVP)

  Scenario: Bearer token used from different IP is accepted (MVP limitation)
    Given user with email "jwt-ip-binding@test.com" and password "passWORD1" exists
    And user "jwt-ip-binding@test.com" has signed in from IP "192.168.1.1"
    And I use the access token from a different IP "10.0.0.1"
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 200

  Scenario: Bearer token used with different User-Agent is accepted (MVP limitation)
    Given user with email "jwt-ua-binding@test.com" and password "passWORD1" exists
    And user "jwt-ua-binding@test.com" has signed in with User-Agent "Browser/1.0"
    And I use the access token with User-Agent "DifferentBrowser/2.0"
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 200

  # JWT from revoked session

  Scenario: JWT from revoked session is rejected
    Given user with email "jwt-revoked@test.com" and password "passWORD1" exists
    And user "jwt-revoked@test.com" has signed in and the session was subsequently revoked
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401

  # JWT after 2FA sign-in

  Scenario: JWT from 2FA sign-in has all standard claims
    Given user with email "jwt-2fa-claims@test.com" and password "passWORD1" exists
    And user with email "jwt-2fa-claims@test.com" has 2FA enabled
    And signing in with email "jwt-2fa-claims@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and a valid TOTP code
    When POST request is send to "/api/signin/2fa"
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
