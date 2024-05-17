Feature: User Operations
  In order to manage users
  As a system administrator
  I want to perform CRUD operations on user records

  Scenario: Retrieving the list of users
    When GET request is send to "/api/users?page=1&itemsPerPage="
    Then the response status code should be 200
    And the response should contain a list of users

  Scenario: Retrieving the list of users with wrong params
    When GET request is send to "/api/users?page=1&itemsPerPage=-100"
    Then the response status code should be 400

  Scenario: Creating a user
    Given creating user with email "test@mail.com", initials "name surname", password "passWORD1"
    When POST request is send to "/api/users"
    Then the response status code should be 201
    And user with email "test@mail.com" and initials "name surname" should be returned

  Scenario: Creating a user with duplicate email
    Given user with email "test2@mail.com" exists
    And creating user with email "test2@mail.com", initials "name surname", password "passWORD1"
    When POST request is send to "/api/users"
    Then the response status code should be 422
    And violation should be "This email address is already registered"

  Scenario: Creating a user with invalid email
    Given creating user with email "test", initials "name surname", password "passWORD1"
    When POST request is send to "/api/users"
    Then the response status code should be 422
    And violation should be "This value is not a valid email address."

  Scenario: Creating a user with password with no uppercase letters
    Given creating user with email "testPass1@mail.com", initials "name surname", password "password1"
    When POST request is send to "/api/users"
    Then the response status code should be 422
    And violation should be "Password must contain at least one uppercase letter"

  Scenario: Creating a user with password with no numbers
    Given creating user with email "testPass2@mail.com", initials "name surname", password "passWORD"
    When POST request is send to "/api/users"
    Then the response status code should be 422
    And violation should be "Password must contain at least one number"

  Scenario: Creating a user with too short password
    Given creating user with email "testPass3@mail.com", initials "name surname", password "pass"
    When POST request is send to "/api/users"
    Then the response status code should be 422
    And violation should be "Password must be between 8 and 64 characters long"

  Scenario: Creating a user with initials that contains only spaces
    Given creating user with email "testPass3@mail.com", initials " ", password "passWORD1"
    When POST request is send to "/api/users"
    Then the response status code should be 422
    And violation should be "Initials can not consist only of spaces"

  Scenario: Creating a user with no input
    Given sending empty body
    When POST request is send to "/api/users"
    Then the response status code should be 422
    And violation should be "This value should not be blank."
    And violation should be "This value should not be blank."
    And violation should be "This value should not be blank."

  Scenario: Creating a batch of users
    Given sending a batch of users
    And with user with email "test1@mail.com", initials "name surname", password "passWORD1"
    And with user with email "test2@mail.com", initials "name surname", password "passWORD1"
    When POST request is send to "/api/users/batch"
    Then the response status code should be 201
    And the response should contain a list of users

  Scenario: Creating a batch of users with duplicate email
    Given user with email "test@mail.com" exists
    And sending a batch of users
    And with user with email "test@mail.com", initials "name surname", password "passWORD1"
    When POST request is send to "/api/users/batch"
    Then the response status code should be 422
    And violation should be "This email address is already registered"

  Scenario: Creating a batch of users invalid email
    Given sending a batch of users
    And with user with email "test", initials "name surname", password "passWORD1"
    When POST request is send to "/api/users/batch"
    Then the response status code should be 422
    And violation should be "This value is not a valid email address."

  Scenario: Creating a batch of users with password with no uppercase letters
    Given sending a batch of users
    And with user with email "test@mail.com", initials "name surname", password "password1"
    When POST request is send to "/api/users/batch"
    Then the response status code should be 422
    And violation should be "Password must contain at least one uppercase letter"

  Scenario: Creating a batch of users with password with no numbers
    Given sending a batch of users
    And with user with email "test@mail.com", initials "name surname", password "passWORD"
    When POST request is send to "/api/users/batch"
    Then the response status code should be 422
    And violation should be "Password must contain at least one number"

  Scenario: Creating a batch of users with too short password
    Given sending a batch of users
    And with user with email "test@mail.com", initials "name surname", password "pAss1"
    When POST request is send to "/api/users/batch"
    Then the response status code should be 422
    And violation should be "Password must be between 8 and 64 characters long"

  Scenario: Creating a batch of users with initials that contains only spaces
    Given sending a batch of users
    And with user with email "test@mail.com", initials " ", password "pAss1"
    When POST request is send to "/api/users/batch"
    Then the response status code should be 422
    And violation should be "Initials can not consist only of spaces"

  Scenario: Creating a batch of users with an empty batch
    Given sending a batch of users
    When POST request is send to "/api/users/batch"
    Then the response status code should be 422
    And violation should be "Batch should have at least one user"

  Scenario: Creating a batch of users with a duplicate emails in a batch
    Given sending a batch of users
    And with user with email "test@mail.com", initials "name surname", password "passWORD1"
    And with user with email "test@mail.com", initials "name surname", password "passWORD1"
    When POST request is send to "/api/users/batch"
    Then the response status code should be 422
    And violation should be "Duplicate email in a batch"

  Scenario: Getting a user
    Given user with id "8be90127-9840-4235-a6da-39b8debfb220" exists
    When GET request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb220"
    Then the response status code should be 200
    And user with id "8be90127-9840-4235-a6da-39b8debfb220" should be returned

  Scenario: Getting a non-existing user
    When GET request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb221"
    Then the response status code should be 404
    And the error message should be "Not Found"

  Scenario: Getting a user with invalid uuid
    When GET request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb221a"
    Then the response status code should be 404
    And the error message should be "Not Found"

  Scenario: Getting a user with invalid id
    When GET request is send to "/api/users/aaaaaa"
    Then the response status code should be 404
    And the error message should be "Not Found"

  Scenario: Deleting a user
    Given user with id "8be90127-9840-4235-a6da-39b8debfb220" exists
    When DELETE request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb220"
    Then the response status code should be 204

  Scenario: Deleting a non-existing user
    When DELETE request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb221"
    Then the response status code should be 404
    And the error message should be "Not Found"

  Scenario: Replacing user
    Given user with id "8be90127-9840-4235-a6da-39b8debfb222" and password "passWORD1" exists
    And updating user with email "testput@mail.com", initials "name surname", oldPassword "passWORD1", newPassword "passWORD2"
    When PUT request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb222"
    Then the response status code should be 200
    And user with id "8be90127-9840-4235-a6da-39b8debfb222" should be returned

  Scenario: Replacing user with wrong password
    Given user with id "8be90127-9840-4235-a6da-39b8debfb222" and password "passWORD1" exists
    And updating user with email "testput@mail.com", initials "name surname", oldPassword "wrongpassWORD1", newPassword "passWORD12"
    When PUT request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb222"
    Then the response status code should be 400
    And the error message should be "Old password is invalid"

  Scenario: Replacing a non-existing user
    When PUT request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb221"
    Then the response status code should be 404
    And the error message should be "Not Found"

  Scenario: Replacing user with duplicate email
    Given user with id "8be90127-9840-4235-a6da-39b8debfb222" and password "passWORD1" exists
    And user with email "test3@mail.com" exists
    And updating user with email "test3@mail.com", initials "name surname", oldPassword "passWORD1", newPassword "passWORD1"
    When PUT request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb222"
    Then the response status code should be 422
    And violation should be "This email address is already registered"

  Scenario: Replacing user with invalid email
    Given user with id "8be90127-9840-4235-a6da-39b8debfb222" exists
    And updating user with email "test", initials "name surname", oldPassword "passWORD1", newPassword "passWORD1"
    When PUT request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb222"
    Then the response status code should be 422
    And violation should be "This value is not a valid email address."

  Scenario: Replacing a user with no input
    Given user with id "8be90127-9840-4235-a6da-39b8debfb222" exists
    And sending empty body
    When PUT request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb222"
    Then the response status code should be 422
    And violation should be "This value should not be blank."
    And violation should be "This value should not be blank."
    And violation should be "This value should not be blank."
    And violation should be "This value should not be blank."

  Scenario: Updating user
    Given user with id "8be90127-9840-4235-a6da-39b8debfb222" and password "passWORD1" exists
    And updating user with email "testupdate@mail.com", initials "name surname", oldPassword "passWORD1", newPassword "passWORD1"
    When PATCH request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb222"
    Then the response status code should be 200
    And user with id "8be90127-9840-4235-a6da-39b8debfb222" should be returned

  Scenario: Updating user without optional fields
    Given user with id "8be90127-9840-4235-a6da-39b8debfb222" and password "passWORD1" exists
    And updating user with oldPassword "passWORD1"
    When PATCH request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb222"
    Then the response status code should be 200
    And user with id "8be90127-9840-4235-a6da-39b8debfb222" should be returned

  Scenario: Updating user with wrong password
    Given user with id "8be90127-9840-4235-a6da-39b8debfb222" and password "passWORD1" exists
    And updating user with email "testpatch@mail.com", initials "name surname", oldPassword "wrongpassWORD1", newPassword "passWORD1"
    When PATCH request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb222"
    Then the response status code should be 400
    And the error message should be "Old password is invalid"

  Scenario: Updating a non-existing user
    When PATCH request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb221"
    Then the response status code should be 404
    And the error message should be "Not Found"

  Scenario: Updating user with duplicate email
    Given user with id "8be90127-9840-4235-a6da-39b8debfb222" and password "passWORD1" exists
    And user with email "test4@mail.com" exists
    And updating user with email "test4@mail.com", initials "name surname", oldPassword "passWORD1", newPassword "passWORD1"
    When PATCH request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb222"
    Then the response status code should be 422
    And violation should be "This email address is already registered"

  Scenario: Updating user with invalid email
    Given user with id "8be90127-9840-4235-a6da-39b8debfb222" exists
    And updating user with email "test", initials "name surname", oldPassword "passWORD1", newPassword "passWORD1"
    When PATCH request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb222"
    Then the response status code should be 422
    And violation should be "This value is not a valid email address."

  Scenario: Updating user with no input
    Given user with id "8be90127-9840-4235-a6da-39b8debfb222" exists
    And sending empty body
    When PATCH request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb222"
    Then the response status code should be 422
    And violation should be "This value should not be blank."

  Scenario: Resending email to user
    Given user with id "8be90127-9840-4235-a6da-39b8debfb222" exists
    When POST request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb222/resend-confirmation-email"
    Then the response status code should be 200

  Scenario: Resending email to user while he's timed out
    Given user with id "8be90127-9840-4235-a6da-39b8debfb222" exists
    When POST request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb222/resend-confirmation-email"
    And POST request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb222/resend-confirmation-email"
    Then the response status code should be 429
    And user should be timed out

  Scenario: Resending email to non-existing user
    When POST request is send to "/api/users/8be90127-9840-4235-a6da-39b8debfb221/resend-confirmation-email"
    Then the response status code should be 404
    And the error message should be "User not found"

  Scenario: Confirming user
    Given user with id "8be90127-9840-4235-a6da-39b8debfb223" exists
    And user with id "8be90127-9840-4235-a6da-39b8debfb223" has confirmation token "confirmationToken"
    And confirming user with token "confirmationToken"
    When PATCH request is send to "/api/users/confirm"
    Then the response status code should be 200

  Scenario: Confirming user with expired token
    Given confirming user with token "expiredToken"
    When PATCH request is send to "/api/users/confirm"
    Then the response status code should be 404
    And the error message should be "Token not found"

  Scenario: Confirming user with no input
    Given sending empty body
    When PATCH request is send to "/api/users/confirm"
    Then the response status code should be 422
    And violation should be "This value should not be blank."

