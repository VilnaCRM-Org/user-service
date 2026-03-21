Feature: GraphQL Password Reset Operations
  In order to allow users to reset their passwords via GraphQL
  As a system
  I want to provide GraphQL password reset functionality

  Scenario: Requesting password reset for existing user via GraphQL
    Given I am authenticated as user "gql-pwreset-auth@test.com"
    And user with email "graphqlreset@test.com" exists
    And requesting password reset for "graphqlreset@test.com" via graphQL
    When graphQL request is send
    Then graphQL password reset mutation should succeed

  Scenario: Requesting password reset for non-existing user via GraphQL
    Given I am authenticated as user "gql-pwreset-auth2@test.com"
    And requesting password reset for "nonexistent@test.com" via graphQL
    When graphQL request is send
    Then graphQL password reset mutation should succeed

  Scenario: Confirming password reset with valid token via GraphQL
    Given I am authenticated as user "gql-pwreset-auth3@test.com"
    And user with email "graphqlreset2@test.com" exists
    And password reset token exists for user "graphqlreset2@test.com"
    And confirming password reset with valid token and new password "newPassWORD1" via graphQL
    When graphQL request is send
    Then graphQL password reset mutation should succeed

  Scenario: Confirming password reset with invalid token via GraphQL
    Given I am authenticated as user "gql-pwreset-auth4@test.com"
    And confirming password reset with token "invalid-token" and new password "newPassWORD1" via graphQL
    When graphQL request is send
    Then graphql error message should be "Password reset token not found"
