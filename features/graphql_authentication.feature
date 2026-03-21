Feature: GraphQL Authentication and Hardening
  In order to support authenticated clients consistently
  As the system
  I want auth operations available in GraphQL and query limits enforced

  # NFR-62: Auth operations available in GraphQL schema

  Scenario: Auth mutations are exposed in GraphQL schema
    Given I am authenticated as user "gql-auth-schema@test.com"
    When I send a GraphQL introspection query for mutation types
    Then the response should contain "signInUser" mutation
    And the response should contain "completeTwoFactorUser" mutation
    And the response should contain "signOutUser" mutation
    And the response should contain "signOutAllUser" mutation
    And the response should contain "setupTwoFactorUser" mutation
    And the response should contain "confirmTwoFactorUser" mutation
    And the response should contain "disableTwoFactorUser" mutation
    And the response should contain "refreshTokenUser" mutation
    And the response should contain "regenerateRecoveryCodesUser" mutation
    And the response should contain "requestPasswordResetUser" mutation
    And the response should contain "confirmPasswordResetUser" mutation

  Scenario: Sign-in mutation returns issued tokens
    Given user with email "gql-signin@test.com" and password "passWORD1" exists
    When I send a GraphQL mutation "signIn" with email "gql-signin@test.com" and password "passWORD1"
    Then the response status code should be 200
    And the GraphQL auth response should contain issued tokens

  Scenario: Sign-in mutation returns a pending two-factor session when 2FA is enabled
    Given user with email "gql-signin-2fa@test.com" and password "passWORD1" exists
    And user with email "gql-signin-2fa@test.com" has 2FA enabled
    When I send a GraphQL mutation "signIn" with email "gql-signin-2fa@test.com" and password "passWORD1"
    Then the response status code should be 200
    And the GraphQL auth response should contain a pending two-factor session

  Scenario: Setup 2FA mutation returns setup details
    Given I am authenticated as user "gql-setup-2fa@test.com"
    When I send a GraphQL mutation "setupTwoFactor"
    Then the response status code should be 200
    And the GraphQL auth response should contain setup details

  Scenario: Sign-out mutation succeeds
    Given I am authenticated as user "gql-signout@test.com"
    When I send a GraphQL mutation "signOut"
    Then the response status code should be 200
    And the GraphQL auth response should indicate success

  # NFR-62: CRUD mutations still work via GraphQL

  Scenario: User creation via GraphQL still works
    Given I am authenticated as user "gql-create-ok@test.com"
    When I execute GraphQL mutation createUser with email "gql-created@test.com", initials "name surname", password "passWORD1"
    Then the GraphQL response should contain a valid user

  Scenario: User update via GraphQL still works for own resource
    Given I am authenticated as user "gql-update-ok@test.com" with id "8be90127-9840-4235-a6da-39b8debfb300"
    And user with id "8be90127-9840-4235-a6da-39b8debfb300" and password "passWORD1" exists
    When I execute GraphQL mutation updateUser for user "8be90127-9840-4235-a6da-39b8debfb300"
    Then the GraphQL response should contain the updated user

  Scenario: User deletion via GraphQL still works for own resource
    Given I am authenticated as user "gql-delete-ok@test.com" with id "8be90127-9840-4235-a6da-39b8debfb301"
    And user with id "8be90127-9840-4235-a6da-39b8debfb301" exists
    When I execute GraphQL mutation deleteUser for user "8be90127-9840-4235-a6da-39b8debfb301"
    Then the GraphQL response should confirm deletion

  Scenario: User collection query via GraphQL still works
    Given I am authenticated as user "gql-collection-ok@test.com"
    When I send a GraphQL query for user collection
    Then the response status code should be 200
    And the GraphQL response should contain user data

  # NFR-35: GraphQL query depth boundary cases

  Scenario: GraphQL query with depth exactly 20 is accepted
    Given I am authenticated as user "gql-depth-20@test.com"
    When I send a GraphQL query with depth exactly 20
    Then the response status code should be 200

  Scenario: GraphQL query with depth 21 is rejected
    Given I am authenticated as user "gql-depth-21@test.com"
    When I send a GraphQL query with depth greater than 20
    Then the response should contain a GraphQL error about depth

  # NFR-36: GraphQL query complexity boundary cases

  Scenario: GraphQL query with complexity exactly 500 is accepted
    Given I am authenticated as user "gql-complex-500@test.com"
    When I send a GraphQL query with complexity exactly 500
    Then the response status code should be 200

  Scenario: GraphQL query with complexity 501 is rejected
    Given I am authenticated as user "gql-complex-501@test.com"
    When I send a GraphQL query with complexity greater than 500
    Then the response should contain a GraphQL error about complexity

  # NFR-35+36: Combined depth and complexity limits

  Scenario: GraphQL query that exceeds both depth and complexity is rejected
    Given I am authenticated as user "gql-both-limits@test.com"
    When I send a GraphQL query with depth 25 and complexity 600
    Then the response should contain a GraphQL error

  # NFR-59: GraphQL batch defense edge cases

  Scenario: GraphQL batch request with 2 queries is rejected
    Given I am authenticated as user "gql-batch-2@test.com"
    When I send a GraphQL batch request with 2 queries as JSON array
    Then the response status code should be 400

  Scenario: GraphQL batch request with empty array is rejected
    Given I am authenticated as user "gql-batch-empty@test.com"
    When I send a GraphQL batch request as empty JSON array
    Then the response status code should be 400

  Scenario: GraphQL request with valid single query object succeeds
    Given I am authenticated as user "gql-single-obj@test.com"
    When I send a single GraphQL query as JSON object
    Then the response status code should be 200

  # GraphQL authentication requirement

  Scenario: Unauthenticated GraphQL mutation returns authorization error
    When I send a GraphQL mutation "setupTwoFactor"
    Then the response status code should be 200
    And the GraphQL response should contain an authorization error

  Scenario: Unauthenticated GraphQL query returns authorization error
    When I send a GraphQL query for user collection
    Then the response status code should be 200
    And the GraphQL response should contain an authorization error

  # NFR-24: GraphQL introspection control

  Scenario: GraphQL introspection disabled in production environment
    Given the application environment is "prod"
    And I am authenticated as user "gql-intro-prod@test.com"
    When I send a GraphQL introspection query
    Then the response should not contain "__schema"

  Scenario: GraphQL introspection enabled in development environment
    Given the application environment is "dev"
    And I am authenticated as user "gql-intro-dev@test.com"
    When I send a GraphQL introspection query
    Then the response should contain "__schema"

  # GraphQL error format

  Scenario: GraphQL validation error uses standard GraphQL error format
    Given I am authenticated as user "gql-err-format@test.com"
    When I execute GraphQL mutation createUser with email "invalid-email", initials "name surname", password "passWORD1"
    Then the GraphQL response should contain "errors"
    And the GraphQL error should contain "message"

  Scenario: GraphQL depth error uses standard GraphQL error format
    Given I am authenticated as user "gql-depth-err@test.com"
    When I send a GraphQL query with depth greater than 20
    Then the GraphQL response should contain "errors"
    And the GraphQL error should contain "message"
