Feature: OAuth authorization
  In order get access to protected resources
  As a OAuth client
  I want to be able to obtain access token

  Scenario: Obtaining access token with client-credentials grant
    Given client with id "ClientCredId", secret "ClientCredSecret" exists and redirect uri "https://example.com/oauth/callback"
    And passing client id "ClientCredId" and client secret "ClientCredSecret"
    When obtaining access token with "client_credentials" grant-type
    Then access token should be provided

  Scenario: Obtaining access token with authorization-code grant
    Given client with id "AuthCodeId", secret "AuthCodeSecret" exists and redirect uri "https://example.com/oauth/callback"
    And passing client id "AuthCodeId" and redirect_uri "https://example.com/oauth/callback"
    And obtaining auth code
    And passing client id "AuthCodeId", client secret "AuthCodeSecret", redirect_uri "https://example.com/oauth/callback" and auth code
    When obtaining access token with "authorization_code" grant-type
    Then access token should be provided

    #TODO need to implement password grant first
#  Scenario: Obtaining access token with password grant
#    Given client with id "a" and secret "b" exists
#    And user with email "login" and password "pass" exists
#    And passing client id "a", client secret "b", login "login" and password "pass"
#    When obtaining access token with "password" grant-type
#    Then access token should be provided

    #TODO also add negative testing