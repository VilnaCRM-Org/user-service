This document outlines the API endpoints in the User Service, as defined in the OpenAPI specification and implemented in the codebase.

## OpenApi specification

You can go [here](https://github.com/VilnaCRM-Org/user-service/blob/main/.github/openapi-spec/spec.yaml), to get our OpenApi specification as a `.yaml` file, and then use [Swagger Editor](https://editor.swagger.io/) to browse it.

If you've set up User Service **locally**, you can check [this URL](https://localhost/api/docs) to see the same OpenApi specification with an interactive interface.

### How to read the documentation

You will see a list of endpoints, and by clicking on them, you can see detailed information about each of them, such as description, available input, and possible responses.

Here is an example:

![screenshot_1714132320565](https://github.com/VilnaCRM-Org/user-service/assets/81823080/cecace37-90f3-4073-ba6e-cd8becd6e872)

You can also send a request, by clicking the `Try it out` button in the upper right corner and providing required input.

### Authorization

To get access to protected resources, click on the `Authorize` button in the upper right corner. After providing the required client credentials you'll be authorized.

Here is how the form looks:

<img src="https://github.com/VilnaCRM-Org/user-service/assets/81823080/214d50d6-61fc-4a43-a04e-c16b40a31581" alt="Description of the image" width="700" height="600">

## GraphQL specification

You can go [here](https://github.com/VilnaCRM-Org/user-service/blob/main/.github/graphql-spec/spec), to get our GraphQL specification and then use [GraphQL Playground](https://graphql-kit.com/graphql-voyager/) to browse it.

If you've set up User Service **locally**, you can check [this URL](https://localhost/api/graphql/graphql_playground) to see the same specification with an interactive GraphQL playground interface.

### How to read the documentation

At the right side of the screen, you'll see a tab named `Docs`. There you'll find all the information about available Mutations and Queries.

Here is an example:

<img src="https://github.com/VilnaCRM-Org/user-service/assets/81823080/b995567f-b1ab-4de8-856b-1587122ae09c" alt="Description of the image" width="1100" height="800">

On the left side of the screen, you'll see a place for writing queries, from it you can send the requests.

Learn more about [GraphQl Queries and Mutations](https://graphql.org/learn/queries/).

### Authorization

At the bottom part of the screen, you'll see an `HTTP HEADERS` tab. You can pass your Access Token via the `Authorization` header to get access to protected resources.

Here is an example:

<img src="https://github.com/VilnaCRM-Org/user-service/assets/81823080/b908d54d-e07e-4f90-9048-a0c4c661f974" alt="Description of the image" width="800" height="200">

Learn more about [Developer Guide](developer-guide.md).

## GraphQL Password Reset Feature

The User Service now provides comprehensive password reset functionality through GraphQL mutations. This feature consists of two mutations that enable secure password recovery workflows.

### requestPasswordResetUser Mutation

Initiates a password reset request for a user by their email address. Sends a password reset token to the user's email.

**GraphQL Mutation:**
```graphql
mutation {
  requestPasswordResetUser(input: {email: "user@example.com"}) {
    user {
      id
      email
    }
  }
}
```

**Security Behavior:**
- Returns null payload for the `user` field (no data leak)
- Does not reveal whether the email exists in the system
- Executes without errors for both existing and non-existing users
- Sends email only to existing users

### confirmPasswordResetUser Mutation

Confirms the password reset using the token received via email and sets a new password.

**GraphQL Mutation:**
```graphql
mutation {
  confirmPasswordResetUser(input: {
    token: "abc123token",
    password: "NewSecurePassword123!"
  }) {
    user {
      id
      email
    }
  }
}
```

**Security Behavior:**
- Returns null payload for the `user` field (no data leak)
- Validates token existence and expiration
- Returns error for invalid or expired tokens
- Does not reveal user information

### Testing

Both mutations are comprehensively tested with Behat E2E tests covering:
- Successful password reset flow for existing users
- Security behavior for non-existing users
- Valid token confirmation
- Error handling for invalid/expired tokens

See [testing.md](testing.md) for detailed test scenarios.
