Feature: User Operations
  In order to manage users
  As a system administrator
  I want to perform CRUD operations on user records

  Scenario: Retrieving the list of users
    When GET request is send to "https://localhost/api/users?page=1&itemsPerPage="
    Then the response status code should be 200
    And the response should contain a list of users

  Scenario: Retrieving the list of users with wrong params
    When GET request is send to "https://localhost/api/users?page=1&itemsPerPage=-100"
    Then the response status code should be 400

  Scenario: Creating a user
    Given creating user with email "test@mail.com", initials "initials", password "pass"
    When POST request is send to "https://localhost/api/users"
    Then the response status code should be 201
    And user should be returned

  Scenario: Creating a user with duplicate email
    Given creating user with email "test@mail.com", initials "initials", password "pass"
    When POST request is send to "https://localhost/api/users"
    Then the response status code should be 409

  Scenario: Creating a user with invalid email
    Given creating user with email "test", initials "initials", password "pass"
    When POST request is send to "https://localhost/api/users"
    Then the response status code should be 422

  Scenario: Creating a user with wrong input
    Given creating user with misformatted data
    When POST request is send to "https://localhost/api/users"
    Then the response status code should be 400

  Scenario: Getting a user
    Given user with id "getUserId" exists
    When GET request is send to "https://localhost/api/users/getUserId"
    Then the response status code should be 200
    And user should be returned

  Scenario: Getting a non-existing user
    When GET request is send to "https://localhost/api/users/nonExistingId"
    Then the response status code should be 404

  Scenario: Deleting a user
    Given user with id "deleteUserId" exists
    When DELETE request is send to "https://localhost/api/users/deleteUserId"
    Then the response status code should be 204

  Scenario: Deleting a non-existing user
    When DELETE request is send to "https://localhost/api/users/nonExistingId"
    Then the response status code should be 404

  Scenario: Replacing user
    Given user with id "putUserId" and password "pass" exists
    And updating user with email "testput@mail.com", initials "initials", oldPassword "pass", newPassword "pass"
    When PUT request is send to "https://localhost/api/users/putUserId"
    Then the response status code should be 200
    And user should be returned

  Scenario: Replacing user with wrong password
    Given user with id "putUserId" and password "pass" exists
    And updating user with email "testput@mail.com", initials "initials", oldPassword "wrongPass", newPassword "pass"
    When PUT request is send to "https://localhost/api/users/putUserId"
    Then the response status code should be 410

  Scenario: Replacing a non-existing user
    When PUT request is send to "https://localhost/api/users//nonExistingId"
    Then the response status code should be 404

  Scenario: Replacing user with duplicate email
    Given user with id "putUserId" and password "pass" exists
    And updating user with email "test@mail.com", initials "initials", oldPassword "pass", newPassword "pass"
    When PUT request is send to "https://localhost/api/users/putUserId"
    Then the response status code should be 409

  Scenario: Replacing a user with wrong input
    Given user with id "putUserId" exists
    And updating user with misformatted data
    When PUT request is send to "https://localhost/api/users/putUserId"
    Then the response status code should be 400

  Scenario: Replacing user with invalid email
    Given user with id "putUserId" exists
    And updating user with email "test", initials "initials", oldPassword "pass", newPassword "pass"
    When PUT request is send to "https://localhost/api/users/putUserId"
    Then the response status code should be 422

  Scenario: Updating user
    Given user with id "patchUserId" and password "pass" exists
    And updating user with email "testupdate@mail.com", initials "initials", oldPassword "pass", newPassword "pass"
    When PATCH request is send to "https://localhost/api/users/patchUserId"
    Then the response status code should be 200
    And user should be returned

  Scenario: Updating user with wrong password
    Given user with id "patchUserId" and password "pass" exists
    And updating user with email "testpatch@mail.com", initials "initials", oldPassword "wrongPass", newPassword "pass"
    When PATCH request is send to "https://localhost/api/users/patchUserId"
    Then the response status code should be 410

  Scenario: Updating a non-existing user
    When PATCH request is send to "https://localhost/api/users//nonExistingId"
    Then the response status code should be 404

  Scenario: Updating user with duplicate email
    Given user with id "patchUserId" and password "pass" exists
    And updating user with email "test@mail.com", initials "initials", oldPassword "pass", newPassword "pass"
    When PATCH request is send to "https://localhost/api/users/patchUserId"
    Then the response status code should be 409

  Scenario: Updating a user with wrong input
    Given user with id "patchUserId" exists
    And updating user with misformatted data
    When PATCH request is send to "https://localhost/api/users/patchUserId"
    Then the response status code should be 400

  Scenario: Updating user with invalid email
    Given user with id "patchUserId" exists
    And updating user with email "test", initials "initials", oldPassword "pass", newPassword "pass"
    When PATCH request is send to "https://localhost/api/users/patchUserId"
    Then the response status code should be 422

  Scenario: Resending email to user
    Given user with id "retryEmailUserId" exists
    When POST request is send to "https://localhost/api/users/retryEmailUserId/resend-confirmation-email"
    Then the response status code should be 200

  Scenario: Resending email to user while he's timed out
    Given user with id "retryEmailUserId" exists
    When POST request is send to "https://localhost/api/users/retryEmailUserId/resend-confirmation-email"
    And POST request is send to "https://localhost/api/users/retryEmailUserId/resend-confirmation-email"
    Then the response status code should be 429

  Scenario: Resending email to non-existing user
    When POST request is send to "https://localhost/api/users/nonExistingId/resend-confirmation-email"
    Then the response status code should be 404

  Scenario: Confirming user
    Given user with id "conformUserId" exists
    And user with id "conformUserId" has a confirmation token assigned to him
    When PATCH request is send to "https://localhost/api/users/confirm"
    Then the response status code should be 200

  Scenario: Confirming user with expired token
    When PATCH request is send to "https://localhost/api/users/confirm"
    Then the response status code should be 404
