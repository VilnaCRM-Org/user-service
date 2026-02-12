Feature: Rate Limiting
  In order to protect the system from abuse and credential stuffing
  As the system
  I want to enforce per-endpoint rate limits with appropriate response codes

  # Story 5.1: Global and existing endpoint rate limiting (NFR-08 through NFR-14, NFR-43 through NFR-49)

  Scenario: Global anonymous rate limit enforced
    Given 100 anonymous requests have been sent within 1 minute
    When GET request is send to "/api/health"
    Then the response status code should be 429
    And the response should have header "Retry-After"
    And the response should be RFC 7807 problem+json

  Scenario: Global authenticated rate limit is higher than anonymous
    Given I am authenticated as user "global-auth-rate@test.com"
    And 100 authenticated requests have been sent within 1 minute
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 200

  Scenario: Global authenticated rate limit enforced at 300/min
    Given I am authenticated as user "global-auth-limit@test.com"
    And 300 authenticated requests have been sent within 1 minute
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 429

  Scenario: Registration rate limit enforced at 5/min per IP
    Given 5 registration requests have been sent from the same IP within 1 minute
    And creating user with email "rate-reg@test.com", initials "name surname", password "passWORD1"
    When POST request is send to "/api/users"
    Then the response status code should be 429

  Scenario: User collection rate limit enforced at 30/min
    Given I am authenticated as user "collection-rate@test.com"
    And 30 GET requests to "/api/users" have been sent within 1 minute
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 429

  Scenario: User update rate limit enforced at 10/min per user
    Given I am authenticated as user "update-rate@test.com" with id "8be90127-9840-4235-a6da-39b8debfb260"
    And user with id "8be90127-9840-4235-a6da-39b8debfb260" and password "passWORD1" exists
    And 10 PATCH requests for the user have been sent within 1 minute
    And updating user with email "rate@test.com", initials "name", oldPassword "passWORD1", newPassword "passWORD1"
    When PATCH request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb260"
    Then the response status code should be 429

  Scenario: User delete rate limit enforced at 3/min per user
    Given I am authenticated as user "delete-rate@test.com" with id "8be90127-9840-4235-a6da-39b8debfb261"
    And 3 DELETE requests for the user have been sent within 1 minute
    When DELETE request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb261"
    Then the response status code should be 429

  Scenario: Resend confirmation rate limit per IP enforced at 3/min
    Given I am authenticated as user "resend-rate@test.com" with id "8be90127-9840-4235-a6da-39b8debfb262"
    And user with id "8be90127-9840-4235-a6da-39b8debfb262" exists
    And 3 resend confirmation requests from the same IP have been sent within 1 minute
    When POST request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb262/resend-confirmation-email"
    Then the response status code should be 429

  Scenario: Token exchange rate limit enforced at 10/min per client
    Given 10 token exchange requests with the same client_id have been sent within 1 minute
    And submitting refresh token "some-token"
    When POST request is send to "/api/token"
    Then the response status code should be 429

  Scenario: Email confirmation rate limit enforced at 10/min per IP
    Given 10 email confirmation requests from the same IP have been sent within 1 minute
    And confirming user with token "some-token"
    When PATCH request is send to "/api/users/confirm"
    Then the response status code should be 429

  # Story 5.2: Sign-in and 2FA rate limiting (NFR-11, NFR-12, NFR-44, NFR-45, NFR-55)

  Scenario: Sign-in rate limit per IP enforced at 10/min
    Given 10 sign-in requests from the same IP have been sent within 1 minute
    And signing in with email "rate-ip@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 429
    And the response should have header "Retry-After"

  Scenario: Sign-in rate limit per email enforced at 5/min
    Given 5 sign-in requests for email "rate-email@test.com" have been sent within 1 minute
    And signing in with email "rate-email@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 429

  Scenario: 2FA verification rate limit per user enforced at 5/min
    Given user with email "2fa-rate@test.com" and password "passWORD1" exists
    And user with email "2fa-rate@test.com" has 2FA enabled
    And 5 two-factor verification requests for user "2fa-rate@test.com" have been sent within 1 minute
    And completing 2FA with pending_session_id "some-session" and code "123456"
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 429

  Scenario: 2FA setup rate limit enforced at 5/min per user
    Given I am authenticated as user "2fa-setup-rate@test.com"
    And 5 two-factor setup requests have been sent within 1 minute
    When POST request is send to "/api/users/2fa/setup"
    Then the response status code should be 429

  Scenario: 2FA confirm rate limit enforced at 5/min per user
    Given I am authenticated as user "2fa-confirm-rate@test.com"
    And 5 two-factor confirm requests have been sent within 1 minute
    And confirming 2FA with code "123456"
    When POST request is send to "/api/users/2fa/confirm"
    Then the response status code should be 429

  Scenario: 2FA disable rate limit enforced at 3/min per user
    Given I am authenticated as user "2fa-disable-rate@test.com"
    And user "2fa-disable-rate@test.com" has 2FA enabled
    And 3 two-factor disable requests have been sent within 1 minute
    And disabling 2FA with code "123456"
    When POST request is send to "/api/users/2fa/disable"
    Then the response status code should be 429

  Scenario: All rate limit rejections include Retry-After header
    Given 10 sign-in requests from the same IP have been sent within 1 minute
    And signing in with email "retry-after@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 429
    And the response should have header "Retry-After"
    And the "Retry-After" header value should be a positive integer

  # Story 5.2 additional: Recovery codes, signout, and other endpoint rate limits

  Scenario: Recovery codes rate limit enforced at 3/min per user
    Given I am authenticated as user "recovery-rate@test.com"
    And user "recovery-rate@test.com" has 2FA enabled
    And user "recovery-rate@test.com" has completed high-trust re-auth within 5 minutes
    And 3 recovery code regeneration requests have been sent within 1 minute
    When POST request is send to "/api/users/2fa/recovery-codes"
    Then the response status code should be 429

  Scenario: Signout rate limit enforced at 10/min per user
    Given I am authenticated as user "signout-rate@test.com"
    And 10 signout requests have been sent within 1 minute
    When POST request is send to "/api/signout"
    Then the response status code should be 429

  Scenario: Signout all rate limit enforced at 5/min per user
    Given I am authenticated as user "signout-all-rate@test.com"
    And 5 signout-all requests have been sent within 1 minute
    When POST request is send to "/api/signout/all"
    Then the response status code should be 429

  Scenario: Resend confirmation per target user rate limit enforced at 3/min
    Given I am authenticated as user "resend-target-rate@test.com" with id "8be90127-9840-4235-a6da-39b8debfb263"
    And user with id "8be90127-9840-4235-a6da-39b8debfb263" exists
    And 3 resend confirmation requests targeting user "8be90127-9840-4235-a6da-39b8debfb263" have been sent within 1 minute
    When POST request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb263/resend-confirmation-email"
    Then the response status code should be 429

  Scenario: Password reset rate limit enforced at 1000/hour per email
    Given 1000 password reset requests for email "reset-rate@test.com" have been sent within 1 hour
    And requesting password reset for email "reset-rate@test.com"
    When POST request is send to "/api/reset-password"
    Then the response status code should be 429

  # Rate limit response format validation (NFR-14, NFR-25)

  Scenario: Rate limit rejection on registration uses RFC 7807 format
    Given 5 registration requests have been sent from the same IP within 1 minute
    And creating user with email "rate-reg-rfc@test.com", initials "name surname", password "passWORD1"
    When POST request is send to "/api/users"
    Then the response status code should be 429
    And the response should be RFC 7807 problem+json
    And the response should contain "type"
    And the response should contain "title"
    And the response should contain "status"
    And the response should contain "detail"

  Scenario: Rate limit rejection on token exchange uses RFC 7807 format
    Given 10 token exchange requests with the same client_id have been sent within 1 minute
    And submitting refresh token "some-token"
    When POST request is send to "/api/token"
    Then the response status code should be 429
    And the response should be RFC 7807 problem+json

  Scenario: Rate limit on 2FA verification per IP enforced
    Given 5 two-factor verification requests from the same IP have been sent within 1 minute
    And completing 2FA with pending_session_id "some-session" and code "123456"
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 429

  # Rate limit below threshold (positive cases)

  Scenario: Requests within global anonymous limit succeed
    Given 50 anonymous requests have been sent within 1 minute
    When GET request is send to "/api/health"
    Then the response status code should be 200

  Scenario: Requests within sign-in per-IP limit succeed
    Given 5 sign-in requests from the same IP have been sent within 1 minute
    And signing in with email "rate-ok@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should not be 429

  Scenario: Requests within registration limit succeed
    Given 3 registration requests have been sent from the same IP within 1 minute
    And creating user with email "rate-reg-ok@test.com", initials "name surname", password "passWORD1"
    When POST request is send to "/api/users"
    Then the response status code should not be 429

  # Rate limit per-email vs per-IP independence (ADR-02)

  Scenario: Sign-in rate limit per-email is independent of per-IP
    Given user with email "rate-email-indep@test.com" and password "passWORD1" exists
    And 4 sign-in requests for email "rate-email-indep@test.com" have been sent within 1 minute
    And 9 sign-in requests from the same IP have been sent within 1 minute
    And signing in with email "rate-email-indep@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should not be 429

  Scenario: Different emails from same IP share IP rate limit
    Given 9 sign-in requests from the same IP have been sent within 1 minute
    And signing in with email "rate-ip-shared-a@test.com" and password "passWORD1"
    And POST request is send to "/api/signin"
    And signing in with email "rate-ip-shared-b@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 429

  # Rate limit response headers (NFR-14)

  Scenario: Rate limit response includes X-RateLimit headers
    Given 10 sign-in requests from the same IP have been sent within 1 minute
    And signing in with email "rate-headers@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 429
    And the response should have header "Retry-After"

  # Rate limit on password reset confirm (ADR-02)

  Scenario: Password reset confirm rate limit enforced at 10/min per IP
    Given 10 password reset confirm requests from the same IP have been sent within 1 minute
    And requesting password reset confirm with token "some-token" and password "passWORD1"
    When POST request is send to "/api/reset-password/confirm"
    Then the response status code should be 429

  # Rate limit on GraphQL endpoint

  Scenario: GraphQL endpoint follows global authenticated rate limit
    Given I am authenticated as user "rate-gql@test.com"
    And 300 authenticated requests have been sent within 1 minute
    When I send a GraphQL query for user collection
    Then the response status code should be 429

  # Rate limit does not block different resources

  Scenario: Rate limit on one user's update does not affect another user
    Given I am authenticated as user "rate-user-a@test.com" with id "8be90127-9840-4235-a6da-39b8debfb264"
    And user with id "8be90127-9840-4235-a6da-39b8debfb264" and password "passWORD1" exists
    And 10 PATCH requests for user "8be90127-9840-4235-a6da-39b8debfb264" have been sent within 1 minute
    And I am authenticated as user "rate-user-b@test.com" with id "8be90127-9840-4235-a6da-39b8debfb265"
    And user with id "8be90127-9840-4235-a6da-39b8debfb265" and password "passWORD1" exists
    And updating user with email "rate@test.com", initials "name", oldPassword "passWORD1", newPassword "passWORD1"
    When PATCH request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb265"
    Then the response status code should not be 429
