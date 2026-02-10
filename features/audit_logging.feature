Feature: Audit Logging for Authentication Events
  In order to investigate security incidents
  As the system
  I want all authentication events logged with structured data

  # Story 6.3: Audit logging (NFR-33, NFR-34)

  Scenario: Successful sign-in emits audit log
    Given user with email "audit-signin@test.com" and password "passWORD1" exists
    And signing in with email "audit-signin@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And an INFO-level audit log should be emitted for "UserSignedIn"
    And the audit log should contain "userId"
    And the audit log should contain "ip"
    And the audit log should contain "userAgent"

  Scenario: Failed sign-in emits audit log
    Given user with email "audit-fail@test.com" and password "passWORD1" exists
    And signing in with email "audit-fail@test.com" and password "wrongPassword1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401
    And a WARNING-level audit log should be emitted for "SignInFailed"
    And the audit log should contain "attemptedEmail"
    And the audit log should contain "ip"
    And the audit log should contain "reason"

  Scenario: 2FA completion emits audit log
    Given user with email "audit-2fa@test.com" and password "passWORD1" exists
    And user with email "audit-2fa@test.com" has 2FA enabled
    And signing in with email "audit-2fa@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and a valid TOTP code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 200
    And an INFO-level audit log should be emitted for "TwoFactorCompleted"
    And the audit log should contain "userId"
    And the audit log should contain "method" with value "totp"

  Scenario: Failed 2FA attempt emits audit log
    Given user with email "audit-2fa-fail@test.com" and password "passWORD1" exists
    And user with email "audit-2fa-fail@test.com" has 2FA enabled
    And signing in with email "audit-2fa-fail@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and code "000000"
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 401
    And a WARNING-level audit log should be emitted for "TwoFactorFailed"

  Scenario: 2FA enabled emits audit log
    Given I am authenticated as user "audit-2fa-enable@test.com"
    And I have completed 2FA setup
    And confirming 2FA with a valid TOTP code
    When POST request is send to "/api/users/2fa/confirm"
    Then the response status code should be 200
    And an INFO-level audit log should be emitted for "TwoFactorEnabled"
    And the audit log should contain "userId"

  Scenario: 2FA disabled emits audit log
    Given I am authenticated as user "audit-2fa-disable@test.com"
    And user "audit-2fa-disable@test.com" has 2FA enabled
    And disabling 2FA with a valid TOTP code
    When POST request is send to "/api/users/2fa/disable"
    Then the response status code should be 204
    And an INFO-level audit log should be emitted for "TwoFactorDisabled"

  Scenario: Logout emits audit log
    Given I am authenticated as user "audit-logout@test.com"
    When POST request is send to "/api/signout"
    Then the response status code should be 204
    And an INFO-level audit log should be emitted for "SessionRevoked"
    And the audit log should contain "reason" with value "logout"

  Scenario: Sign-out-all emits audit log
    Given I am authenticated as user "audit-signout-all@test.com"
    When POST request is send to "/api/signout/all"
    Then the response status code should be 204
    And an INFO-level audit log should be emitted for "AllSessionsRevoked"
    And the audit log should contain "reason" with value "user_initiated"

  Scenario: Token rotation emits audit log
    Given user with email "audit-rotate@test.com" and password "passWORD1" exists
    And user "audit-rotate@test.com" has signed in and received tokens
    And submitting the refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 200
    And a DEBUG-level audit log should be emitted for "RefreshTokenRotated"

  Scenario: Refresh token theft detection emits CRITICAL audit log
    Given user with email "audit-theft@test.com" and password "passWORD1" exists
    And user "audit-theft@test.com" has signed in and received tokens
    And the refresh token has been rotated and grace reuse has been consumed
    And submitting the rotated refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 401
    And a CRITICAL-level audit log should be emitted for "RefreshTokenTheftDetected"
    And the audit log should contain "sessionId"
    And the audit log should contain "userId"
    And the audit log should contain "ip"

  Scenario: Recovery code usage emits audit log with remaining count
    Given user with email "audit-recovery@test.com" and password "passWORD1" exists
    And user with email "audit-recovery@test.com" has 2FA enabled with recovery codes
    And signing in with email "audit-recovery@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and a valid recovery code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 200
    And a WARNING-level audit log should be emitted for "RecoveryCodeUsed"
    And the audit log should contain "userId"
    And the audit log should contain "remainingCodes"

  Scenario: Account lockout emits audit log
    Given user with email "audit-lockout@test.com" and password "passWORD1" exists
    And 20 failed sign-in attempts have been recorded for email "audit-lockout@test.com"
    And signing in with email "audit-lockout@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 423
    And a WARNING-level audit log should be emitted for "AccountLockedOut"
    And the audit log should contain "email"

  Scenario: Password change session revocation emits audit log
    Given user with id "8be90127-9840-4235-a6da-39b8debfb295" and password "passWORD1" exists
    And user "8be90127-9840-4235-a6da-39b8debfb295" has 2 active sessions
    And I am authenticated on session 1 for user "8be90127-9840-4235-a6da-39b8debfb295"
    And updating user with email "audit-pwchange@test.com", initials "name", oldPassword "passWORD1", newPassword "passWORD2"
    When PATCH request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb295"
    Then the response status code should be 200
    And an INFO-level audit log should be emitted for "AllSessionsRevoked"
    And the audit log should contain "reason" with value "password_change"

  Scenario: 2FA enable session revocation emits audit log
    Given user "audit-2fa-revoke@test.com" has 3 active sessions
    And I am authenticated as user "audit-2fa-revoke@test.com" on device 1
    And I have completed 2FA setup
    And confirming 2FA with a valid TOTP code
    When POST request is send to "/api/users/2fa/confirm"
    Then the response status code should be 200
    And an INFO-level audit log should be emitted for "AllSessionsRevoked"
    And the audit log should contain "reason" with value "two_factor_enabled"

  Scenario: 2FA sign-in with recovery code emits correct method
    Given user with email "audit-recovery-method@test.com" and password "passWORD1" exists
    And user with email "audit-recovery-method@test.com" has 2FA enabled with recovery codes
    And signing in with email "audit-recovery-method@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and a valid recovery code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 200
    And an INFO-level audit log should be emitted for "TwoFactorCompleted"
    And the audit log should contain "method" with value "recovery"

  # Audit log: User-Agent tracking (ADR-01)

  Scenario: Successful sign-in audit log includes User-Agent
    Given user with email "audit-ua@test.com" and password "passWORD1" exists
    And signing in with email "audit-ua@test.com" and password "passWORD1" with User-Agent "TestBrowser/1.0"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And an INFO-level audit log should be emitted for "UserSignedIn"
    And the audit log should contain "userAgent" with value "TestBrowser/1.0"

  Scenario: Failed sign-in audit log includes User-Agent
    Given user with email "audit-ua-fail@test.com" and password "passWORD1" exists
    And signing in with email "audit-ua-fail@test.com" and password "wrongPassword1" with User-Agent "TestBrowser/1.0"
    When POST request is send to "/api/signin"
    Then the response status code should be 401
    And a WARNING-level audit log should be emitted for "SignInFailed"
    And the audit log should contain "userAgent"

  Scenario: Audit log with missing User-Agent header still succeeds
    Given user with email "audit-no-ua@test.com" and password "passWORD1" exists
    And signing in with email "audit-no-ua@test.com" and password "passWORD1" without User-Agent header
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And an INFO-level audit log should be emitted for "UserSignedIn"

  # Audit log: Session metadata (ADR-01)

  Scenario: Sign-in audit log includes session ID
    Given user with email "audit-sid@test.com" and password "passWORD1" exists
    And signing in with email "audit-sid@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 200
    And an INFO-level audit log should be emitted for "UserSignedIn"
    And the audit log should contain "sessionId"

  Scenario: 2FA completion audit log includes session ID
    Given user with email "audit-2fa-sid@test.com" and password "passWORD1" exists
    And user with email "audit-2fa-sid@test.com" has 2FA enabled
    And signing in with email "audit-2fa-sid@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And I store the pending_session_id from the response
    And completing 2FA with the stored pending_session_id and a valid TOTP code
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 200
    And an INFO-level audit log should be emitted for "TwoFactorCompleted"
    And the audit log should contain "sessionId"

  # Audit log: Log levels are correct (ADR-08)

  Scenario: RefreshTokenRotated event is at DEBUG level not higher
    Given user with email "audit-debug-level@test.com" and password "passWORD1" exists
    And user "audit-debug-level@test.com" has signed in and received tokens
    And submitting the refresh token to exchange
    When POST request is send to "/api/token"
    Then the response status code should be 200
    And a DEBUG-level audit log should be emitted for "RefreshTokenRotated"
    And no WARNING-level audit log should be emitted for "RefreshTokenRotated"
    And no CRITICAL-level audit log should be emitted for "RefreshTokenRotated"

  # Audit log: Rate limit events

  Scenario: Rate limit exceeded emits audit log
    Given 10 sign-in requests from the same IP have been sent within 1 minute
    And signing in with email "audit-ratelimit@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 429
    And a WARNING-level audit log should be emitted for "RateLimitExceeded"
    And the audit log should contain "ip"
    And the audit log should contain "endpoint"
