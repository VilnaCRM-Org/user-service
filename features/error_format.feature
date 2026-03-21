Feature: Error Response Format
  In order to integrate reliably with API clients
  As the system
  I want all error responses to use RFC 7807 problem+json format

  # NFR-25: All error responses use RFC 7807 format

  Scenario: 401 on protected endpoint uses RFC 7807 format
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401
    And the response should be RFC 7807 problem+json
    And the response should contain "type"
    And the response should contain "title"
    And the response should contain "status"
    And the response should contain "detail"

  Scenario: 401 on sign-in failure uses RFC 7807 format
    Given signing in with email "rfc-signin@test.com" and password "wrongPassword1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401
    And the response should be RFC 7807 problem+json
    And the response should contain "type"
    And the response should contain "title"
    And the response should contain "status"
    And the response should contain "detail"

  Scenario: 401 on expired JWT uses RFC 7807 format
    Given I have an expired JWT
    When GET request is send to "/api/users?page=1&itemsPerPage=10"
    Then the response status code should be 401
    And the response should be RFC 7807 problem+json

  Scenario: 401 on 2FA failure uses RFC 7807 format
    Given completing 2FA with pending_session_id "invalid-session" and code "123456"
    When POST request is send to "/api/signin/2fa"
    Then the response status code should be 401
    And the response should be RFC 7807 problem+json

  Scenario: 401 on invalid refresh token uses RFC 7807 format
    Given submitting refresh token "invalid-token"
    When POST request is send to "/api/token"
    Then the response status code should be 401
    And the response should be RFC 7807 problem+json

  Scenario: 403 on ownership violation uses RFC 7807 format
    Given I am authenticated as user "rfc-403@test.com" with id "8be90127-9840-4235-a6da-39b8debfb290"
    And user with id "8be90127-9840-4235-a6da-39b8debfb291" exists
    When DELETE request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb291"
    Then the response status code should be 403
    And the response should be RFC 7807 problem+json

  Scenario: 403 on batch without ROLE_SERVICE uses RFC 7807 format
    Given I am authenticated as user "rfc-batch@test.com" with role "ROLE_USER"
    And sending a batch of users
    And with user with email "rfc-batch-user@mail.com", initials "name surname", password "passWORD1"
    When POST request is send to "/api/users/batch"
    Then the response status code should be 403
    And the response should be RFC 7807 problem+json

  Scenario: 404 on non-existent user uses RFC 7807 format
    Given I am authenticated as user "rfc-404@test.com"
    When GET request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb299"
    Then the response status code should be 404
    And the response should be RFC 7807 problem+json

  Scenario: 422 on validation error uses RFC 7807 format
    Given creating user with email "not-valid", initials "name surname", password "passWORD1"
    When POST request is send to "/api/users"
    Then the response status code should be 422
    And the response should be RFC 7807 problem+json

  Scenario: 423 on account lockout uses RFC 7807 format
    Given user with email "rfc-lockout@test.com" and password "passWORD1" exists
    And 20 failed sign-in attempts have been recorded for email "rfc-lockout@test.com"
    And signing in with email "rfc-lockout@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 423
    And the response should be RFC 7807 problem+json

  Scenario: 429 on global rate limit uses RFC 7807 format
    Given 100 anonymous requests have been sent within 1 minute
    When GET request is send to "/api/health"
    Then the response status code should be 429
    And the response should be RFC 7807 problem+json
    And the response should contain "type"
    And the response should contain "title"
    And the response should contain "status"
    And the response should contain "detail"

  Scenario: 429 on sign-in rate limit uses RFC 7807 format
    Given 10 sign-in requests from the same IP have been sent within 1 minute
    And signing in with email "rfc-rate@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 429
    And the response should be RFC 7807 problem+json

  Scenario: Error responses do not expose stack traces
    Given signing in with email "notrace@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401
    And the response should not contain "trace"
    And the response should not contain "stack"
    And the response should not contain "Exception"

  Scenario: Error responses include correct Content-Type header
    Given signing in with email "content-err@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401
    And the response should have header "Content-Type" containing "application/problem+json"

  # Additional RFC 7807 format validations

  Scenario: 400 on malformed JSON uses RFC 7807 format
    Given sending malformed JSON body to sign-in
    When POST request is send to "/api/signin"
    Then the response status code should be 400
    And the response should be RFC 7807 problem+json

  Scenario: 405 on wrong HTTP method uses RFC 7807 format
    When GET request is send to "/api/signin"
    Then the response status code should be 405
    And the response should be RFC 7807 problem+json

  Scenario: 413 on oversized body uses RFC 7807 format
    Given a request body larger than 64KB
    When POST request is send to "/api/users"
    Then the response status code should be 413
    And the response should be RFC 7807 problem+json

  Scenario: 415 on unsupported media type uses RFC 7807 format
    Given signing in with email "rfc-415@test.com" and password "passWORD1" with Content-Type "text/plain"
    When POST request is send to "/api/signin"
    Then the response status code should be 415
    And the response should be RFC 7807 problem+json

  # RFC 7807 "status" field matches HTTP status code

  Scenario: RFC 7807 status field matches 401 HTTP status
    Given signing in with email "rfc-status-401@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401
    And the RFC 7807 "status" field should be 401

  Scenario: RFC 7807 status field matches 422 HTTP status
    Given signing in with email "not-an-email" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 422
    And the RFC 7807 "status" field should be 422

  Scenario: RFC 7807 status field matches 429 HTTP status
    Given 10 sign-in requests from the same IP have been sent within 1 minute
    And signing in with email "rfc-status-429@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 429
    And the RFC 7807 "status" field should be 429

  # Error responses do not reveal implementation details

  Scenario: Error responses do not contain MongoDB details
    Given signing in with email "no-mongo@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401
    And the response should not contain "MongoDB"
    And the response should not contain "collection"
    And the response should not contain "doctrine"

  Scenario: Error responses do not contain Symfony internals
    Given signing in with email "no-symfony@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401
    And the response should not contain "Symfony"
    And the response should not contain "kernel"
    And the response should not contain "container"

  Scenario: Error responses do not contain file paths
    Given signing in with email "no-paths@test.com" and password "passWORD1"
    When POST request is send to "/api/signin"
    Then the response status code should be 401
    And the response should not contain "/var/www"
    And the response should not contain ".php"
