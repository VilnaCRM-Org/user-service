Feature: User GraphQL Operations
  In order to manage users
  As a system administrator
  I want to perform GraphQL operations on user records

  Scenario: Creating user
    Given requesting to return user's id and email
    And creating user with email "graphqltest@mail.com" initials "name" password "pass"
    When graphQL request is send
    Then user's id and email should be returned

  Scenario: Creating a user with duplicate email
    Given requesting to return user's id and email
    And creating user with email "graphqltest@mail.com" initials "name" password "pass"
    When graphQL request is send
    Then graphql error response should be returned

  Scenario: Creating a user with invalid email
    Given requesting to return user's id and email
    And creating user with email "graphqlTest" initials "name" password "pass"
    When graphQL request is send
    Then graphql error response should be returned

  Scenario: Updating user
    Given requesting to return user's id and email
    And user with id "updateGraphQLUserId" and password "pass" exists
    And updating user with id "updateGraphQLUserId" and password "pass" to new email "testUpdateGraphQL@mail.com"
    When graphQL request is send
    Then user's id and email should be returned

  Scenario: Updating user to duplicate email
    Given requesting to return user's id and email
    And user with id "updateToDuplicateEmailGraphQLUserId" and password "pass" exists
    And updating user with id "updateToDuplicateEmailGraphQLUserId" and password "pass" to new email "testUpdateGraphQL@mail.com"
    When graphQL request is send
    Then graphql error response should be returned

  Scenario: Updating user with wrong password
    Given requesting to return user's id and email
    And updating user with id "updateGraphQLUserId" and password "wrongPass" to new email "testUpdateGraphQL@mail.com"
    When graphQL request is send
    Then graphql error response should be returned

  Scenario: Updating a non-existing user
    Given requesting to return user's id and email
    And updating user with id "wrongUpdateGraphQLUserId" and password "pass" to new email "testUpdateGraphQL@mail.com"
    When graphQL request is send
    Then graphql error response should be returned

  Scenario: Updating user to invalid email
    Given requesting to return user's id and email
    And updating user with id "updateGraphQLUserId" and password "pass" to new email "test"
    When graphQL request is send
    Then graphql error response should be returned
