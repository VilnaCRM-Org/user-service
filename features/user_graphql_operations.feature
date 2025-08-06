Feature: User GraphQL Operations
  In order to manage users
  As a system administrator
  I want to perform GraphQL operations on user records

  Scenario: Creating user
    Given requesting to return user's id and email
    And creating user with email "graphqlcreate456@mail.com" initials "name surname" password "passWORD1"
    When graphQL request is send
    Then mutation response should return requested fields

  Scenario: Creating a user with duplicate email
    Given requesting to return user's id and email
    And user with email "graphqltest@mail.com2" exists
    And creating user with email "graphqltest@mail.com2" initials "name surname" password "passWORD1"
    When graphQL request is send
    Then graphql error message should be "email: This email address is already registered"

  Scenario: Creating a user with invalid email
    Given requesting to return user's id and email
    And creating user with email "graphqlTest" initials "name surname" password "passWORD1"
    When graphQL request is send
    Then graphql error message should be "email: This value is not a valid email address."

  Scenario: Creating a user with password with no uppercase letters
    Given requesting to return user's id and email
    And creating user with email "graphqlTest@mail.com" initials "name surname" password "password1"
    When graphQL request is send
    Then graphql error message should be "password: Password must contain at least one uppercase letter"

  Scenario: Creating a user with password with no numbers
    Given requesting to return user's id and email
    And creating user with email "graphqlTest@mail.com" initials "name surname" password "passWORD"
    When graphQL request is send
    Then graphql error message should be "password: Password must contain at least one number"

  Scenario: Creating a user with too short password
    Given requesting to return user's id and email
    And creating user with email "graphqlTest@mail.com" initials "name surname" password "WORD1"
    When graphQL request is send
    Then graphql error message should be "password: Password must be between 8 and 64 characters long"

  Scenario: Creating a user with initials that contains only spaces
    Given requesting to return user's id and email
    And creating user with email "graphqlTest@mail.com" initials " " password "passWORD1"
    When graphQL request is send
    Then graphql error message should be "initials: Initials can not consist only of spaces"

  Scenario: Updating user
    Given requesting to return user's id and email
    And user with id "8be90127-9840-4235-a6da-39b8debfb110" and password "passWORD1" exists
    And updating user with id "8be90127-9840-4235-a6da-39b8debfb110" and password "passWORD1" to new email "testUpdateGraphQL@mail.com"
    When graphQL request is send
    Then mutation response should return requested fields

  Scenario: Updating user to duplicate email
    Given requesting to return user's id and email
    And user with email "testUpdateGraphQL2@mail.com" exists
    And user with id "8be90127-9840-4235-a6da-39b8debfb111" and password "passWORD1" exists
    And updating user with id "8be90127-9840-4235-a6da-39b8debfb111" and password "passWORD1" to new email "testUpdateGraphQL2@mail.com"
    When graphQL request is send
    Then graphql error message should be "email: This email address is already registered"

  Scenario: Updating user with wrong password
    Given requesting to return user's id and email
    And user with id "8be90127-9840-4235-a6da-39b8debfb111" exists
    And updating user with id "8be90127-9840-4235-a6da-39b8debfb111" and password "wrongpassWORD1" to new email "testUpdateGraphQL@mail.com"
    When graphQL request is send
    Then graphql error message should be "Old password is invalid"

  Scenario: Updating a non-existing user
    Given requesting to return user's id and email
    And updating user with id "8be90127-9840-4235-a6da-39b8debfb112" and password "passWORD1" to new email "testUpdateGraphQL@mail.com"
    When graphQL request is send
    Then graphql error message should be 'Item "/api/users/8be90127-9840-4235-a6da-39b8debfb112" not found.'

  Scenario: Updating user to invalid email
    Given requesting to return user's id and email
    And user with id "8be90127-9840-4235-a6da-39b8debfb111" exists
    And updating user with id "8be90127-9840-4235-a6da-39b8debfb111" and password "passWORD1" to new email "test"
    When graphQL request is send
    Then graphql error message should be "email: This value is not a valid email address."

  Scenario: Deleting user
    Given requesting to return user's id
    And user with id "8be90127-9840-4235-a6da-39b8debfb111" exists
    And deleting user with id "8be90127-9840-4235-a6da-39b8debfb111"
    When graphQL request is send
    Then mutation response should return requested fields

  Scenario: Deleting non-existing user
    Given requesting to return user's id
    And deleting user with id "8be90127-9840-4235-a6da-39b8debfb112"
    When graphQL request is send
    Then graphql error message should be 'Item "/api/users/8be90127-9840-4235-a6da-39b8debfb112" not found.'

  Scenario: Confirm user
    Given requesting to return user's id and email
    And user with id "8be90127-9840-4235-a6da-39b8debfb113" exists
    And user with id "8be90127-9840-4235-a6da-39b8debfb113" has confirmation token "confirmationToken"
    And confirming user with token "confirmationToken" via graphQl
    When graphQL request is send
    Then mutation response should return requested fields

  Scenario: Confirm with expired token
    Given requesting to return user's id and email
    And confirming user with token "expiredToken" via graphQl
    When graphQL request is send
    Then graphql error message should be "Token not found"

  Scenario: Resend email to user
    Given requesting to return user's id and email
    And user with id "8be90127-9840-4235-a6da-39b8debfb113" exists
    And resending email to user with id "8be90127-9840-4235-a6da-39b8debfb113"
    When graphQL request is send
    Then mutation response should return requested fields

  Scenario: Resend email non-existing to user
    Given requesting to return user's id and email
    And resending email to user with id "8be90127-9840-4235-a6da-39b8debfb112"
    When graphQL request is send
    Then graphql error message should be 'Item "/api/users/8be90127-9840-4235-a6da-39b8debfb112" not found.'

  Scenario: Getting user
    Given requesting to return user's id and email
    And user with id "8be90127-9840-4235-a6da-39b8debfb113" exists
    And getting user with id "8be90127-9840-4235-a6da-39b8debfb113"
    When graphQL request is send
    Then query response should return requested fields

  Scenario: Getting non-existing user
    Given requesting to return user's id and email
    And getting user with id "8be90127-9840-4235-a6da-39b8debfb112"
    When graphQL request is send
    Then graphql response should be null

  Scenario: Getting a user with invalid uuid
    Given requesting to return user's id and email
    And getting user with id "8be90127-9840-4235-a6da-39b8debfb221a"
    When graphQL request is send
    Then graphql response should be null

  Scenario: Getting a user with invalid id
    Given requesting to return user's id and email
    And getting user with id "aaaaaa"
    When graphQL request is send
    Then graphql response should be null

  Scenario: Getting collection of users
    Given requesting to return user's id and email
    And getting collection of users
    When graphQL request is send
    Then collection of users should be returned
