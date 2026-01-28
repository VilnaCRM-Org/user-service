Feature: OAuth authorization
  In order get access to protected resources
  As a OAuth client
  I want to be able to obtain access token

  Scenario: Obtaining access token with client-credentials grant
    Given client with id "ClientCredId", secret "ClientCredSecret" and redirect uri "https://example.com" exists
    And passing client id "ClientCredId" and client secret "ClientCredSecret"
    When obtaining access token with "client_credentials" grant-type
    Then access token should be provided

  Scenario: Obtaining access token with authorization-code grant
    Given client with id "AuthCodeId", secret "AuthCodeSecret" and redirect uri "https://example.com" exists
    And passing client id "AuthCodeId" and redirect_uri "https://example.com"
    And authenticating user with email "testuser@example.com" and password "password"
    And obtaining auth code
    And passing client id "AuthCodeId", client secret "AuthCodeSecret", redirect_uri "https://example.com" and auth code
    When obtaining access token with "authorization_code" grant-type
    Then access token should be provided

  Scenario: Obtaining access token with password grant
    Given client with id "PasswordId", secret "PasswordSecret" and redirect uri "https://example.com" exists
    And user with email "passGrant@mail.com" and password "pass" exists
    And passing client id "PasswordId", client secret "PasswordSecret", email "passGrant@mail.com" and password "pass"
    When obtaining access token with "password" grant-type
    Then access token should be provided

  Scenario: Obtaining access token with invalid credentials
    And passing client id "invalidId" and client secret "invalidSecret"
    When obtaining access token with "client_credentials" grant-type
    Then invalid credentials error should be returned

  Scenario: Obtaining access token with invalid grant
    Given client with id "InvalidGrantId", secret "InvalidGrantSecret" and redirect uri "https://example.com" exists
    And passing client id "InvalidGrantId" and client secret "InvalidGrantSecret"
    When obtaining access token with "invalidGrant" grant-type
    Then unsupported grant type error should be returned

  Scenario: Failing to obtain authorization code without authentication
    Given client with id "AuthCodeId", secret "AuthCodeSecret" and redirect uri "https://example.com" exists
    And passing client id "AuthCodeId" and redirect_uri "https://example.com"
    When I request the authorization endpoint
    Then unauthorized error should be returned

  Scenario: Obtaining access token with implicit grant
    Given client with id "ImplicitId", secret "ImplicitSecret" and redirect uri "https://example.com" exists
    And passing client id "ImplicitId" and redirect_uri "https://example.com"
    And using response type "token"
    And authenticating user with email "implicituser@example.com" and password "password"
    When I request the authorization endpoint
    Then implicit access token should be provided

  Scenario: Failing to obtain authorization code with invalid client
    And passing client id "InvalidClientId" and redirect_uri "https://example.com"
    When I request the authorization endpoint
    Then invalid credentials error should be returned

  Scenario: Failing to obtain authorization code with invalid redirect uri
    Given client with id "BadRedirectId", secret "BadRedirectSecret" and redirect uri "https://example.com" exists
    And passing client id "BadRedirectId" and redirect_uri "https://evil.example.com"
    When I request the authorization endpoint
    Then invalid credentials error should be returned

  Scenario: Failing to obtain authorization code with unsupported response type
    Given client with id "BadResponseId", secret "BadResponseSecret" and redirect uri "https://example.com" exists
    And passing client id "BadResponseId" and redirect_uri "https://example.com"
    And using response type "invalid"
    When I request the authorization endpoint
    Then unsupported response type error should be returned

  Scenario: Failing to obtain authorization code with invalid scope
    Given client with id "BadScopeId", secret "BadScopeSecret" and redirect uri "https://example.com" exists
    And passing client id "BadScopeId" and redirect_uri "https://example.com"
    And requesting scope "unknown_scope"
    When I request the authorization endpoint
    Then authorization redirect error "invalid_scope" with description "The requested scope is invalid, unknown, or malformed" should be returned

  Scenario: Failing to obtain authorization code for public client without code challenge
    Given public client with id "PublicClientId" and redirect uri "https://example.com" exists
    And passing client id "PublicClientId" and redirect_uri "https://example.com"
    When I request the authorization endpoint
    Then invalid request error should be returned

  Scenario: Failing to obtain authorization code with invalid code challenge
    Given client with id "BadPkceId", secret "BadPkceSecret" and redirect uri "https://example.com" exists
    And passing client id "BadPkceId" and redirect_uri "https://example.com"
    And using code challenge "invalid"
    When I request the authorization endpoint
    Then invalid request error should be returned

  Scenario: Failing to obtain authorization code with invalid code challenge method
    Given client with id "BadPkceMethodId", secret "BadPkceMethodSecret" and redirect uri "https://example.com" exists
    And passing client id "BadPkceMethodId" and redirect_uri "https://example.com"
    And using code challenge "validcodechallengevalidcodechallengevalidcodechallenge" and method "invalid"
    When I request the authorization endpoint
    Then invalid request error should be returned

  Scenario: Denying authorization request
    Given client with id "DeniedId", secret "DeniedSecret" and redirect uri "https://example.com" exists
    And passing client id "DeniedId" and redirect_uri "https://example.com"
    And authenticating user with email "denieduser@example.com" and password "password"
    When I request the authorization endpoint without approval
    Then authorization redirect error "access_denied" with description "The resource owner or authorization server denied the request." should be returned

  Scenario: Obtaining access token with refresh token grant
    Given client with id "RefreshId", secret "RefreshSecret" and redirect uri "https://example.com" exists
    And user with email "refreshGrant@mail.com" and password "pass" exists
    And passing client id "RefreshId", client secret "RefreshSecret", email "refreshGrant@mail.com" and password "pass"
    When obtaining access token with "password" grant-type
    Then refresh token should be provided
    And passing client id "RefreshId", client secret "RefreshSecret" and refresh token
    When obtaining access token with "refresh_token" grant-type
    Then access token should be provided

  Scenario: Obtaining access token with invalid user credentials
    Given client with id "WrongPasswordId", secret "WrongPasswordSecret" and redirect uri "https://example.com" exists
    And user with email "wrongpass@mail.com" and password "pass" exists
    And passing client id "WrongPasswordId", client secret "WrongPasswordSecret", email "wrongpass@mail.com" and password "wrong"
    When obtaining access token with "password" grant-type
    Then invalid user credentials error should be returned

  Scenario: Obtaining access token with invalid authorization code
    Given client with id "BadCodeId", secret "BadCodeSecret" and redirect uri "https://example.com" exists
    And passing client id "BadCodeId", client secret "BadCodeSecret", redirect_uri "https://example.com" and auth code "invalid-code"
    When obtaining access token with "authorization_code" grant-type
    Then invalid grant error should be returned

  Scenario: Obtaining access token with invalid refresh token
    Given client with id "BadRefreshId", secret "BadRefreshSecret" and redirect uri "https://example.com" exists
    And passing client id "BadRefreshId", client secret "BadRefreshSecret" and refresh token "invalid-refresh-token"
    When obtaining access token with "refresh_token" grant-type
    Then invalid refresh token error should be returned

  Scenario: Obtaining access token without grant type
    Given client with id "NoGrantId", secret "NoGrantSecret" and redirect uri "https://example.com" exists
    And passing client id "NoGrantId" and client secret "NoGrantSecret"
    When obtaining access token without grant type
    Then unsupported grant type error should be returned

  Scenario: Obtaining access token with missing password
    Given client with id "MissingPassId", secret "MissingPassSecret" and redirect uri "https://example.com" exists
    And user with email "missingpass@mail.com" and password "pass" exists
    And passing client id "MissingPassId", client secret "MissingPassSecret" and email "missingpass@mail.com"
    When obtaining access token with password grant without password
    Then invalid request error should be returned

  Scenario: Public client PKCE S256 authorization code flow with valid code verifier
    Given public client with id "PublicPkceId" and redirect uri "https://example.com" exists
    And passing client id "PublicPkceId" and redirect_uri "https://example.com"
    And using PKCE with S256 method
    And authenticating user with email "pkceuser@example.com" and password "password"
    And obtaining auth code with PKCE
    And passing client id "PublicPkceId", redirect_uri "https://example.com", auth code and code verifier
    When obtaining access token with "authorization_code" grant-type
    Then access token should be provided

  Scenario: Authorization code reuse is prevented
    Given client with id "CodeReuseId", secret "CodeReuseSecret" and redirect uri "https://example.com" exists
    And passing client id "CodeReuseId" and redirect_uri "https://example.com"
    And authenticating user with email "codereuseuser@example.com" and password "password"
    And obtaining auth code
    And passing client id "CodeReuseId", client secret "CodeReuseSecret", redirect_uri "https://example.com" and auth code
    When obtaining access token with "authorization_code" grant-type
    Then access token should be provided
    When obtaining access token with "authorization_code" grant-type
    Then invalid grant error should be returned

  Scenario: PKCE code verifier mismatch is rejected
    Given public client with id "PkceMismatchId" and redirect uri "https://example.com" exists
    And passing client id "PkceMismatchId" and redirect_uri "https://example.com"
    And using PKCE with S256 method
    And authenticating user with email "pkcemismatch@example.com" and password "password"
    And obtaining auth code with PKCE
    And passing client id "PkceMismatchId", redirect_uri "https://example.com", auth code and wrong code verifier
    When obtaining access token with "authorization_code" grant-type
    Then invalid grant error should be returned
