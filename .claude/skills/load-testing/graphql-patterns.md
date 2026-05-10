# GraphQL Load Testing Patterns

## Script Structure Template

```javascript
import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import { randomString } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';

const scenarioName = 'graphQLOperationResource'; // e.g., 'graphQLCreateCustomer'

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Use REST API for faster setup of dependencies
  const dependencyData = { value: `TestDep_${Date.now()}` };
  const response = utils.createDependency(dependencyData);

  if (response.status === 201) {
    return { dependency: JSON.parse(response.body) };
  }

  return { dependency: null };
}

export default function graphQLOperationResource(data) {
  // Main GraphQL test logic
  // Note: buildGraphQLQuery must be implemented per scenario (see below)
  // Expected return: { query: string, variables?: object }
  const query = buildGraphQLQuery(data);
  const response = utils.executeGraphQL(query);

  utils.checkResponse(response, 'GraphQL operation successful', res => {
    if (res.status === 200) {
      const body = JSON.parse(res.body);
      return body.data && !body.errors;
    }
    return false;
  });
}

export function teardown(data) {
  // Use REST API for faster cleanup
  if (data.dependency) {
    try {
      const deleteResponse = http.del(`${utils.getBaseHttpUrl()}${data.dependency['@id']}`);
      if (
        deleteResponse.status !== 204 &&
        deleteResponse.status !== 200 &&
        deleteResponse.status !== 404
      ) {
        console.warn(
          `Failed to clean up dependency: ${deleteResponse.status} - ${data.dependency['@id']}`
        );
      } else if (deleteResponse.status === 404) {
        console.info(`Dependency already deleted: ${data.dependency['@id']}`);
      }
    } catch (e) {
      console.error(`Error deleting dependency ${data.dependency['@id']}: ${e.message}`);
    }
  }
}

function buildGraphQLQuery(data) {
  return {
    query: `mutation CreateResource($input: CreateResourceInput!) {
      createResource(input: $input) {
        resource {
          id
          name
        }
      }
    }`,
    variables: {
      input: {
        name: `Resource_${randomString(8)}`,
        dependency: data.dependency['@id'],
      },
    },
  };
}
```

## GraphQL Load Test Types

### 1. Mutation Operations (Create)

**Purpose**: Test resource creation via GraphQL mutations

```javascript
export default function graphQLCreateResource(data) {
  const mutation = {
    query: `mutation CreateCustomer($input: CreateCustomerInput!) {
      createCustomer(input: $input) {
        customer {
          id
          initials
          email
          type
          status
        }
      }
    }`,
    variables: {
      input: {
        initials: `Customer_${randomString(8)}`,
        email: `test_${Date.now()}@example.com`,
        phone: `+1-555-${Math.floor(Math.random() * 9000) + 1000}`,
        type: data.type['@id'], // Full IRI from setup phase, e.g., '/api/customer_types/01K85E...'
        status: data.status['@id'], // Full IRI from setup phase, e.g., '/api/customer_statuses/01K85E...'
        confirmed: true,
      },
    },
  };

  const response = utils.executeGraphQL(mutation);

  utils.checkResponse(response, 'customer created via GraphQL', res => {
    if (res.status === 200) {
      const body = JSON.parse(res.body);
      if (body.data?.createCustomer?.customer) {
        if (!data.createdCustomers) data.createdCustomers = [];
        data.createdCustomers.push(body.data.createCustomer.customer.id);
        return true;
      }
      if (body.errors) {
        console.error('GraphQL errors:', JSON.stringify(body.errors));
      }
    }
    return false;
  });
}
```

**Validation**:

- Check `response.status === 200`
- Verify `body.data` contains expected data
- Ensure `body.errors` is undefined or empty

### 2. Query Operations (Read)

**Get Single Resource**:

```javascript
export default function graphQLGetResource(data) {
  const query = {
    query: `query GetCustomer($id: ID!) {
      customer(id: $id) {
        id
        initials
        email
        type
        status
        confirmed
        createdAt
      }
    }`,
    variables: {
      id: data.customerIri,
    },
  };

  const response = utils.executeGraphQL(query);

  utils.checkResponse(response, 'customer fetched via GraphQL', res => {
    if (res.status === 200) {
      const body = JSON.parse(res.body);
      return body.data?.customer && !body.errors;
    }
    return false;
  });
}
```

