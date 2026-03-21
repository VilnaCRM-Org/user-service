Feature: User GraphQL Operations Localization
  In order to internationalize the service
  As a user
  I want to receive messages in chosen language via GraphQL

  Scenario: Creating a user with duplicate email and Ukrainian language
    Given I am authenticated as user "gql-loc-auth@test.com"
    And requesting to return user's id and email
    And with graphql language "uk"
    And user with email "graphqltest@mail.com2" exists
    And creating user with email "graphqltest@mail.com2" initials "name surname" password "passWORD1"
    When graphQL request is send
    Then graphql error message should be "email: Ця email-адреса вже зареєстрована"

  Scenario: Creating a user with invalid email and Ukrainian language
    Given I am authenticated as user "gql-loc-auth2@test.com"
    And requesting to return user's id and email
    And with graphql language "uk"
    And creating user with email "graphqlTest" initials "name surname" password "passWORD1"
    When graphQL request is send
    Then graphql error message should be "email: Це значення не є дійсною електронною адресою."

  Scenario: Creating a user with password with no uppercase letters and Ukrainian language
    Given I am authenticated as user "gql-loc-auth3@test.com"
    And requesting to return user's id and email
    And with graphql language "uk"
    And creating user with email "graphqlTest@mail.com" initials "name surname" password "password1"
    When graphQL request is send
    Then graphql error message should be "password: Пароль має містити принаймні одну велику літеру"

  Scenario: Creating a user with password with no numbers and Ukrainian language
    Given I am authenticated as user "gql-loc-auth4@test.com"
    And requesting to return user's id and email
    And with graphql language "uk"
    And creating user with email "graphqlTest@mail.com" initials "name surname" password "passWORD"
    When graphQL request is send
    Then graphql error message should be "password: Пароль повинен містити хоча б одне число"

  Scenario: Creating a user with too short password and Ukrainian language
    Given I am authenticated as user "gql-loc-auth5@test.com"
    And requesting to return user's id and email
    And with graphql language "uk"
    And creating user with email "graphqlTest@mail.com" initials "name surname" password "WORD1"
    When graphQL request is send
    Then graphql error message should be "password: Пароль має містити від 8 до 64 символів"

  Scenario: Creating a user with initials that contains only spaces and Ukrainian language
    Given I am authenticated as user "gql-loc-auth6@test.com"
    And requesting to return user's id and email
    And with graphql language "uk"
    And creating user with email "graphqlTest@mail.com" initials " " password "passWORD1"
    When graphQL request is send
    Then graphql error message should be "initials: Ім'я та прізвище не можуть складатися лише з пробілів"

  Scenario: Updating user to duplicate email and Ukrainian language
    Given I am authenticated as user "gql-loc-auth7@test.com" with id "8be90127-9840-4235-a6da-39b8debfb111"
    And requesting to return user's id and email
    And with graphql language "uk"
    And user with email "testUpdateGraphQL2@mail.com" exists
    And user with id "8be90127-9840-4235-a6da-39b8debfb111" and password "passWORD1" exists
    And updating user with id "8be90127-9840-4235-a6da-39b8debfb111" and password "passWORD1" to new email "testUpdateGraphQL2@mail.com"
    When graphQL request is send
    Then graphql error message should be "email: Ця email-адреса вже зареєстрована"

  Scenario: Updating user with wrong password and Ukrainian language
    Given I am authenticated as user "gql-loc-auth8@test.com" with id "8be90127-9840-4235-a6da-39b8debfb111"
    And requesting to return user's id and email
    And with graphql language "uk"
    And user with id "8be90127-9840-4235-a6da-39b8debfb111" exists
    And updating user with id "8be90127-9840-4235-a6da-39b8debfb111" and password "wrongpassWORD1" to new email "testUpdateGraphQLWrongUk@mail.com"
    When graphQL request is send
    Then graphql error message should be "Старий пароль невірний"

  Scenario: Confirm with expired token and Ukrainian language
    Given I am authenticated as user "gql-loc-auth9@test.com"
    And requesting to return user's id and email
    And with graphql language "uk"
    And confirming user with token "expiredToken" via graphQl
    When graphQL request is send
    Then graphql error message should be "Токен не знайдено"

  Scenario: Updating a non-existing user and Ukrainian language
    Given I am authenticated as user "gql-loc-auth10@test.com"
    And requesting to return user's id and email
    And with graphql language "uk"
    And updating user with id "8be90127-9840-4235-a6da-39b8debfb112" and password "passWORD1" to new email "testUpdateGraphQL@mail.com"
    When graphQL request is send
    Then graphql error message should be 'Елемент "/api/users/8be90127-9840-4235-a6da-39b8debfb112" не знайдено.'

  Scenario: Deleting non-existing user and Ukrainian language
    Given I am authenticated as user "gql-loc-auth11@test.com"
    And requesting to return user's id
    And with graphql language "uk"
    And deleting user with id "8be90127-9840-4235-a6da-39b8debfb112"
    When graphQL request is send
    Then graphql error message should be 'Елемент "/api/users/8be90127-9840-4235-a6da-39b8debfb112" не знайдено.'

  Scenario: Resend email non-existing to user and Ukrainian language
    Given I am authenticated as user "gql-loc-auth12@test.com"
    And requesting to return user's id and email
    And with graphql language "uk"
    And resending email to user with id "8be90127-9840-4235-a6da-39b8debfb112"
    When graphQL request is send
    Then graphql error message should be 'Елемент "/api/users/8be90127-9840-4235-a6da-39b8debfb112" не знайдено.'
