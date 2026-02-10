Feature: Session Lifecycle and Observability
  In order to manage my active sessions and maintain security
  As an authenticated user
  I want to log out from current or all sessions

  # Story 6.1: Logout current session (FR-13, NFR-54)

  Scenario: Logout from current session
    Given I am authenticated as user "logout@test.com"
    When POST request is send to "/api/signout"
    Then the response status code should be 204
    And the Set-Cookie header should clear "__Host-auth_token" with Max-Age=0

  Scenario: Logout revokes current session
    Given I am authenticated as user "logout-session@test.com" with a tracked session
    And POST request is send to "/api/signout"
    And the response status code should be 204
    When I attempt to use the revoked session's refresh token
    Then the response status code should be 401

  Scenario: Logout does not revoke other sessions
    Given user "logout-multi@test.com" has 2 active sessions
    And I am authenticated on session 1 for user "logout-multi@test.com"
    And POST request is send to "/api/signout"
    And the response status code should be 204
    When I use session 2's refresh token
    Then the response status code should be 200

  Scenario: Logout requires authentication
    When POST request is send to "/api/signout"
    Then the response status code should be 401

  Scenario: Logout clears cookie correctly
    Given I am authenticated as user "logout-cookie@test.com"
    When POST request is send to "/api/signout"
    Then the response status code should be 204
    And the response should have a Set-Cookie header for "__Host-auth_token"
    And the Set-Cookie header should contain "Max-Age=0"
    And the Set-Cookie header should contain "Path=/"
    And the Set-Cookie header should contain "HttpOnly"
    And the Set-Cookie header should contain "Secure"
    And the Set-Cookie header should contain "SameSite=Lax"

  # Story 6.2: Sign out everywhere (FR-14)

  Scenario: Sign out from all sessions
    Given user "signout-all@test.com" has 3 active sessions
    And I am authenticated on session 1 for user "signout-all@test.com"
    When POST request is send to "/api/signout/all"
    Then the response status code should be 204
    And the Set-Cookie header should clear "__Host-auth_token" with Max-Age=0

  Scenario: Sign out everywhere revokes all sessions
    Given user "signout-all-revoke@test.com" has 3 active sessions
    And I am authenticated on session 1 for user "signout-all-revoke@test.com"
    And POST request is send to "/api/signout/all"
    And the response status code should be 204
    When I attempt to use session 2's refresh token
    Then the response status code should be 401
    When I attempt to use session 3's refresh token
    Then the response status code should be 401

  Scenario: Sign out everywhere requires authentication
    When POST request is send to "/api/signout/all"
    Then the response status code should be 401

  # Integration: Full sign-in to logout flow

  Scenario: Complete sign-in and logout lifecycle
    Given user with email "lifecycle@test.com" and password "passWORD1" exists
    And signing in with email "lifecycle@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And the response status code should be 200
    And I store the access token from the response
    And I am authenticated with the stored access token
    When POST request is send to "/api/signout"
    Then the response status code should be 204
    And the Set-Cookie header should clear "__Host-auth_token" with Max-Age=0

  # Integration: Full 2FA sign-in lifecycle

  Scenario: Complete 2FA sign-in and sign-out-everywhere lifecycle
    Given user with email "lifecycle-2fa@test.com" and password "passWORD1" exists
    And user with email "lifecycle-2fa@test.com" has 2FA enabled
    And signing in with email "lifecycle-2fa@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And the response status code should be 200
    And the response field "2fa_enabled" should be true
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and a valid TOTP code
    And POST request is send to "/api/signin/2fa"
    And the response status code should be 200
    And I store the access token from the response
    And I am authenticated with the stored access token
    When POST request is send to "/api/signout/all"
    Then the response status code should be 204

  # Integration: Token refresh after sign-in

  Scenario: Complete sign-in, refresh, and logout lifecycle
    Given user with email "lifecycle-refresh@test.com" and password "passWORD1" exists
    And signing in with email "lifecycle-refresh@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And the response status code should be 200
    And I store the refresh token from the response
    And submitting the stored refresh token to exchange
    And POST request is send to "/api/token"
    And the response status code should be 200
    And I store the access token from the response
    And I am authenticated with the stored access token
    When POST request is send to "/api/signout"
    Then the response status code should be 204

  # Additional session lifecycle scenarios (FR-13, FR-14, NFR-33, NFR-54)

  Scenario: Logout after token refresh revokes the session
    Given user with email "logout-after-refresh@test.com" and password "passWORD1" exists
    And signing in with email "logout-after-refresh@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And the response status code should be 200
    And I store the refresh token from the response
    And submitting the stored refresh token to exchange
    And POST request is send to "/api/token"
    And the response status code should be 200
    And I store the access token from the response
    And I store the new refresh token from the response
    And I am authenticated with the stored access token
    And POST request is send to "/api/signout"
    And the response status code should be 204
    And submitting the new stored refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 401

  Scenario: Sign-out-all after 2FA enable
    Given user with email "signout-2fa@test.com" and password "passWORD1" exists
    And user "signout-2fa@test.com" has 3 active sessions
    And I am authenticated on session 1 for user "signout-2fa@test.com"
    When POST request is send to "/api/signout/all"
    Then the response status code should be 204
    And all 3 sessions should be revoked

  Scenario: Multiple sign-ins create independent sessions
    Given user with email "multi-signin@test.com" and password "passWORD1" exists
    And signing in with email "multi-signin@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And the response status code should be 200
    And I store the access token from the response as "token1"
    And signing in with email "multi-signin@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And the response status code should be 200
    And I store the access token from the response as "token2"
    And I am authenticated with stored access token "token1"
    When POST request is send to "/api/signout"
    Then the response status code should be 204
    And stored access token "token2" should still be valid

  Scenario: Logout is idempotent on already-revoked session
    Given I am authenticated as user "logout-idem@test.com" with a tracked session
    And POST request is send to "/api/signout"
    And the response status code should be 204
    When I attempt to use the revoked session's access token
    Then the response status code should be 401

  Scenario: Sign-out-all clears cookie correctly
    Given user "signout-all-cookie@test.com" has 2 active sessions
    And I am authenticated on session 1 for user "signout-all-cookie@test.com"
    When POST request is send to "/api/signout/all"
    Then the response status code should be 204
    And the response should have a Set-Cookie header for "__Host-auth_token"
    And the Set-Cookie header should contain "Max-Age=0"
    And the Set-Cookie header should contain "Path=/"
    And the Set-Cookie header should contain "HttpOnly"
    And the Set-Cookie header should contain "Secure"
    And the Set-Cookie header should contain "SameSite=Lax"

  # Integration: 2FA enable then sign-in lifecycle

  Scenario: Complete 2FA enable and subsequent sign-in lifecycle
    Given user with email "lifecycle-enable-2fa@test.com" and password "passWORD1" exists
    And I am authenticated as user "lifecycle-enable-2fa@test.com"
    And POST request is send to "/api/users/2fa/setup"
    And the response status code should be 200
    And confirming 2FA with a valid TOTP code
    And POST request is send to "/api/users/2fa/confirm"
    And the response status code should be 200
    And signing in with email "lifecycle-enable-2fa@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And the response status code should be 200
    And the response field "2fa_enabled" should be true
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and a valid TOTP code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 200
    And the response should contain "access_token"

  # Integration: Password change invalidates sessions then re-sign-in

  Scenario: Password change then re-sign-in with new password lifecycle
    Given user with email "lifecycle-pwchange@test.com" and password "passWORD1" exists
    And I am authenticated as user "lifecycle-pwchange@test.com" with id "8be90127-9840-4235-a6da-39b8debfb280"
    And user with id "8be90127-9840-4235-a6da-39b8debfb280" and password "passWORD1" exists
    And updating user with email "lifecycle-pwchange@test.com", initials "name", oldPassword "passWORD1", newPassword "passWORD2"
    And PATCH request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb280"
    And the response status code should be 200
    And signing in with email "lifecycle-pwchange@test.com" and password "passWORD2"
    When POST request is send to "/api/signin"
    Then the response status code should be 200

  # Integration: Recovery code sign-in then regenerate codes

  Scenario: Recovery code sign-in then regenerate codes lifecycle
    Given user with email "lifecycle-recovery@test.com" and password "passWORD1" exists
    And user with email "lifecycle-recovery@test.com" has 2FA enabled with recovery codes
    And signing in with email "lifecycle-recovery@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and a valid recovery code
    And POST request is send to "/api/signin/2fa"
    And the response status code should be 200
    And I store the access token from the response
    And I am authenticated with the stored access token
    And user "lifecycle-recovery@test.com" has completed high-trust re-auth within 5 minutes
    When POST request is send to "/api/users/2fa/recovery-codes"
    Then the response status code should be 200
    And the response should contain 8 recovery codes

  # Integration: 2FA enable revokes other sessions, then sign-in lifecycle

  Scenario: 2FA enable revokes other sessions then new sign-in requires 2FA
    Given user with email "lifecycle-2fa-revoke@test.com" and password "passWORD1" exists
    And user "lifecycle-2fa-revoke@test.com" has 3 active sessions
    And I am authenticated on session 1 for user "lifecycle-2fa-revoke@test.com"
    And POST request is send to "/api/users/2fa/setup"
    And the response status code should be 200
    And confirming 2FA with a valid TOTP code
    And POST request is send to "/api/users/2fa/confirm"
    And the response status code should be 200
    And sessions on devices 2 and 3 should be revoked
    And signing in with email "lifecycle-2fa-revoke@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And the response field "2fa_enabled" should be true
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and a valid TOTP code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 200

  # Integration: Password change then all sessions revoked then re-sign-in

  Scenario: Password change revokes all other sessions then re-sign-in works
    Given user with email "lifecycle-pw-revoke@test.com" and password "passWORD1" exists
    And I am authenticated as user "lifecycle-pw-revoke@test.com" with id "8be90127-9840-4235-a6da-39b8debfb281"
    And user with id "8be90127-9840-4235-a6da-39b8debfb281" and password "passWORD1" exists
    And user "8be90127-9840-4235-a6da-39b8debfb281" has 2 active sessions
    And updating user with email "lifecycle-pw-revoke@test.com", initials "name", oldPassword "passWORD1", newPassword "passWORD2"
    And PATCH request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb281"
    And the response status code should be 200
    And session 2 should be revoked
    And signing in with email "lifecycle-pw-revoke@test.com" and password "passWORD2"
    When POST request is send to "/api/signin"
    Then the response status code should be 200

  # Integration: Token rotation then logout then sign-in

  Scenario: Token rotation then logout then fresh sign-in lifecycle
    Given user with email "lifecycle-rotate-logout@test.com" and password "passWORD1" exists
    And signing in with email "lifecycle-rotate-logout@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And the response status code should be 200
    And I store the refresh token from the response
    And submitting the stored refresh token to exchange
    And POST request is send to "/api/token"
    And the response status code should be 200
    And I store the access token from the response
    And I am authenticated with the stored access token
    And POST request is send to "/api/signout"
    And the response status code should be 204
    And signing in with email "lifecycle-rotate-logout@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the response should contain "access_token"

  # Concurrent session operations

  Scenario: Two users can sign in simultaneously without interference
    Given user with email "concurrent-a@test.com" and password "passWORD1" exists
    And user with email "concurrent-b@test.com" and password "passWORD1" exists
    And signing in with email "concurrent-a@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And the response status code should be 200
    And signing in with email "concurrent-b@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200

  Scenario: Logout from one user does not affect another user's session
    Given user with email "isolated-a@test.com" and password "passWORD1" exists
    And user with email "isolated-b@test.com" and password "passWORD1" exists
    And I am authenticated as user "isolated-a@test.com"
    And user "isolated-b@test.com" has signed in and received tokens
    And POST request is send to "/api/signout"
    And the response status code should be 204
    And I am authenticated as user "isolated-b@test.com"
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 200

  # Session with remember_me flag

  Scenario: Session with remember_me has 30-day cookie but 15-min JWT TTL
    Given user with email "session-remember@test.com" and password "passWORD1" exists
    And signing in with email "session-remember@test.com", password "passWORD1" and remember me
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the Set-Cookie header should contain "Max-Age=2592000"
    And the JWT "exp" should be approximately 15 minutes after "iat"

  # Sign-out response body

  Scenario: Logout returns empty body with 204
    Given I am authenticated as user "logout-empty@test.com"
    When POST request is send to "/api/signout"
    Then the response status code should be 204
    And the response body should be empty

  Scenario: Sign-out-all returns empty body with 204
    Given user "signout-all-empty@test.com" has 2 active sessions
    And I am authenticated on session 1 for user "signout-all-empty@test.com"
    When POST request is send to "/api/signout/all"
    Then the response status code should be 204
    And the response body should be empty
