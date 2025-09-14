Feature: User Operations Localization
  In order to internationalize the service
  As a user
  I want to receive messages in chosen language

  Scenario: Creating a user with duplicate email and Ukrainian language
    Given user with email "test@mail.com" exists
    And with language "uk"
    And creating user with email "test@mail.com", initials "namesurname", password "passWORD1"
    When POST request is send to "/api/users"
    Then the response status code should be 422
    And violation should be "Ця email-адреса вже зареєстрована"

  Scenario: Creating a user with invalid email and Ukrainian language
    Given creating user with email "test", initials "namesurname", password "passWORD1"
    And with language "uk"
    When POST request is send to "/api/users"
    Then the response status code should be 422
    And violation should be "Це значення не є дійсною електронною адресою."

  Scenario: Creating a user with password with no uppercase letters and Ukrainian language
    Given creating user with email "testPass1@mail.com", initials "namesurname", password "password1"
    And with language "uk"
    When POST request is send to "/api/users"
    Then the response status code should be 422
    And violation should be "Пароль має містити принаймні одну велику літеру"

  Scenario: Creating a user with password with no numbers and Ukrainian language
    Given creating user with email "testPass2@mail.com", initials "namesurname", password "passWORD"
    And with language "uk"
    When POST request is send to "/api/users"
    Then the response status code should be 422
    And violation should be "Пароль повинен містити хоча б одне число"

  Scenario: Creating a user with too short password and Ukrainian language
    Given creating user with email "testPass3@mail.com", initials "namesurname", password "pass"
    And with language "uk"
    When POST request is send to "/api/users"
    Then the response status code should be 422
    And violation should be "Пароль має містити від 8 до 64 символів"

  Scenario: Creating a user with initials that contains only spaces and Ukrainian language
    Given creating user with email "testPass3@mail.com", initials " ", password "pass"
    And with language "uk"
    When POST request is send to "/api/users"
    Then the response status code should be 422
    And violation should be "Ім'я та прізвище не можуть складатися лише з пробілів"

  Scenario: Creating a user with no input and Ukrainian language
    Given sending empty body
    And with language "uk"
    When POST request is send to "/api/users"
    Then the response status code should be 422
    And violation should be "Це значення не має бути пустим."
    And violation should be "Це значення не має бути пустим."
    And violation should be "Це значення не має бути пустим."

  Scenario: Creating a batch of users and Ukrainian language
    Given sending a batch of users
    And with language "uk"
    And with user with email "test1@mail.com", initials "namesurname", password "passWORD1"
    And with user with email "test2@mail.com", initials "namesurname", password "passWORD1"
    When POST request is send to "/api/users/batch"
    Then the response status code should be 201
    And the response should contain a list of users

  Scenario: Creating a batch of users with duplicate email and Ukrainian language
    Given user with email "test@mail.com" exists
    And with language "uk"
    And sending a batch of users
    And with user with email "test@mail.com", initials "namesurname", password "passWORD1"
    When POST request is send to "/api/users/batch"
    Then the response status code should be 422
    And violation should be "Ця email-адреса вже зареєстрована"

  Scenario: Creating a batch of users invalid email and Ukrainian language
    Given sending a batch of users
    And with language "uk"
    And with user with email "test", initials "namesurname", password "passWORD1"
    When POST request is send to "/api/users/batch"
    Then the response status code should be 422
    And violation should be "Це значення не є дійсною електронною адресою."

  Scenario: Creating a batch of users with password with no uppercase letters and Ukrainian language
    Given sending a batch of users
    And with language "uk"
    And with user with email "test@mail.com", initials "namesurname", password "password1"
    When POST request is send to "/api/users/batch"
    Then the response status code should be 422
    And violation should be "Пароль має містити принаймні одну велику літеру"

  Scenario: Creating a batch of users with password with no numbers and Ukrainian language
    Given sending a batch of users
    And with language "uk"
    And with user with email "test@mail.com", initials "namesurname", password "passWORD"
    When POST request is send to "/api/users/batch"
    Then the response status code should be 422
    And violation should be "Пароль повинен містити хоча б одне число"

  Scenario: Creating a batch of users with too short password and Ukrainian language
    Given sending a batch of users
    And with language "uk"
    And with user with email "test@mail.com", initials "namesurname", password "pAss1"
    When POST request is send to "/api/users/batch"
    Then the response status code should be 422
    And violation should be "Пароль має містити від 8 до 64 символів"

  Scenario: Creating a batch of users with initials that contains only spaces and Ukrainian language
    Given sending a batch of users
    And with language "uk"
    And with user with email "test@mail.com", initials " ", password "pAss1"
    When POST request is send to "/api/users/batch"
    Then the response status code should be 422
    And violation should be "Ім'я та прізвище не можуть складатися лише з пробілів"

  Scenario: Creating a batch of users with an empty batch and Ukrainian language
    Given sending a batch of users
    And with language "uk"
    When POST request is send to "/api/users/batch"
    Then the response status code should be 422
    And violation should be "Група повинна мати принаймні одного користувача"

  Scenario: Creating a batch of users with a duplicate emails in a batch and Ukrainian language
    Given sending a batch of users
    And with language "uk"
    And with user with email "test@mail.com", initials "namesurname", password "passWORD1"
    And with user with email "test@mail.com", initials "namesurname", password "passWORD1"
    When POST request is send to "/api/users/batch"
    Then the response status code should be 422
    And violation should be "Дублікат електронної пошти в групі"

  Scenario: Replacing user with wrong password and Ukrainian language
    Given user with id "8be90127-9840-4235-a6da-39b8debfb222" and password "passWORD1" exists
    And with language "uk"
    And updating user with email "testput@mail.com", initials "namesurname", oldPassword "wrongpassWORD1", newPassword "passWORD12"
    When PUT request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb222"
    Then the response status code should be 400
    And the error message should be "Старий пароль невірний"

  Scenario: Replacing user with duplicate email and Ukrainian language
    Given user with id "8be90127-9840-4235-a6da-39b8debfb222" and password "passWORD1" exists
    And with language "uk"
    And user with email "test3@mail.com" exists
    And updating user with email "test3@mail.com", initials "namesurname", oldPassword "passWORD1", newPassword "passWORD1"
    When PUT request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb222"
    Then the response status code should be 422
    And violation should be "Ця email-адреса вже зареєстрована"

  Scenario: Replacing user with invalid email and Ukrainian language
    Given user with id "8be90127-9840-4235-a6da-39b8debfb222" exists
    And with language "uk"
    And updating user with email "test", initials "namesurname", oldPassword "passWORD1", newPassword "passWORD1"
    When PUT request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb222"
    Then the response status code should be 422
    And violation should be "Це значення не є дійсною електронною адресою."

  Scenario: Replacing a user with no input and Ukrainian language
    Given user with id "8be90127-9840-4235-a6da-39b8debfb222" exists
    And with language "uk"
    And sending empty body
    When PUT request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb222"
    Then the response status code should be 422
    And violation should be "Це значення не має бути пустим."
    And violation should be "Це значення не має бути пустим."
    And violation should be "Це значення не має бути пустим."
    And violation should be "Це значення не має бути пустим."

  Scenario: Updating user with wrong password and Ukrainian language
    Given user with id "8be90127-9840-4235-a6da-39b8debfb222" and password "passWORD1" exists
    And with language "uk"
    And updating user with email "testpatch@mail.com", initials "namesurname", oldPassword "wrongpassWORD1", newPassword "passWORD1"
    When PATCH request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb222"
    Then the response status code should be 400
    And the error message should be "Старий пароль невірний"

  Scenario: Updating user with duplicate email and Ukrainian language
    Given user with id "8be90127-9840-4235-a6da-39b8debfb222" and password "passWORD1" exists
    And with language "uk"
    And user with email "test4@mail.com" exists
    And updating user with email "test4@mail.com", initials "namesurname", oldPassword "passWORD1", newPassword "passWORD1"
    When PATCH request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb222"
    Then the response status code should be 422
    And violation should be "Ця email-адреса вже зареєстрована"

  Scenario: Updating user with invalid email and Ukrainian language
    Given user with id "8be90127-9840-4235-a6da-39b8debfb222" exists
    And with language "uk"
    And updating user with email "test", initials "namesurname", oldPassword "passWORD1", newPassword "passWORD1"
    When PATCH request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb222"
    Then the response status code should be 422
    And violation should be "Це значення не є дійсною електронною адресою."

  Scenario: Updating user with no input and Ukrainian language
    Given user with id "8be90127-9840-4235-a6da-39b8debfb222" exists
    And with language "uk"
    And sending empty body
    When PATCH request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb222"
    Then the response status code should be 422
    And violation should be "Це значення не має бути пустим."

  Scenario: Confirming user with expired token and Ukrainian language
    Given confirming user with token "expiredToken"
    And with language "uk"
    When PATCH request is send to "/api/users/confirm"
    Then the response status code should be 404
    And the error message should be "Токен не знайдено"

  Scenario: Confirming user with no input and Ukrainian language
    Given sending empty body
    And with language "uk"
    When PATCH request is send to "/api/users/confirm"
    Then the response status code should be 422
    And violation should be "Це значення не має бути пустим."

  Scenario: Getting a non-existing user and Ukrainian language
    Given with language "uk"
    When GET request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb221"
    Then the response status code should be 404
    And the error message should be "Не знайдено"

  Scenario: Getting a user with invalid uuid and Ukrainian language
    Given with language "uk"
    When GET request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb221a"
    Then the response status code should be 404
    And the error message should be "Не знайдено"

  Scenario: Getting a user with invalid id and Ukrainian language
    Given with language "uk"
    When GET request is send to "/api/users/aaaaaa"
    Then the response status code should be 404
    And the error message should be "Не знайдено"

  Scenario: Deleting a non-existing user and Ukrainian language
    Given with language "uk"
    When DELETE request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb221"
    Then the response status code should be 404
    And the error message should be "Не знайдено"

  Scenario: Replacing a non-existing user and Ukrainian language
    Given with language "uk"
    When PUT request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb221"
    Then the response status code should be 404
    And the error message should be "Не знайдено"

  Scenario: Updating a non-existing user and Ukrainian language
    Given with language "uk"
    When PATCH request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb221"
    Then the response status code should be 404
    And the error message should be "Не знайдено"

  Scenario: Resending email to non-existing user and Ukrainian language
    Given with language "uk"
    When POST request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb221/resend-confirmation-email"
    Then the response status code should be 404
    And the error message should be "Користувача не знайдено"