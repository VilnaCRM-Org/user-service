Feature: Social OAuth
  In order to sign in with supported social providers
  As an API client
  I want the social OAuth endpoints to complete stable direct and 2FA flows

  Scenario: Social OAuth direct sign-in succeeds
    When GET request is send to "/api/auth/social/github"
    Then the response status code should be 302
    And I store the OAuth state from the redirect location as "direct_state"
    And I store the "oauth_flow_binding" cookie from the response as "direct_cookie"
    When I complete social OAuth for provider "github" with code "behat-direct-user" using stored state "direct_state" and cookie "direct_cookie"
    Then the response status code should be 200
    And the response should contain "access_token"
    And the response should contain "refresh_token"
    And the response should set auth cookie

  Scenario: Social OAuth starts a 2FA challenge for linked users
    Given user with email "github-behat-two-factor@oauth.example.test" and password "passWORD1" exists
    And user with email "github-behat-two-factor@oauth.example.test" has 2FA enabled
    When GET request is send to "/api/auth/social/github"
    Then the response status code should be 302
    And I store the OAuth state from the redirect location as "two_factor_state"
    And I store the "oauth_flow_binding" cookie from the response as "two_factor_cookie"
    When I complete social OAuth for provider "github" with code "behat-two-factor" using stored state "two_factor_state" and cookie "two_factor_cookie"
    Then the response status code should be 200
    And the response should contain "pending_session_id"
    And the response should not set auth cookie

  Scenario: Replaying a consumed social OAuth callback fails
    When GET request is send to "/api/auth/social/google"
    Then the response status code should be 302
    And I store the OAuth state from the redirect location as "replay_state"
    And I store the "oauth_flow_binding" cookie from the response as "replay_cookie"
    When I complete social OAuth for provider "google" with code "behat-replay" using stored state "replay_state" and cookie "replay_cookie"
    Then the response status code should be 200
    When I complete social OAuth for provider "google" with code "behat-replay" using stored state "replay_state" and cookie "replay_cookie"
    Then the response status code should be 422
    And the response should contain "invalid_state"

  Scenario: Facebook social OAuth returns provider email unavailable
    When GET request is send to "/api/auth/social/facebook"
    Then the response status code should be 302
    And I store the OAuth state from the redirect location as "facebook_state"
    And I store the "oauth_flow_binding" cookie from the response as "facebook_cookie"
    When I complete social OAuth for provider "facebook" with code "no-email" using stored state "facebook_state" and cookie "facebook_cookie"
    Then the response status code should be 422
    And the response should contain "provider_email_unavailable"
