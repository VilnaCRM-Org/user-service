Feature: Cookie Security and Attribute Validation
  In order to prevent session hijacking and cookie-based attacks
  As the system
  I want strict cookie attributes on all auth-related Set-Cookie headers

  # NFR-54: __Host- prefix requirements (RFC 6265bis)

  Scenario: Sign-in cookie uses __Host- prefix
    Given user with email "cookie-host@test.com" and password "passWORD1" exists
    And signing in with email "cookie-host@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the response should have a Set-Cookie header for "__Host-auth_token"

  Scenario: Sign-in cookie has no Domain attribute
    Given user with email "cookie-nodomain@test.com" and password "passWORD1" exists
    And signing in with email "cookie-nodomain@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the Set-Cookie header should not contain "Domain"

  Scenario: Sign-in cookie sets Path to root
    Given user with email "cookie-path@test.com" and password "passWORD1" exists
    And signing in with email "cookie-path@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the Set-Cookie header should contain "Path=/"

  Scenario: Sign-in cookie sets Secure flag
    Given user with email "cookie-secure@test.com" and password "passWORD1" exists
    And signing in with email "cookie-secure@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the Set-Cookie header should contain "Secure"

  Scenario: Sign-in cookie sets HttpOnly flag
    Given user with email "cookie-httponly@test.com" and password "passWORD1" exists
    And signing in with email "cookie-httponly@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the Set-Cookie header should contain "HttpOnly"

  Scenario: Sign-in cookie sets SameSite=Lax
    Given user with email "cookie-samesite@test.com" and password "passWORD1" exists
    And signing in with email "cookie-samesite@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the Set-Cookie header should contain "SameSite=Lax"

  # NFR-54: Cookie lifetime based on remember_me

  Scenario: Cookie Max-Age is 900 seconds without remember_me
    Given user with email "cookie-short@test.com" and password "passWORD1" exists
    And signing in with email "cookie-short@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the Set-Cookie header should contain "Max-Age=900"

  Scenario: Cookie Max-Age is 2592000 seconds with remember_me
    Given user with email "cookie-long@test.com" and password "passWORD1" exists
    And signing in with email "cookie-long@test.com", password "passWORD1" and remember me
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the Set-Cookie header should contain "Max-Age=2592000"

  # NFR-54: 2FA completion cookie attributes

  Scenario: 2FA sign-in cookie has all required security attributes
    Given user with email "cookie-2fa@test.com" and password "passWORD1" exists
    And user with email "cookie-2fa@test.com" has 2FA enabled
    And signing in with email "cookie-2fa@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and a valid TOTP code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 200
    And the response should have a Set-Cookie header for "__Host-auth_token"
    And the Set-Cookie header should contain "HttpOnly"
    And the Set-Cookie header should contain "Secure"
    And the Set-Cookie header should contain "SameSite=Lax"
    And the Set-Cookie header should contain "Path=/"
    And the Set-Cookie header should not contain "Domain"

  Scenario: 2FA sign-in with remember_me preserves long cookie lifetime
    Given user with email "cookie-2fa-remember@test.com" and password "passWORD1" exists
    And user with email "cookie-2fa-remember@test.com" has 2FA enabled
    And signing in with email "cookie-2fa-remember@test.com", password "passWORD1" and remember me
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and a valid TOTP code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 200
    And the Set-Cookie header should contain "Max-Age=2592000"

  Scenario: 2FA sign-in without remember_me uses short cookie lifetime
    Given user with email "cookie-2fa-short@test.com" and password "passWORD1" exists
    And user with email "cookie-2fa-short@test.com" has 2FA enabled
    And signing in with email "cookie-2fa-short@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and a valid TOTP code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 200
    And the Set-Cookie header should contain "Max-Age=900"

  # NFR-54: Cookie clearing on logout

  Scenario: Logout clears cookie with Max-Age=0 and all security attributes
    Given I am authenticated as user "cookie-logout@test.com"
    When POST request is send to "/api/signout"
    Then the response status code should be 204
    And the response should have a Set-Cookie header for "__Host-auth_token"
    And the Set-Cookie header should contain "Max-Age=0"
    And the Set-Cookie header should contain "Path=/"
    And the Set-Cookie header should contain "HttpOnly"
    And the Set-Cookie header should contain "Secure"
    And the Set-Cookie header should contain "SameSite=Lax"

  Scenario: Sign-out-all clears cookie with Max-Age=0 and all security attributes
    Given user "cookie-signout-all@test.com" has 2 active sessions
    And I am authenticated on session 1 for user "cookie-signout-all@test.com"
    When POST request is send to "/api/signout/all"
    Then the response status code should be 204
    And the response should have a Set-Cookie header for "__Host-auth_token"
    And the Set-Cookie header should contain "Max-Age=0"
    And the Set-Cookie header should contain "Path=/"
    And the Set-Cookie header should contain "HttpOnly"
    And the Set-Cookie header should contain "Secure"
    And the Set-Cookie header should contain "SameSite=Lax"

  # Cookie value validation

  Scenario: Sign-in cookie value is a valid JWT
    Given user with email "cookie-jwt-val@test.com" and password "passWORD1" exists
    And signing in with email "cookie-jwt-val@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the Set-Cookie header value for "__Host-auth_token" should be a valid JWT

  Scenario: Sign-in cookie JWT matches the access_token in response body
    Given user with email "cookie-jwt-match@test.com" and password "passWORD1" exists
    And signing in with email "cookie-jwt-match@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the "__Host-auth_token" cookie JWT should match the "access_token" in the response body

  # Cookie not set on non-auth endpoints

  Scenario: Token refresh does not set session cookie
    Given user with email "cookie-no-refresh@test.com" and password "passWORD1" exists
    And user "cookie-no-refresh@test.com" has signed in and received tokens
    And submitting the refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 200
    And the response should not have a Set-Cookie header for "__Host-auth_token"

  Scenario: Sign-in with 2FA-enabled user does not set cookie before 2FA
    Given user with email "cookie-no-pending@test.com" and password "passWORD1" exists
    And user with email "cookie-no-pending@test.com" has 2FA enabled
    And signing in with email "cookie-no-pending@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the response should not have a Set-Cookie header for "__Host-auth_token"

  Scenario: Failed sign-in does not set cookie
    Given signing in with email "cookie-no-fail@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401
    And the response should not have a Set-Cookie header for "__Host-auth_token"
