Feature: Two-Factor Authentication Management
  In order to protect my account with an additional security layer
  As a registered user
  I want to set up, confirm, disable, and use TOTP-based 2FA

  # Story 2.1: Complete 2FA sign-in with TOTP (FR-03, NFR-07)

  Scenario: Complete 2FA sign-in with valid TOTP code
    Given user with email "2fa-complete@test.com" and password "passWORD1" exists
    And user with email "2fa-complete@test.com" has 2FA enabled
    And signing in with email "2fa-complete@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and a valid TOTP code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 200
    And the response should contain "access_token"
    And the response should contain "refresh_token"
    And the response field "2fa_enabled" should be true
    And the response should have a Set-Cookie header for "__Host-auth_token"

  Scenario: Complete 2FA sign-in with invalid TOTP code
    Given user with email "2fa-bad-code@test.com" and password "passWORD1" exists
    And user with email "2fa-bad-code@test.com" has 2FA enabled
    And signing in with email "2fa-bad-code@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and code "000000"
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 401
    And the error message should be "Invalid two-factor code"

  Scenario: Pending session remains valid after wrong TOTP code
    Given user with email "2fa-retry@test.com" and password "passWORD1" exists
    And user with email "2fa-retry@test.com" has 2FA enabled
    And signing in with email "2fa-retry@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and code "000000"
    And POST request is send to "/api/signin/2fa"
    And the response status code should be 401
    And completing 2FA with the stored pending_session_id and a valid TOTP code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 200
    And the response should contain "access_token"

  Scenario: Complete 2FA with expired pending session
    Given user with email "2fa-expired@test.com" and password "passWORD1" exists
    And user with email "2fa-expired@test.com" has 2FA enabled
    And an expired pending session exists for user "2fa-expired@test.com"
    And completing 2FA with the expired pending_session_id and a valid TOTP code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 401

  Scenario: Complete 2FA with invalid pending session ID
    Given completing 2FA with pending_session_id "invalid-session-id" and code "123456"
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 401

  Scenario: Complete 2FA with empty body
    Given sending empty body
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 422
    And violation should be "This value should not be blank."

  # Story 2.2: 2FA setup (FR-07, NFR-16)

  Scenario: Set up 2FA for authenticated user
    Given I am authenticated as user "2fa-setup@test.com"
    When POST request is send to "/api/users/2fa/setup"
    Then the response status code should be 200
    And the response should contain "otpauth_uri"
    And the response should contain "secret"
    And the otpauth_uri should contain "VilnaCRM"
    And the otpauth_uri should contain "2fa-setup@test.com"

  Scenario: 2FA setup does not enable 2FA immediately
    Given I am authenticated as user "2fa-setup-pending@test.com"
    And POST request is send to "/api/users/2fa/setup"
    And the response status code should be 200
    When GET request is send to the current user endpoint
    Then the user should have "twoFactorEnabled" set to false

  Scenario: 2FA setup requires authentication
    When POST request is send to "/api/users/2fa/setup"
    Then the response status code should be 401

  # Story 2.3: 2FA confirmation with recovery codes (FR-08, FR-16, FR-20, NFR-42, NFR-52)

  Scenario: Confirm 2FA setup with valid TOTP code
    Given I am authenticated as user "2fa-confirm@test.com"
    And I have completed 2FA setup
    And confirming 2FA with a valid TOTP code
    When POST request is send to "/api/users/2fa/confirm"
    Then the response status code should be 200
    And the response should contain "recovery_codes"
    And the response should contain 8 recovery codes
    And each recovery code should match the format "xxxx-xxxx"

  Scenario: Confirm 2FA with invalid TOTP code
    Given I am authenticated as user "2fa-confirm-bad@test.com"
    And I have completed 2FA setup
    And confirming 2FA with code "000000"
    When POST request is send to "/api/users/2fa/confirm"
    Then the response status code should be 401
    And the user should have "twoFactorEnabled" set to false

  Scenario: 2FA confirmation revokes other sessions
    Given user "2fa-multisession@test.com" has 3 active sessions
    And I am authenticated as user "2fa-multisession@test.com" on device 1
    And I have completed 2FA setup
    And confirming 2FA with a valid TOTP code
    When POST request is send to "/api/users/2fa/confirm"
    Then the response status code should be 200
    And sessions on devices 2 and 3 should be revoked

  Scenario: 2FA confirmation requires authentication
    Given confirming 2FA with code "123456"
    When POST request is send to "/api/users/2fa/confirm"
    Then the response status code should be 401

  # Story 2.4: 2FA disable (FR-15)

  Scenario: Disable 2FA with valid TOTP code
    Given I am authenticated as user "2fa-disable@test.com"
    And user "2fa-disable@test.com" has 2FA enabled
    And disabling 2FA with a valid TOTP code
    When POST request is send to "/api/users/2fa/disable"
    Then the response status code should be 204
    And the user should have "twoFactorEnabled" set to false

  Scenario: Disable 2FA with valid recovery code
    Given I am authenticated as user "2fa-disable-recovery@test.com"
    And user "2fa-disable-recovery@test.com" has 2FA enabled with recovery codes
    And disabling 2FA with a valid recovery code
    When POST request is send to "/api/users/2fa/disable"
    Then the response status code should be 204
    And the user should have "twoFactorEnabled" set to false

  Scenario: Disable 2FA with invalid code
    Given I am authenticated as user "2fa-disable-bad@test.com"
    And user "2fa-disable-bad@test.com" has 2FA enabled
    And disabling 2FA with code "000000"
    When POST request is send to "/api/users/2fa/disable"
    Then the response status code should be 401
    And the user should have "twoFactorEnabled" set to true

  Scenario: Disable 2FA when not enabled
    Given I am authenticated as user "2fa-disable-none@test.com"
    And user "2fa-disable-none@test.com" does not have 2FA enabled
    And disabling 2FA with code "123456"
    When POST request is send to "/api/users/2fa/disable"
    Then the response status code should be 403

  Scenario: Disable 2FA invalidates all recovery codes
    Given I am authenticated as user "2fa-disable-codes@test.com"
    And user "2fa-disable-codes@test.com" has 2FA enabled with recovery codes
    And disabling 2FA with a valid TOTP code
    And POST request is send to "/api/users/2fa/disable"
    And the response status code should be 204
    Then all recovery codes for user "2fa-disable-codes@test.com" should be invalidated

  Scenario: Disable 2FA requires authentication
    Given disabling 2FA with code "123456"
    When POST request is send to "/api/users/2fa/disable"
    Then the response status code should be 401

  # Story 2.5: Complete 2FA sign-in with recovery code (FR-17, NFR-68)

  Scenario: Complete 2FA sign-in with valid recovery code
    Given user with email "2fa-recovery@test.com" and password "passWORD1" exists
    And user with email "2fa-recovery@test.com" has 2FA enabled with recovery codes
    And signing in with email "2fa-recovery@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and a valid recovery code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 200
    And the response should contain "access_token"
    And the response should contain "refresh_token"
    And the response field "2fa_enabled" should be true

  Scenario: Recovery code cannot be reused
    Given user with email "2fa-reuse@test.com" and password "passWORD1" exists
    And user with email "2fa-reuse@test.com" has 2FA enabled with recovery codes
    And signing in with email "2fa-reuse@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and a valid recovery code
    And POST request is send to "/api/signin/2fa"
    And the response status code should be 200
    And signing in with email "2fa-reuse@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and the same recovery code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 401

  Scenario: Recovery code exhaustion warning when remaining codes are low
    Given user with email "2fa-exhaustion@test.com" and password "passWORD1" exists
    And user with email "2fa-exhaustion@test.com" has 2FA enabled with recovery codes
    And 6 of 8 recovery codes for user "2fa-exhaustion@test.com" have been used
    And signing in with email "2fa-exhaustion@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and a valid recovery code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 200
    And the response should contain "recovery_codes_remaining"
    And the response field "recovery_codes_remaining" should be 1

  # Story 2.6: Regenerate recovery codes (FR-18)

  Scenario: Regenerate recovery codes with recent high-trust auth
    Given I am authenticated as user "2fa-regen@test.com"
    And user "2fa-regen@test.com" has 2FA enabled
    And user "2fa-regen@test.com" has completed high-trust re-auth within 5 minutes
    When POST request is send to "/api/users/2fa/recovery-codes"
    Then the response status code should be 200
    And the response should contain "recovery_codes"
    And the response should contain 8 recovery codes
    And all previous recovery codes should be invalidated

  Scenario: Regenerate recovery codes without recent high-trust auth
    Given I am authenticated as user "2fa-regen-noauth@test.com"
    And user "2fa-regen-noauth@test.com" has 2FA enabled
    And user "2fa-regen-noauth@test.com" has not completed high-trust re-auth recently
    When POST request is send to "/api/users/2fa/recovery-codes"
    Then the response status code should be 403
    And the response should indicate sudo mode is required

  Scenario: Regenerate recovery codes without 2FA enabled
    Given I am authenticated as user "2fa-regen-no2fa@test.com"
    And user "2fa-regen-no2fa@test.com" does not have 2FA enabled
    When POST request is send to "/api/users/2fa/recovery-codes"
    Then the response status code should be 403

  Scenario: Regenerate recovery codes requires authentication
    When POST request is send to "/api/users/2fa/recovery-codes"
    Then the response status code should be 401

  # Additional 2FA sign-in edge cases (FR-03, NFR-07, NFR-50)

  Scenario: 2FA sign-in response includes Set-Cookie with correct attributes
    Given user with email "2fa-cookie@test.com" and password "passWORD1" exists
    And user with email "2fa-cookie@test.com" has 2FA enabled
    And signing in with email "2fa-cookie@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and a valid TOTP code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 200
    And the Set-Cookie header should contain "HttpOnly"
    And the Set-Cookie header should contain "Secure"
    And the Set-Cookie header should contain "SameSite=Lax"
    And the Set-Cookie header should contain "Path=/"
    And the Set-Cookie header should not contain "Domain="

  Scenario: 2FA sign-in JWT contains all required claims
    Given user with email "2fa-jwt-claims@test.com" and password "passWORD1" exists
    And user with email "2fa-jwt-claims@test.com" has 2FA enabled
    And signing in with email "2fa-jwt-claims@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and a valid TOTP code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 200
    And the access token should be a valid JWT signed with RS256
    And the JWT should contain claim "sub"
    And the JWT should contain claim "iss" with value "vilnacrm-user-service"
    And the JWT should contain claim "aud" with value "vilnacrm-api"
    And the JWT should contain claim "sid"
    And the JWT should contain claim "roles"

  Scenario: 2FA sign-in with missing pending_session_id field
    Given completing 2FA with no pending_session_id and code "123456"
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 422
    And violation should be "This value should not be blank."

  Scenario: 2FA sign-in with missing code field
    Given completing 2FA with pending_session_id "some-session" and no code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 422
    And violation should be "This value should not be blank."

  Scenario: 2FA sign-in response does not expose TOTP secret
    Given user with email "2fa-no-secret@test.com" and password "passWORD1" exists
    And user with email "2fa-no-secret@test.com" has 2FA enabled
    And signing in with email "2fa-no-secret@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and a valid TOTP code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 200
    And the response should not contain "twoFactorSecret"
    And the response should not contain "secret"

  # Additional 2FA setup edge cases (FR-07)

  Scenario: 2FA setup when 2FA is already enabled
    Given I am authenticated as user "2fa-setup-already@test.com"
    And user "2fa-setup-already@test.com" has 2FA enabled
    When POST request is send to "/api/users/2fa/setup"
    Then the response status code should be 200
    And the response should contain "otpauth_uri"
    And the response should contain "secret"

  Scenario: 2FA setup secret is not returned in subsequent GET requests
    Given I am authenticated as user "2fa-secret-hidden@test.com"
    And POST request is send to "/api/users/2fa/setup"
    And the response status code should be 200
    When GET request is send to the current user endpoint
    Then the response should not contain "twoFactorSecret"
    And the response should not contain "secret"

  # Additional 2FA confirm edge cases (FR-08, FR-16, NFR-42)

  Scenario: 2FA confirm without prior setup fails
    Given I am authenticated as user "2fa-confirm-nosetup@test.com"
    And user "2fa-confirm-nosetup@test.com" has not completed 2FA setup
    And confirming 2FA with code "123456"
    When POST request is send to "/api/users/2fa/confirm"
    Then the response status code should be 400

  Scenario: 2FA confirm with empty code
    Given I am authenticated as user "2fa-confirm-empty@test.com"
    And I have completed 2FA setup
    And confirming 2FA with code ""
    When POST request is send to "/api/users/2fa/confirm"
    Then the response status code should be 422
    And violation should be "This value should not be blank."

  Scenario: Confirmed recovery codes are exactly 8 unique codes
    Given I am authenticated as user "2fa-unique-codes@test.com"
    And I have completed 2FA setup
    And confirming 2FA with a valid TOTP code
    When POST request is send to "/api/users/2fa/confirm"
    Then the response status code should be 200
    And the response should contain 8 recovery codes
    And all 8 recovery codes should be unique

  Scenario: 2FA confirmation current session remains valid
    Given I am authenticated as user "2fa-confirm-session@test.com"
    And I have completed 2FA setup
    And confirming 2FA with a valid TOTP code
    And POST request is send to "/api/users/2fa/confirm"
    And the response status code should be 200
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 200

  # Additional recovery code edge cases (FR-17, NFR-68)

  Scenario: Recovery code exhaustion warning at zero remaining
    Given user with email "2fa-zero-codes@test.com" and password "passWORD1" exists
    And user with email "2fa-zero-codes@test.com" has 2FA enabled with recovery codes
    And 7 of 8 recovery codes for user "2fa-zero-codes@test.com" have been used
    And signing in with email "2fa-zero-codes@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and the last valid recovery code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 200
    And the response should contain "recovery_codes_remaining"
    And the response field "recovery_codes_remaining" should be 0

  Scenario: All recovery codes exhausted still allows TOTP sign-in
    Given user with email "2fa-all-used@test.com" and password "passWORD1" exists
    And user with email "2fa-all-used@test.com" has 2FA enabled with recovery codes
    And all 8 recovery codes for user "2fa-all-used@test.com" have been used
    And signing in with email "2fa-all-used@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and a valid TOTP code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 200

  Scenario: Recovery code with invalid format is treated as TOTP
    Given user with email "2fa-format@test.com" and password "passWORD1" exists
    And user with email "2fa-format@test.com" has 2FA enabled
    And signing in with email "2fa-format@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and code "not-valid"
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 401

  # Additional 2FA disable edge cases (FR-15)

  Scenario: Disable 2FA clears twoFactorSecret
    Given I am authenticated as user "2fa-disable-secret@test.com"
    And user "2fa-disable-secret@test.com" has 2FA enabled
    And disabling 2FA with a valid TOTP code
    And POST request is send to "/api/users/2fa/disable"
    And the response status code should be 204
    And signing in with email "2fa-disable-secret@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the response field "2fa_enabled" should be false
    And the response should not contain "pending_session_id"

  Scenario: After 2FA disable, sign-in proceeds without 2FA step
    Given user with email "2fa-disable-flow@test.com" and password "passWORD1" exists
    And user with email "2fa-disable-flow@test.com" has 2FA enabled
    And I am authenticated as user "2fa-disable-flow@test.com"
    And disabling 2FA with a valid TOTP code
    And POST request is send to "/api/users/2fa/disable"
    And the response status code should be 204
    And signing in with email "2fa-disable-flow@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And the response field "2fa_enabled" should be false
    And the response should contain "access_token"

  # Additional regenerate recovery codes edge cases (FR-18)

  Scenario: Regenerated codes differ from previous codes
    Given I am authenticated as user "2fa-regen-diff@test.com"
    And user "2fa-regen-diff@test.com" has 2FA enabled with recovery codes
    And I store the current recovery codes
    And user "2fa-regen-diff@test.com" has completed high-trust re-auth within 5 minutes
    When POST request is send to "/api/users/2fa/recovery-codes"
    Then the response status code should be 200
    And the new recovery codes should differ from the stored ones

  Scenario: Previous recovery codes invalid after regeneration
    Given I am authenticated as user "2fa-regen-invalid@test.com"
    And user "2fa-regen-invalid@test.com" has 2FA enabled with recovery codes
    And I store a valid recovery code
    And user "2fa-regen-invalid@test.com" has completed high-trust re-auth within 5 minutes
    And POST request is send to "/api/users/2fa/recovery-codes"
    And the response status code should be 200
    And signing in with email "2fa-regen-invalid@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and the previously stored recovery code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 401

  # Recovery code format and case-sensitivity (ADR-05, NFR-42, NFR-68)

  Scenario: Recovery code is case-insensitive
    Given user with email "2fa-case@test.com" and password "passWORD1" exists
    And user with email "2fa-case@test.com" has 2FA enabled with recovery codes
    And I store a valid recovery code in uppercase
    And signing in with email "2fa-case@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and the uppercase recovery code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 200

  Scenario: Recovery code format is xxxx-xxxx
    Given I am authenticated as user "2fa-code-format@test.com"
    And I have completed 2FA setup
    And confirming 2FA with a valid TOTP code
    When POST request is send to "/api/users/2fa/confirm"
    Then the response status code should be 200
    And each recovery code should match the format "xxxx-xxxx"
    And each recovery code should be 9 characters long

  Scenario: Recovery codes contain only alphanumeric characters and hyphen
    Given I am authenticated as user "2fa-code-chars@test.com"
    And I have completed 2FA setup
    And confirming 2FA with a valid TOTP code
    When POST request is send to "/api/users/2fa/confirm"
    Then the response status code should be 200
    And each recovery code should match pattern "[a-z0-9]{4}-[a-z0-9]{4}"

  # Pending session consumption (FR-02, FR-03)

  Scenario: Pending session is consumed after successful 2FA completion
    Given user with email "2fa-consumed@test.com" and password "passWORD1" exists
    And user with email "2fa-consumed@test.com" has 2FA enabled
    And signing in with email "2fa-consumed@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and a valid TOTP code
    And POST request is send to "/api/signin/2fa"
    And the response status code should be 200
    And completing 2FA with the stored pending_session_id and a valid TOTP code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 401

  Scenario: Multiple pending sessions can exist for the same user
    Given user with email "2fa-multi-pending@test.com" and password "passWORD1" exists
    And user with email "2fa-multi-pending@test.com" has 2FA enabled
    And signing in with email "2fa-multi-pending@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response as "session1"
    And signing in with email "2fa-multi-pending@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response as "session2"
    And completing 2FA with stored pending_session_id "session2" and a valid TOTP code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 200

  Scenario: Pending session created before first one is still valid
    Given user with email "2fa-both-pending@test.com" and password "passWORD1" exists
    And user with email "2fa-both-pending@test.com" has 2FA enabled
    And signing in with email "2fa-both-pending@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response as "session1"
    And signing in with email "2fa-both-pending@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response as "session2"
    And completing 2FA with stored pending_session_id "session1" and a valid TOTP code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 200

  # 2FA setup idempotency edge case (FR-07)

  Scenario: Second 2FA setup overwrites pending secret
    Given I am authenticated as user "2fa-setup-twice@test.com"
    And POST request is send to "/api/users/2fa/setup"
    And the response status code should be 200
    And I store the setup secret from the response as "secret1"
    And POST request is send to "/api/users/2fa/setup"
    And the response status code should be 200
    And I store the setup secret from the response as "secret2"
    Then stored "secret1" should differ from stored "secret2"

  # 2FA timing safety (NFR-01)

  Scenario: 2FA sign-in with invalid code takes similar time to valid code
    Given user with email "2fa-timing@test.com" and password "passWORD1" exists
    And user with email "2fa-timing@test.com" has 2FA enabled
    And signing in with email "2fa-timing@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and code "000000"
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 401
    And the response time should not reveal code validity

  # 2FA with account lockout integration (ADR-10)

  Scenario: Failed 2FA attempts count toward lockout counter
    Given user with email "2fa-lockout-count@test.com" and password "passWORD1" exists
    And user with email "2fa-lockout-count@test.com" has 2FA enabled
    And 19 failed 2FA attempts have been recorded for email "2fa-lockout-count@test.com"
    And signing in with email "2fa-lockout-count@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and code "000000"
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 401

  # 2FA recovery code remaining count in response (NFR-68)

  Scenario: Successful TOTP 2FA does not include recovery_codes_remaining
    Given user with email "2fa-totp-no-remaining@test.com" and password "passWORD1" exists
    And user with email "2fa-totp-no-remaining@test.com" has 2FA enabled
    And signing in with email "2fa-totp-no-remaining@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and a valid TOTP code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 200
    And the response should not contain "recovery_codes_remaining"

  Scenario: Recovery code 2FA always includes recovery_codes_remaining
    Given user with email "2fa-recovery-count@test.com" and password "passWORD1" exists
    And user with email "2fa-recovery-count@test.com" has 2FA enabled with recovery codes
    And signing in with email "2fa-recovery-count@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and a valid recovery code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 200
    And the response should contain "recovery_codes_remaining"
