Feature: OAuth authorization
  In order get access to protected resources
  As a OAuth client
  I want to be able to obtain access token

  Scenario: Obtaining access token with client-credentials grant
    Given client with id "TestClientSecret", secret "9dcdad26bb0a2945242f9971ac82975d21f8284e164ed1320031b9be595a4c728e83341e80750af6b1382f6609be1d917ccd36a3e0a05d3b0a59cefcc170de25" and redirect uri "https://example.com/oauth/callback" exists
    And passing client id "TestClientSecret" and client secret "9dcdad26bb0a2945242f9971ac82975d21f8284e164ed1320031b9be595a4c728e83341e80750af6b1382f6609be1d917ccd36a3e0a05d3b0a59cefcc170de25"
    When obtaining access token with "client_credentials" grant-type
    Then access token should be provided

  Scenario: Obtaining access token with authorization-code grant
    Given client with id "AuthCodeSecret", secret "f70df0851b9b6e8a2ff220441eaf458b9c9a3ce6398176734adfc6d57b713fdce11544a691da59f7b1f88130d0dd07c9120ca4b301b521d3690ab43b68a07fa4" and redirect uri "https://example.com/oauth/callback" exists
    And passing client id "AuthCodeSecret" and redirect_uri "https://example.com/oauth/callback"
    And authenticating user with email "testuser@example.com" and password "password"
    And obtaining auth code
    And passing client id "AuthCodeSecret", client secret "f70df0851b9b6e8a2ff220441eaf458b9c9a3ce6398176734adfc6d57b713fdce11544a691da59f7b1f88130d0dd07c9120ca4b301b521d3690ab43b68a07fa4", redirect_uri "https://example.com/oauth/callback" and auth code
    When obtaining access token with "authorization_code" grant-type
    Then the response status code should be 400

  Scenario: Obtaining access token with password grant
    Given client with id "PasswordId", secret "PasswordSecret" and redirect uri "https://example.com/oauth/callback" exists
    And user with email "passGrant@example.com" and password "pass" exists
    And passing client id "PasswordId", client secret "PasswordSecret", email "passGrant@example.com" and password "pass"
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

  Scenario: Failing to obtain authorization code without authentication
    Given client with id "AuthCodeId", secret "AuthCodeSecret" and redirect uri "https://example.com/oauth/callback" exists
    And passing client id "AuthCodeId" and redirect_uri "https://example.com/oauth/callback"
    When I request the authorization endpoint
    Then unauthorized error should be returned
