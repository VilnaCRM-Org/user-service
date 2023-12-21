Feature: User GraphQL Operations
  In order to manage users
  As a system administrator
  I want to perform GraphQL operations on user records

  Scenario: Creating user
    Given requesting to return user's id and email
    And creating user with email "graphqltest@mail.com" initials "name" password "pass"
    When graphQL request is send
    Then mutation response should return requested fields

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
    Then mutation response should return requested fields

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

  Scenario: Deleting user
    Given requesting to return user's id
    And user with id "deleteGraphQLUserId" exists
    And deleting user with id "deleteGraphQLUserId"
    When graphQL request is send
    Then mutation response should return requested fields

  Scenario: Deleting non-existing user
    Given requesting to return user's id
    And deleting user with id "wrongDeleteGraphQLUserId"
    When graphQL request is send
    Then graphql error response should be returned

  Scenario: Confirm user
    Given requesting to return user's id and email
    And user with id "confirmGraphQLUserId" exists
    And user with id "confirmGraphQLUserId" has confirmation token "confirmationToken"
    And confirming user with token "confirmationToken" via graphQl
    When graphQL request is send
    Then mutation response should return requested fields

  Scenario: Confirm with expired token
    Given requesting to return user's id and email
    And confirming user with token "expiredToken" via graphQl
    When graphQL request is send
    Then graphql error response should be returned

  Scenario: Resend email to user
    Given requesting to return user's id and email
    And user with id "resendEmailGraphQLUserId" exists
    And resending email to user with id "resendEmailGraphQLUserId"
    When graphQL request is send
    Then mutation response should return requested fields

  Scenario: Resend email non-existing to user
    Given requesting to return user's id and email
    And resending email to user with id "wrongResendEmailGraphQLUserId"
    When graphQL request is send
    Then graphql error response should be returned

  Scenario: Getting user
    Given requesting to return user's id and email
    And user with id "getGraphQLUserId" exists
    And getting user with id "getGraphQLUserId"
    When graphQL request is send
    Then query response should return requested fields

  Scenario: Getting non-existing user
    Given requesting to return user's id and email
    And getting user with id "wrongGetGraphQLUserId"
    When graphQL request is send
    Then graphql error response should be returned

  Scenario: Getting collection of users
    Given requesting to return user's id and email
    And getting collection of users
    When graphQL request is send
    Then collection of users should be returned
