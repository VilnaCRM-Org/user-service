# API Endpoints Documentation

## REST API Endpoints

### When Adding New REST Endpoint

**Update**: `docs/api-endpoints.md`

1. **Endpoint Definition**:

````markdown
### POST /api/customers

Creates a new customer.

**Request**:
\```json
{
"name": "John Doe",
"email": "john@example.com"
}
\```

**Response**: 201 Created
\```json
{
"@id": "/api/customers/01HQ5ZK...",
"name": "John Doe",
"email": "john@example.com"
}
\```

**Errors**:

- 400: Invalid input
- 401: Unauthorized
- 409: Email already exists
````

1. **Generate OpenAPI Spec**:

```bash
make generate-openapi-spec
```

1. **Update User Guide**: Add usage examples to `docs/user-guide.md`

## GraphQL Operations

### When Adding New GraphQL Mutation/Query

**Update**: `docs/api-endpoints.md`

1. **Operation Definition**:

````markdown
### Mutation: createCustomer

\```graphql
mutation CreateCustomer($input: CreateCustomerInput!) {
createCustomer(input: $input) {
customer {
id
name
email
}
}
}
\```

**Input**:
\```json
{
"input": {
"name": "John Doe",
"email": "john@example.com"
}
}
\```
````

1. **Generate GraphQL Spec**:

```bash
make generate-graphql-spec
```

1. **Update User Guide**: Add client integration examples

## Authentication Documentation

**When auth requirements change**:

1. Update `docs/security.md` with auth flow
1. Update `docs/api-endpoints.md` with endpoint auth requirements
1. Update `docs/user-guide.md` with client examples