**Get Collection with Filters**:

```javascript
export default function graphQLGetResources(data) {
  const query = {
    query: `query GetCustomers($page: Int, $itemsPerPage: Int, $status: String) {
      customers(page: $page, itemsPerPage: $itemsPerPage, status: $status) {
        collection {
          id
          initials
          email
          status
        }
        paginationInfo {
          itemsPerPage
          lastPage
          totalCount
        }
      }
    }`,
    variables: {
      page: 1,
      itemsPerPage: 30,
      status: data.status['@id'],
    },
  };

  const response = utils.executeGraphQL(query);

  utils.checkResponse(response, 'customers collection fetched', res => {
    if (res.status === 200) {
      const body = JSON.parse(res.body);
      return body.data?.customers?.collection && !body.errors;
    }
    return false;
  });
}
```

### 3. Mutation Operations (Update)

```javascript
export default function graphQLUpdateResource(data) {
  const mutation = {
    query: `mutation UpdateCustomer($input: UpdateCustomerInput!) {
      updateCustomer(input: $input) {
        customer {
          id
          initials
          email
        }
      }
    }`,
    variables: {
      input: {
        id: data.customerIri,
        initials: `Updated_${randomString(8)}`,
      },
    },
  };

  const response = utils.executeGraphQL(mutation);

  utils.checkResponse(response, 'customer updated via GraphQL', res => {
    if (res.status === 200) {
      const body = JSON.parse(res.body);
      return body.data?.updateCustomer?.customer && !body.errors;
    }
    return false;
  });
}
```

### 4. Mutation Operations (Delete)

```javascript
export default function graphQLDeleteResource(data) {
  const mutation = {
    query: `mutation DeleteCustomer($input: DeleteCustomerInput!) {
      deleteCustomer(input: $input) {
        customer {
          id
        }
      }
    }`,
    variables: {
      input: {
        id: data.customerIri,
      },
    },
  };

  const response = utils.executeGraphQL(mutation);

  utils.checkResponse(response, 'customer deleted via GraphQL', res => {
    if (res.status === 200) {
      const body = JSON.parse(res.body);
      return body.data?.deleteCustomer && !body.errors;
    }
    return false;
  });
}
```

## ID/IRI Handling in GraphQL

### GraphQL Uses Full IRI Format

Unlike REST where you can use just the IRI path, GraphQL queries/mutations expect the full IRI:

```javascript
// ✅ GOOD: Use full IRI in GraphQL
const mutation = {
  query: `mutation CreateCustomer($input: CreateCustomerInput!) { ... }`,
  variables: {
    input: {
      type: '/api/customer_types/01234', // Full IRI
      status: '/api/customer_statuses/56789',
    },
  },
};
```

### Extracting IRI from GraphQL Response

```javascript
// GraphQL response returns the full IRI
const body = JSON.parse(response.body);
const customerId = body.data.createCustomer.customer.id;
// customerId will be the full IRI: "/api/customers/01K85E6755EFKTKPFMK6WHF99V"

// Store for later use
data.createdCustomers.push(customerId);
```

### Using Stored IRIs in Subsequent Queries

```javascript
const query = {
  query: `query GetCustomer($id: ID!) {
    customer(id: $id) { ... }
  }`,
  variables: {
    id: data.customerIri, // Use stored IRI
  },
};
```

## Data Generation

### Realistic GraphQL Input Data

```javascript
function generateCustomerInput(data) {
  const domains = ['example.com', 'test.org', 'demo.net'];
  const leadSources = ['Website', 'Referral', 'Social Media'];
  const initials = `Customer_${randomString(8)}`;

  return {
    initials: initials,
    email: `${initials.toLowerCase()}@${domains[Math.floor(Math.random() * domains.length)]}`,
    phone: `+1-555-${Math.floor(Math.random() * 9000) + 1000}`,
    leadSource: leadSources[Math.floor(Math.random() * leadSources.length)],
    type: data.type['@id'],
    status: data.status['@id'],
    confirmed: Math.random() > 0.5,
    // Note: createdAt and updatedAt are set by the server
  };
}
```

