# Extending the Utils Class

## Overview

The `Utils` class in `tests/Load/utils/utils.js` provides common functionality for K6 load tests. You can extend it for project-specific needs.

## Current Utils Class Structure

```javascript
class Utils {
  getBaseHttpUrl() {
    /* ... */
  }
  getJsonHeader() {
    /* ... */
  }
  getMergePatchHeader() {
    /* ... */
  }
  checkResponse(response, description, validator) {
    /* ... */
  }
  executeGraphQL(query) {
    /* ... */
  }
}
```

## Extension Pattern

### Option 1: Subclass Utils

Create domain-specific utilities by extending Utils:

```javascript
// tests/Load/utils/customerUtils.js
import Utils from './utils.js';
import http from 'k6/http';
import { randomString } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';

export default class CustomerUtils extends Utils {
  /**
   * Create a customer type via REST API
   */
  createCustomerType(value) {
    const data = { value: value || `Type_${Date.now()}` };
    return http.post(
      `${this.getBaseHttpUrl()}/api/customer_types`,
      JSON.stringify(data),
      this.getJsonHeader()
    );
  }

  /**
   * Create a customer status via REST API
   */
  createCustomerStatus(value) {
    const data = { value: value || `Status_${Date.now()}` };
    return http.post(
      `${this.getBaseHttpUrl()}/api/customer_statuses`,
      JSON.stringify(data),
      this.getJsonHeader()
    );
  }

  /**
   * Generate realistic customer data
   */
  generateCustomerData(typeIri, statusIri) {
    const domains = ['example.com', 'test.org', 'demo.net'];
    const leadSources = ['Website', 'Referral', 'Social Media'];
    const initials = `Customer_${randomString(8)}`;

    return {
      initials: initials,
      email: `${initials.toLowerCase()}@${domains[Math.floor(Math.random() * domains.length)]}`,
      phone: `+1-555-${Math.floor(Math.random() * 9000) + 1000}`,
      leadSource: leadSources[Math.floor(Math.random() * leadSources.length)],
      type: typeIri,
      status: statusIri,
      confirmed: Math.random() > 0.5,
      // Note: createdAt and updatedAt are set by the server
    };
  }

  /**
   * Create a complete customer with all dependencies
   */
  createCustomerWithDependencies() {
    // Create type
    const typeResponse = this.createCustomerType();
    if (typeResponse.status !== 201) {
      console.error('Failed to create customer type');
      return null;
    }
    const type = JSON.parse(typeResponse.body);
    if (!type['@id']) {
      console.error('Response missing @id field for customer type');
      return null;
    }

    // Create status
    const statusResponse = this.createCustomerStatus();
    if (statusResponse.status !== 201) {
      console.error('Failed to create customer status');
      http.del(`${this.getBaseHttpUrl()}${type['@id']}`);
      return null;
    }
    const status = JSON.parse(statusResponse.body);
    if (!status['@id']) {
      console.error('Response missing @id field for customer status');
      http.del(`${this.getBaseHttpUrl()}${type['@id']}`);
      return null;
    }

    // Create customer
    const customerData = this.generateCustomerData(type['@id'], status['@id']);
    const customerResponse = http.post(
      `${this.getBaseHttpUrl()}/api/customers`,
      JSON.stringify(customerData),
      this.getJsonHeader()
    );

    if (customerResponse.status !== 201) {
      console.error('Failed to create customer');
      http.del(`${this.getBaseHttpUrl()}${type['@id']}`);
      http.del(`${this.getBaseHttpUrl()}${status['@id']}`);
      return null;
    }

    const customer = JSON.parse(customerResponse.body);

    return {
      customer: customer,
      type: type,
      status: status,
    };
  }

  /**
   * Clean up customer and dependencies
   */
  cleanupCustomerWithDependencies(data) {
    if (data.customer && data.customer['@id']) {
      const custResponse = http.del(`${this.getBaseHttpUrl()}${data.customer['@id']}`);
      if (
        custResponse.status !== 204 &&
        custResponse.status !== 200 &&
        custResponse.status !== 404
      ) {
        console.error(
          `Failed to delete customer: ${custResponse.status} - ${data.customer['@id']}`
        );
      }
    }
    if (data.type && data.type['@id']) {
      const typeResponse = http.del(`${this.getBaseHttpUrl()}${data.type['@id']}`);
      if (
        typeResponse.status !== 204 &&
        typeResponse.status !== 200 &&
        typeResponse.status !== 404
      ) {
        console.error(`Failed to delete type: ${typeResponse.status} - ${data.type['@id']}`);
      }
    }
    if (data.status && data.status['@id']) {
      const statusResponse = http.del(`${this.getBaseHttpUrl()}${data.status['@id']}`);
      if (
        statusResponse.status !== 204 &&
        statusResponse.status !== 200 &&
        statusResponse.status !== 404
      ) {
        console.error(`Failed to delete status: ${statusResponse.status} - ${data.status['@id']}`);
      }
    }
  }
}
```

