Feature: GraphQL Authentication Exclusion and Hardening
  In order to enforce REST-only authentication and protect against GraphQL abuse
  As the system
  I want auth operations excluded from GraphQL and query limits enforced

  # NFR-62: Auth operations excluded from GraphQL schema

  Scenario: Sign-in mutation is not exposed in GraphQL schema
    Given I am authenticated as user "gql-no-signin@test.com"
    When I send a GraphQL mutation "signIn" with email "test@test.com" and password "passWORD1"
    Then the GraphQL response should indicate the mutation does not exist

  Scenario: Complete 2FA mutation is not exposed in GraphQL schema
    Given I am authenticated as user "gql-no-2fa@test.com"
    When I send a GraphQL mutation "completeTwoFactor" with pending_session_id "id" and code "123456"
    Then the GraphQL response should indicate the mutation does not exist

  Scenario: Sign-out mutation is not exposed in GraphQL schema
    Given I am authenticated as user "gql-no-signout@test.com"
    When I send a GraphQL mutation "signOut"
    Then the GraphQL response should indicate the mutation does not exist

  Scenario: Sign-out-all mutation is not exposed in GraphQL schema
    Given I am authenticated as user "gql-no-signout-all@test.com"
    When I send a GraphQL mutation "signOutAll"
    Then the GraphQL response should indicate the mutation does not exist

  Scenario: 2FA setup mutation is not exposed in GraphQL schema
    Given I am authenticated as user "gql-no-2fa-setup@test.com"
    When I send a GraphQL mutation "setupTwoFactor"
    Then the GraphQL response should indicate the mutation does not exist

  Scenario: 2FA confirm mutation is not exposed in GraphQL schema
    Given I am authenticated as user "gql-no-2fa-confirm@test.com"
    When I send a GraphQL mutation "confirmTwoFactor" with code "123456"
    Then the GraphQL response should indicate the mutation does not exist

  Scenario: 2FA disable mutation is not exposed in GraphQL schema
    Given I am authenticated as user "gql-no-2fa-disable@test.com"
    When I send a GraphQL mutation "disableTwoFactor" with code "123456"
    Then the GraphQL response should indicate the mutation does not exist

  Scenario: Token refresh mutation is not exposed in GraphQL schema
    Given I am authenticated as user "gql-no-refresh@test.com"
    When I send a GraphQL mutation "refreshToken" with refresh_token "token"
    Then the GraphQL response should indicate the mutation does not exist

  Scenario: Recovery code regeneration mutation is not exposed in GraphQL schema
    Given I am authenticated as user "gql-no-recovery@test.com"
    When I send a GraphQL mutation "regenerateRecoveryCodes"
    Then the GraphQL response should indicate the mutation does not exist

  Scenario: Password reset mutation is not exposed in GraphQL schema
    Given I am authenticated as user "gql-no-reset@test.com"
    When I send a GraphQL mutation "resetPassword" with email "test@test.com"
    Then the GraphQL response should indicate the mutation does not exist

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

  Scenario: Unauthenticated GraphQL mutation returns error
    When I execute GraphQL mutation createUser with email "gql-unauth@test.com", initials "name surname", password "passWORD1"
    Then the response status code should be 401

  Scenario: Unauthenticated GraphQL query returns error
    When I send a GraphQL query for user collection
    Then the response status code should be 401

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
