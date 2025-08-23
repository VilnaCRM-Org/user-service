Feature: Password Reset Operations
  In order to reset forgotten passwords
  As a user
  I want to request and confirm password reset operations

  Scenario: Request password reset for existing user via GraphQL
    Given requesting to return user's id and email
    And user with email "test@example.com" and password "oldPassword123" exists
    And requesting password reset for email "test@example.com"
    When graphQL request is send
    Then mutation response should return requested fields

  Scenario: Request password reset for non-existent user via GraphQL
    Given requesting to return user's id and email
    And requesting password reset for email "nonexistent@example.com"
    When graphQL request is send
    Then mutation response should return requested fields

  Scenario: Request password reset with invalid email via GraphQL
    Given requesting to return user's id and email
    And requesting password reset for email "invalid-email"
    When graphQL request is send
    Then graphql error message should be "email: This value is not a valid email address."

  Scenario: Confirm password reset with valid token via GraphQL
    Given requesting to return user's id and email
    And user with id "8be90127-9840-4235-a6da-39b8debfb113" and password "oldPassword123" exists
    And user with id "8be90127-9840-4235-a6da-39b8debfb113" has password reset token "validResetToken"
    And confirming password reset with token "validResetToken" and new password "newPassword456"
    When graphQL request is send
    Then mutation response should return requested fields

  Scenario: Confirm password reset with invalid token via GraphQL
    Given requesting to return user's id and email
    And confirming password reset with token "invalidToken" and new password "newPassword456"
    When graphQL request is send
    Then graphql error message should be "Token not found"

  Scenario: Confirm password reset with expired token via GraphQL
    Given requesting to return user's id and email
    And user with id "8be90127-9840-4235-a6da-39b8debfb113" exists
    And user with id "8be90127-9840-4235-a6da-39b8debfb113" has expired password reset token "expiredToken"
    And confirming password reset with token "expiredToken" and new password "newPassword456"
    When graphQL request is send
    Then graphql error message should be "Token has expired"

  Scenario: Confirm password reset with weak password via GraphQL
    Given requesting to return user's id and email
    And user with id "8be90127-9840-4235-a6da-39b8debfb113" exists
    And user with id "8be90127-9840-4235-a6da-39b8debfb113" has password reset token "validResetToken"
    And confirming password reset with token "validResetToken" and new password "weak"
    When graphQL request is send
    Then graphql error message should be "newPassword: Password must be between 8 and 64 characters long"

  Scenario: Confirm password reset with password missing uppercase via GraphQL
    Given requesting to return user's id and email
    And user with id "8be90127-9840-4235-a6da-39b8debfb113" exists
    And user with id "8be90127-9840-4235-a6da-39b8debfb113" has password reset token "validResetToken"
    And confirming password reset with token "validResetToken" and new password "password123"
    When graphQL request is send
    Then graphql error message should be "newPassword: Password must contain at least one uppercase letter"

  Scenario: Confirm password reset with password missing number via GraphQL
    Given requesting to return user's id and email
    And user with id "8be90127-9840-4235-a6da-39b8debfb113" exists
    And user with id "8be90127-9840-4235-a6da-39b8debfb113" has password reset token "validResetToken"
    And confirming password reset with token "validResetToken" and new password "Password"
    When graphQL request is send
    Then graphql error message should be "newPassword: Password must contain at least one number"