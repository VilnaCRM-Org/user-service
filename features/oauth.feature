Feature: OAuth authorization
  In order get access to protected resources
  As a OAuth client
  I want to be able to obtain access token

  Scenario: Obtaining access token with client-credentials grant
    Given client with id "ClientCredId", secret "ClientCredSecret" and redirect uri "https://example.com/oauth/callback" exists
    And passing client id "ClientCredId" and client secret "ClientCredSecret"
    When obtaining access token with "client_credentials" grant-type
    Then access token should be provided

  Scenario: Obtaining access token with authorization-code grant
    Given client with id "AuthCodeId", secret "AuthCodeSecret" and redirect uri "https://example.com/oauth/callback" exists
    And passing client id "AuthCodeId" and redirect_uri "https://example.com/oauth/callback"
    And authenticating user with email "testuser@example.com" and password "password"
    And obtaining auth code
    And passing client id "AuthCodeId", client secret "AuthCodeSecret", redirect_uri "https://example.com/oauth/callback" and auth code
    When obtaining access token with "authorization_code" grant-type
    Then access token should be provided

  Scenario: Obtaining access token with password grant
    Given client with id "PasswordId", secret "PasswordSecret" and redirect uri "https://example.com/oauth/callback" exists
    And user with email "passGrant@mail.com" and password "pass" exists
    And passing client id "PasswordId", client secret "PasswordSecret", email "passGrant@mail.com" and password "pass"
    When obtaining access token with "password" grant-type
    Then access token should be provided

  Scenario: Obtaining access token with invalid credentials
    And passing client id "invalidId" and client secret "invalidSecret"
    When obtaining access token with "client_credentials" grant-type
    Then invalid credentials error should be returned

  Scenario: Obtaining access token with invalid grant
    And passing client id "invalidId" and client secret "invalidSecret"
    When obtaining access token with "invalidGrant" grant-type
    Then unsupported grant type error should be returned