**Usage**:

```javascript
import CustomerUtils from '../utils/customerUtils.js';
import ScenarioUtils from '../utils/scenarioUtils.js';

const customerUtils = new CustomerUtils();
const scenarioUtils = new ScenarioUtils(customerUtils, 'createCustomer');

export const options = scenarioUtils.getOptions();

export function setup() {
  return customerUtils.createCustomerWithDependencies();
}

export default function (data) {
  const customerData = customerUtils.generateCustomerData(data.type['@id'], data.status['@id']);
  // ... rest of test
}

export function teardown(data) {
  customerUtils.cleanupCustomerWithDependencies(data);
}
```

### Option 2: Composition Pattern

Use Utils as a component rather than extending:

```javascript
// tests/Load/utils/domainHelpers.js
import http from 'k6/http';

export class CustomerHelper {
  constructor(utils) {
    this.utils = utils;
  }

  createType(value) {
    return http.post(
      `${this.utils.getBaseHttpUrl()}/api/customer_types`,
      JSON.stringify({ value: value }),
      this.utils.getJsonHeader()
    );
  }

  createStatus(value) {
    return http.post(
      `${this.utils.getBaseHttpUrl()}/api/customer_statuses`,
      JSON.stringify({ value: value }),
      this.utils.getJsonHeader()
    );
  }
}

export class ProductHelper {
  constructor(utils) {
    this.utils = utils;
  }

  createCategory(name) {
    return http.post(
      `${this.utils.getBaseHttpUrl()}/api/product_categories`,
      JSON.stringify({ name: name }),
      this.utils.getJsonHeader()
    );
  }
}
```

**Usage**:

```javascript
import Utils from '../utils/utils.js';
import { CustomerHelper, ProductHelper } from '../utils/domainHelpers.js';

const utils = new Utils();
const customerHelper = new CustomerHelper(utils);
const productHelper = new ProductHelper(utils);

export function setup() {
  const typeResponse = customerHelper.createType(`Type_${Date.now()}`);
  const categoryResponse = productHelper.createCategory(`Cat_${Date.now()}`);

  return {
    type: JSON.parse(typeResponse.body),
    category: JSON.parse(categoryResponse.body),
  };
}
```

## Common Extension Patterns

### 1. Resource Factories

Create helpers for generating test data:

```javascript
export class ResourceFactory {
  constructor(utils) {
    this.utils = utils;
  }

  createUser(overrides = {}) {
    const defaults = {
      email: `user_${Date.now()}@example.com`,
      name: `User_${randomString(8)}`,
      active: true,
    };

    const userData = { ...defaults, ...overrides };

    return http.post(
      `${this.utils.getBaseHttpUrl()}/api/users`,
      JSON.stringify(userData),
      this.utils.getJsonHeader()
    );
  }

  createUsers(count, overrides = {}) {
    const users = [];
    for (let i = 0; i < count; i++) {
      const response = this.createUser(overrides);
      if (response.status === 201) {
        users.push(JSON.parse(response.body));
      }
    }
    return users;
  }
}
```

### 2. Authentication Helpers

Handle OAuth or token-based authentication:

```javascript
export class AuthHelper {
  constructor(utils) {
    this.utils = utils;
    this.tokenCache = new Map();
  }

  getToken(clientId, clientSecret) {
    const cacheKey = `${clientId}:${clientSecret}`;

    if (this.tokenCache.has(cacheKey)) {
      return this.tokenCache.get(cacheKey);
    }

    const response = http.post(
      `${this.utils.getBaseHttpUrl()}/oauth/token`,
      JSON.stringify({
        grant_type: 'client_credentials',
        client_id: clientId,
        client_secret: clientSecret,
      }),
      this.utils.getJsonHeader()
    );

    if (response.status === 200) {
      const body = JSON.parse(response.body);
      this.tokenCache.set(cacheKey, body.access_token);
      return body.access_token;
    }

    return null;
  }

  getAuthHeader(token) {
    return {
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
    };
  }
}
```

### 3. Validation Helpers

