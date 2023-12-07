Feature: User CRUD Operations
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
