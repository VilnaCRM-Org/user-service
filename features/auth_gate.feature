Feature: Authentication Gate and Access Control
  In order to protect API resources from unauthorized access
  As the system
  I want to enforce authentication on all protected endpoints

  # Story 4.1: Symfony security firewall (FR-09, NFR-04, NFR-56)

  Scenario: Unauthenticated request to protected endpoint returns 401
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401
    And the response should be RFC 7807 problem+json
    And the response should have header "WWW-Authenticate" with value "Bearer"

  Scenario: Unauthenticated GET single user returns 401
    When GET request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb220"
    Then the response status code should be 401
    And the response should have header "WWW-Authenticate" with value "Bearer"

  Scenario: Unauthenticated PATCH user returns 401
    Given updating user with email "test@mail.com", initials "name", oldPassword "passWORD1", newPassword "passWORD2"
    When PATCH request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb220"
    Then the response status code should be 401

  Scenario: Unauthenticated DELETE user returns 401
    When DELETE request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb220"
    Then the response status code should be 401

  Scenario: Unauthenticated resend confirmation email returns 401
    When POST request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb220/resend-confirmation-email"
    Then the response status code should be 401

  Scenario: Authenticated request to protected endpoint succeeds
    Given I am authenticated as user "auth-gate@test.com"
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 200

  Scenario: Authenticated request via session cookie succeeds
    Given I am authenticated via session cookie as user "cookie-auth@test.com"
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

  Scenario: Expired JWT is rejected
    Given I have an expired JWT
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401

  # Story 4.2: Access control with public allowlist (FR-09, FR-10, FR-11)

  Scenario: Registration endpoint is publicly accessible
    Given creating user with email "public-reg@test.com", initials "name surname", password "passWORD1"
    When POST request is send to "/api/users"
    Then the response status code should not be 401

  Scenario: Email confirmation endpoint is publicly accessible
    Given confirming user with token "some-token"
    When PATCH request is send to "/api/users/confirm"
    Then the response status code should not be 401

  Scenario: Sign-in endpoint is publicly accessible
    Given signing in with email "public-signin@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should not be 401

  Scenario: 2FA completion endpoint is publicly accessible
    Given completing 2FA with pending_session_id "some-session" and code "123456"
    When POST request is send to "/api/signin/2fa"
    Then the response status code should not be 401

  Scenario: Token refresh endpoint is publicly accessible
    Given submitting refresh token "some-token"
    When POST request is send to "/api/token"
    Then the response status code should not be 401

  Scenario: Password reset endpoint is publicly accessible
    Given requesting password reset for email "reset-public@test.com"
    When POST request is send to "/api/reset-password"
    Then the response status code should not be 401

  Scenario: Health check endpoint is publicly accessible
    When GET request is send to "/api/health"
    Then the response status code should not be 401

  Scenario: API docs endpoint is publicly accessible
    When GET request is send to "/api/docs"
    Then the response status code should not be 401

  Scenario: Batch endpoint requires ROLE_SERVICE
    Given I am authenticated as user "batch-user@test.com" with role "ROLE_USER"
    And sending a batch of users
    And with user with email "batch-forbidden@mail.com", initials "name surname", password "passWORD1"
    When POST request is send to "/api/users/batch"
    Then the response status code should be 403

  Scenario: Batch endpoint succeeds with ROLE_SERVICE
    Given I am authenticated with role "ROLE_SERVICE"
    And sending a batch of users
    And with user with email "batch-service@mail.com", initials "name surname", password "passWORD1"
    When POST request is send to "/api/users/batch"
    Then the response status code should be 201

  # Story 4.3: Ownership enforcement (FR-12, DR-01)

  Scenario: User can PATCH their own resource
    Given I am authenticated as user "owner-patch@test.com" with id "8be90127-9840-4235-a6da-39b8debfb230"
    And user with id "8be90127-9840-4235-a6da-39b8debfb230" and password "passWORD1" exists
    And updating user with email "new-owner@test.com", initials "name", oldPassword "passWORD1", newPassword "passWORD2"
    When PATCH request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb230"
    Then the response status code should be 200

  Scenario: User cannot PATCH another user's resource
    Given I am authenticated as user "attacker-patch@test.com" with id "8be90127-9840-4235-a6da-39b8debfb231"
    And user with id "8be90127-9840-4235-a6da-39b8debfb232" exists
    And updating user with email "hacked@test.com", initials "hacked", oldPassword "passWORD1", newPassword "passWORD2"
    When PATCH request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb232"
    Then the response status code should be 403

  Scenario: User can DELETE their own resource
    Given I am authenticated as user "owner-delete@test.com" with id "8be90127-9840-4235-a6da-39b8debfb233"
    And user with id "8be90127-9840-4235-a6da-39b8debfb233" exists
    When DELETE request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb233"
    Then the response status code should be 204

  Scenario: User cannot DELETE another user's resource
    Given I am authenticated as user "attacker-delete@test.com" with id "8be90127-9840-4235-a6da-39b8debfb234"
    And user with id "8be90127-9840-4235-a6da-39b8debfb235" exists
    When DELETE request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb235"
    Then the response status code should be 403

  Scenario: User cannot resend confirmation email for another user
    Given I am authenticated as user "attacker-resend@test.com" with id "8be90127-9840-4235-a6da-39b8debfb236"
    And user with id "8be90127-9840-4235-a6da-39b8debfb237" exists
    When POST request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb237/resend-confirmation-email"
    Then the response status code should be 403

  # Story 4.3: GraphQL ownership enforcement

  Scenario: User cannot update another user via GraphQL
    Given I am authenticated as user "gql-attacker@test.com" with id "8be90127-9840-4235-a6da-39b8debfb238"
    And user with id "8be90127-9840-4235-a6da-39b8debfb239" exists
    When I execute GraphQL mutation updateUser for user "8be90127-9840-4235-a6da-39b8debfb239"
    Then the GraphQL response should contain an authorization error

  Scenario: User cannot delete another user via GraphQL
    Given I am authenticated as user "gql-del-attacker@test.com" with id "8be90127-9840-4235-a6da-39b8debfb240"
    And user with id "8be90127-9840-4235-a6da-39b8debfb241" exists
    When I execute GraphQL mutation deleteUser for user "8be90127-9840-4235-a6da-39b8debfb241"
    Then the GraphQL response should contain an authorization error

  Scenario: User cannot resend email for another user via GraphQL
    Given I am authenticated as user "gql-resend-attacker@test.com" with id "8be90127-9840-4235-a6da-39b8debfb242"
    And user with id "8be90127-9840-4235-a6da-39b8debfb243" exists
    When I execute GraphQL mutation resendEmailTo for user "8be90127-9840-4235-a6da-39b8debfb243"
    Then the GraphQL response should contain an authorization error

  # Story 4.4: Disable OAuth password grant (NFR-41)

  Scenario: Password grant type is rejected
    Given client with id "PwGrantId", secret "PwGrantSecret" and redirect uri "https://example.com" exists
    And user with email "pwgrant@mail.com" and password "pass" exists
    And passing client id "PwGrantId", client secret "PwGrantSecret", email "pwgrant@mail.com" and password "pass"
    When obtaining access token with "password" grant-type
    Then unsupported grant type error should be returned

  Scenario: Client credentials grant still works after password grant disabled
    Given client with id "StillWorksId", secret "StillWorksSecret" and redirect uri "https://example.com" exists
    And passing client id "StillWorksId" and client secret "StillWorksSecret"
    When obtaining access token with "client_credentials" grant-type
    Then access token should be provided

  # Story 4.5: Password change invalidates other sessions (FR-19, NFR-31)

  Scenario: Password change revokes other sessions
    Given user with id "8be90127-9840-4235-a6da-39b8debfb250" and password "passWORD1" exists
    And user "8be90127-9840-4235-a6da-39b8debfb250" has 2 active sessions
    And I am authenticated on session 1 for user "8be90127-9840-4235-a6da-39b8debfb250"
    And updating user with email "pwchange@test.com", initials "name", oldPassword "passWORD1", newPassword "passWORD2"
    When PATCH request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb250"
    Then the response status code should be 200
    And session 1 should remain valid
    And session 2 should be revoked
    And session 2 refresh tokens should be revoked

  # Additional auth gate scenarios (FR-09, FR-10, FR-12, NFR-04, NFR-56)

  Scenario: Unauthenticated PUT user returns 401
    Given updating user with email "test@mail.com", initials "name", oldPassword "passWORD1", newPassword "passWORD2"
    When PUT request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb220"
    Then the response status code should be 401

  Scenario: User can PUT their own resource
    Given I am authenticated as user "owner-put@test.com" with id "8be90127-9840-4235-a6da-39b8debfb244"
    And user with id "8be90127-9840-4235-a6da-39b8debfb244" and password "passWORD1" exists
    And updating user with email "new-owner-put@test.com", initials "name surname", oldPassword "passWORD1", newPassword "passWORD2"
    When PUT request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb244"
    Then the response status code should be 200

  Scenario: User cannot PUT another user's resource
    Given I am authenticated as user "attacker-put@test.com" with id "8be90127-9840-4235-a6da-39b8debfb245"
    And user with id "8be90127-9840-4235-a6da-39b8debfb246" exists
    And updating user with email "hacked-put@test.com", initials "hacked", oldPassword "passWORD1", newPassword "passWORD2"
    When PUT request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb246"
    Then the response status code should be 403

  Scenario: Unauthenticated signout returns 401
    When POST request is send to "/api/signout"
    Then the response status code should be 401

  Scenario: Unauthenticated signout all returns 401
    When POST request is send to "/api/signout/all"
    Then the response status code should be 401

  Scenario: Unauthenticated 2FA setup returns 401
    When POST request is send to "/api/users/2fa/setup"
    Then the response status code should be 401

  Scenario: Unauthenticated 2FA confirm returns 401
    Given confirming 2FA with code "123456"
    When POST request is send to "/api/users/2fa/confirm"
    Then the response status code should be 401

  Scenario: Unauthenticated 2FA disable returns 401
    Given disabling 2FA with code "123456"
    When POST request is send to "/api/users/2fa/disable"
    Then the response status code should be 401

  Scenario: Unauthenticated recovery codes endpoint returns 401
    When POST request is send to "/api/users/2fa/recovery-codes"
    Then the response status code should be 401

  Scenario: Password reset confirm endpoint is publicly accessible
    Given requesting password reset confirm with token "some-token" and password "passWORD1"
    When POST request is send to "/api/reset-password/confirm"
    Then the response status code should not be 401

  Scenario: Unauthenticated GraphQL query returns 401
    When I send a GraphQL query for user collection
    Then the response status code should be 401

  Scenario: Authenticated GraphQL query succeeds
    Given I am authenticated as user "gql-auth@test.com"
    When I send a GraphQL query for user collection
    Then the response status code should be 200

  Scenario: JWT with future nbf claim is rejected
    Given I have a JWT with nbf set to 1 hour in the future
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401

  Scenario: GET single user requires authentication
    When GET request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb220"
    Then the response status code should be 401

  Scenario: Authenticated user can GET single user
    Given I am authenticated as user "get-user@test.com"
    And user with id "8be90127-9840-4235-a6da-39b8debfb247" exists
    When GET request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb247"
    Then the response status code should be 200

  # Password change without newPassword does not revoke sessions (FR-19)

  Scenario: Update without password change does not revoke other sessions
    Given user with id "8be90127-9840-4235-a6da-39b8debfb251" and password "passWORD1" exists
    And user "8be90127-9840-4235-a6da-39b8debfb251" has 2 active sessions
    And I am authenticated on session 1 for user "8be90127-9840-4235-a6da-39b8debfb251"
    And updating user with email "nopwchange@test.com", initials "name", oldPassword "passWORD1"
    When PATCH request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb251"
    Then the response status code should be 200
    And session 2 should remain valid

  # Password change session revocation keeps current session (FR-19, NFR-31)

  Scenario: Password change keeps current session valid
    Given user with id "8be90127-9840-4235-a6da-39b8debfb252" and password "passWORD1" exists
    And user "8be90127-9840-4235-a6da-39b8debfb252" has 3 active sessions
    And I am authenticated on session 1 for user "8be90127-9840-4235-a6da-39b8debfb252"
    And updating user with email "pwkeep@test.com", initials "name", oldPassword "passWORD1", newPassword "passWORD2"
    When PATCH request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb252"
    Then the response status code should be 200
    And session 1 should remain valid
    And session 2 should be revoked
    And session 3 should be revoked

  # 2FA enable session revocation keeps current session (FR-08)

  Scenario: 2FA enable keeps current session valid
    Given user "2fa-keep-session@test.com" has 3 active sessions
    And I am authenticated as user "2fa-keep-session@test.com" on device 1
    And I have completed 2FA setup
    And confirming 2FA with a valid TOTP code
    When POST request is send to "/api/users/2fa/confirm"
    Then the response status code should be 200
    And sessions on devices 2 and 3 should be revoked
    And the current session should remain valid

  # GraphQL public endpoints (NFR-62)

  Scenario: GraphQL endpoint requires authentication
    When I send a GraphQL query for user collection
    Then the response status code should be 401

  # User can read their own details

  Scenario: User can GET their own resource
    Given I am authenticated as user "own-get@test.com" with id "8be90127-9840-4235-a6da-39b8debfb253"
    And user with id "8be90127-9840-4235-a6da-39b8debfb253" exists
    When GET request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb253"
    Then the response status code should be 200

  # Access control on 2FA endpoints (FR-07, FR-08, FR-15, FR-18)

  Scenario: User can only access their own 2FA setup
    Given I am authenticated as user "2fa-own@test.com"
    When POST request is send to "/api/users/2fa/setup"
    Then the response status code should be 200

  Scenario: User can only access their own recovery codes
    Given I am authenticated as user "recovery-own@test.com"
    And user "recovery-own@test.com" has 2FA enabled
    And user "recovery-own@test.com" has completed high-trust re-auth within 5 minutes
    When POST request is send to "/api/users/2fa/recovery-codes"
    Then the response status code should be 200