Create reusable validation logic:

```javascript
export class ValidationHelper {
  static validateCustomer(customer) {
    const checks = {
      'has id': customer['@id'] !== undefined,
      'has email': customer.email !== undefined,
      'has initials': customer.initials !== undefined,
      'email format valid': /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(customer.email),
      'has type': customer.type !== undefined,
      'has status': customer.status !== undefined,
    };

    const failed = Object.entries(checks)
      .filter(([_, passed]) => !passed)
      .map(([check]) => check);

    if (failed.length > 0) {
      console.error('Validation failed:', failed.join(', '));
      return false;
    }

    return true;
  }

  static validateCollection(collection, expectedMinSize = 0) {
    return (
      collection['hydra:member'] !== undefined &&
      Array.isArray(collection['hydra:member']) &&
      collection['hydra:totalItems'] >= expectedMinSize
    );
  }
}
```

### 4. GraphQL Query Builders

Build complex GraphQL queries dynamically:

```javascript
export class GraphQLQueryBuilder {
  constructor(utils) {
    this.utils = utils;
  }

  buildCustomerQuery(fields = ['id', 'initials', 'email'], filters = {}) {
    const fieldList = fields.join('\n          ');
    const filterParams = Object.keys(filters)
      .map(key => `$${key}: String`)
      .join(', ');
    const filterArgs = Object.keys(filters)
      .map(key => `${key}: $${key}`)
      .join(', ');

    return {
      query: `query GetCustomers(${filterParams}) {
        customers(${filterArgs}) {
          collection {
            ${fieldList}
          }
          paginationInfo {
            totalCount
          }
        }
      }`,
      variables: filters,
    };
  }

  execute(queryObject) {
    return this.utils.executeGraphQL(queryObject);
  }
}
```

**Usage**:

```javascript
const queryBuilder = new GraphQLQueryBuilder(utils);
const query = queryBuilder.buildCustomerQuery(['id', 'email'], {
  status: '/api/customer_statuses/01234',
});
const response = queryBuilder.execute(query);
```

## Testing Extended Utils

Create unit tests for your utilities:

```javascript
// tests/Load/utils/__tests__/customerUtils.test.js
import { describe, it, expect } from '@jest/globals';
import CustomerUtils from '../customerUtils.js';

describe('CustomerUtils', () => {
  it('generates valid customer data', () => {
    const utils = new CustomerUtils();
    const data = utils.generateCustomerData('/api/types/1', '/api/statuses/1');

    expect(data.initials).toBeDefined();
    expect(data.email).toMatch(/^[^\s@]+@[^\s@]+\.[^\s@]+$/);
    expect(data.phone).toMatch(/^\+1-555-\d{4}$/);
    expect(data.type).toBe('/api/types/1');
    expect(data.status).toBe('/api/statuses/1');
  });
});
```

## Best Practices

### 1. Keep Extensions Focused

Each helper class should have a single responsibility:

```javascript
// ✅ GOOD: Focused helpers
class CustomerFactory {}
class CustomerValidator {}
class CustomerCleaner {}

// ❌ BAD: God class
class CustomerEverything {}
```

### 2. Make Helpers Stateless

Avoid storing state in helper classes:

```javascript
// ✅ GOOD: Stateless
createCustomer(data) {
  return http.post(url, JSON.stringify(data), headers);
}

// ❌ BAD: Stateful
this.lastCustomer = createCustomer(data);
```

### 3. Use Dependency Injection

Pass dependencies rather than importing directly:

```javascript
// ✅ GOOD: Dependency injection
class CustomerHelper {
  constructor(utils, http) {
    this.utils = utils;
    this.http = http;
  }
}

// ❌ BAD: Hardcoded dependencies
import http from 'k6/http';
class CustomerHelper {
  makeRequest() {
    http.post(...);
  }
}
```

### 4. Document Public Methods

Add JSDoc comments for all public methods:

```javascript
/**
 * Creates a customer type with the given value
 * @param {string} value - The type value
 * @returns {Object} HTTP response object
 * @example
 * const response = utils.createCustomerType('Premium');
 */
createCustomerType(value) {
  // ...
}
```

### 5. Handle Errors Gracefully

Always check response status and handle failures:

```javascript
createResource(data) {
  const response = http.post(url, JSON.stringify(data), headers);

  if (response.status !== 201) {
    console.error(`Failed to create resource: ${response.status}`);
    console.error(`Response: ${response.body}`);
    return null;
  }

  return JSON.parse(response.body);
}
```