### Handling Nested Objects

```javascript
function generateComplexInput(data) {
  return {
    customer: {
      initials: `Customer_${randomString(8)}`,
      email: `test_${Date.now()}@example.com`,
      address: {
        street: `${Math.floor(Math.random() * 9999)} Main St`,
        city: 'Test City',
        country: 'US',
        postalCode: `${Math.floor(Math.random() * 90000) + 10000}`,
      },
    },
    type: data.type['@id'],
  };
}
```

## Best Practices

### Response Validation

Always check both status and GraphQL-specific errors:

```javascript
utils.checkResponse(response, 'operation description', res => {
  if (res.status !== 200) {
    return false;
  }

  const body = JSON.parse(res.body);

  // Check for GraphQL errors
  if (body.errors && body.errors.length > 0) {
    console.error('GraphQL errors:', JSON.stringify(body.errors));
    return false;
  }

  // Check for expected data
  if (!body.data || !body.data.expectedField) {
    console.error('Missing expected data in response');
    return false;
  }

  return true;
});
```

### Use REST for Setup/Teardown

GraphQL is slower for bulk operations. Use REST API for setup/teardown:

```javascript
export function setup() {
  // ✅ GOOD: Use REST for faster setup
  const typeResponse = http.post(
    `${utils.getBaseHttpUrl()}/api/customer_types`,
    JSON.stringify({ value: `Type_${Date.now()}` }),
    utils.getJsonHeader()
  );

  return { type: JSON.parse(typeResponse.body) };
}

export function teardown(data) {
  // ✅ GOOD: Use REST for faster cleanup
  if (data.type) {
    http.del(`${utils.getBaseHttpUrl()}${data.type['@id']}`);
  }
}
```

### Error Handling

```javascript
const response = utils.executeGraphQL(mutation);

utils.checkResponse(response, 'operation successful', res => {
  if (res.status !== 200) {
    console.error(`HTTP error: ${res.status} ${res.statusText}`);
    return false;
  }

  try {
    const body = JSON.parse(res.body);

    if (body.errors) {
      body.errors.forEach(error => {
        console.error(`GraphQL error: ${error.message}`);
        if (error.extensions) {
          console.error('Extensions:', JSON.stringify(error.extensions));
        }
      });
      return false;
    }

    return body.data && body.data.expectedField;
  } catch (e) {
    console.error('Failed to parse response:', e);
    return false;
  }
});
```

### Query Variables Best Practices

```javascript
// ✅ GOOD: Use variables for all dynamic values
const query = {
  query: `query GetCustomers($status: String!, $page: Int) {
    customers(status: $status, page: $page) { ... }
  }`,
  variables: {
    status: data.status['@id'],
    page: 1,
  },
};

// ❌ BAD: Inline values in query string
const query = {
  query: `query {
    customers(status: "${data.status['@id']}", page: 1) { ... }
  }`,
};
```

### Field Selection

Only request fields you need to validate:

```javascript
// ✅ GOOD: Minimal field selection
const query = {
  query: `query GetCustomer($id: ID!) {
    customer(id: $id) {
      id
      initials
    }
  }`,
  variables: { id: customerId },
};

// ❌ BAD: Requesting all fields
const query = {
  query: `query GetCustomer($id: ID!) {
    customer(id: $id) {
      id
      initials
      email
      phone
      address
      type
      status
      confirmed
      createdAt
      updatedAt
      // ... many more fields
    }
  }`,
  variables: { id: customerId },
};
```

## Deterministic Operations

```javascript
// ✅ GOOD: Use iteration-based patterns
export default function graphQLMixedOperations(data) {
  const operationIndex = __ITER % 4;

  const operations = [
    () => graphQLCreateCustomer(data),
    () => graphQLGetCustomer(data),
    () => graphQLUpdateCustomer(data),
    () => graphQLDeleteCustomer(data),
  ];

  operations[operationIndex]();
}

// ❌ BAD: Random operations
const operation = Math.random(); // Never do this!
```

## Complete Reference

See `examples/graphql-customer-crud.js` for a complete working example.
