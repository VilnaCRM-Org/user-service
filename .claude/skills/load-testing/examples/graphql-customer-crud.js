/**
 * GraphQL Load Test Example: Customer CRUD Operations
 *
 * This is a complete working example demonstrating best practices for
 * GraphQL load testing with K6.
 *
 * Features:
 * - Setup uses REST API for faster dependency creation
 * - Main function uses GraphQL mutations
 * - Teardown uses REST API for faster cleanup
 * - Proper GraphQL error handling
 * - Full IRI format in GraphQL variables
 * - Comprehensive response validation
 *
 * Usage:
 *   make execute-load-tests-script scenario=graphQLCustomerExample
 *
 * Before using:
 * 1. Copy this file to tests/Load/scripts/graphQLCustomerExample.js
 * 2. Add configuration to tests/Load/config.json.dist
 */

import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import { randomString } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';

// Scenario name must match the filename (without .js extension)
const scenarioName = 'graphQLCustomerExample';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

// Load scenario configuration from config.json.dist
export const options = scenarioUtils.getOptions();

/**
 * Setup function runs once before all iterations
 * Uses REST API for faster dependency creation
 *
 * @returns {Object} Data passed to default function and teardown
 */
export function setup() {
  console.log('=== Setup: Creating dependencies via REST ===');

  // Use REST API (faster than GraphQL for bulk operations)
  const typeData = {
    value: `TestType_${Date.now()}`,
  };

  const typeResponse = http.post(
    `${utils.getBaseUrl()}/customer_types`,
    JSON.stringify(typeData),
    utils.getJsonHeader()
  );

  if (typeResponse.status !== 201) {
    console.error(`Failed to create customer type: ${typeResponse.status}`);
    console.error(`Response: ${typeResponse.body}`);
    return { type: null, status: null };
  }

  const type = JSON.parse(typeResponse.body);
  console.log(`Created type: ${type['@id']}`);

  // Create customer status
  const statusData = {
    value: `TestStatus_${Date.now()}`,
  };

  const statusResponse = http.post(
    `${utils.getBaseUrl()}/customer_statuses`,
    JSON.stringify(statusData),
    utils.getJsonHeader()
  );

  if (statusResponse.status !== 201) {
    console.error(`Failed to create customer status: ${statusResponse.status}`);
    console.error(`Response: ${statusResponse.body}`);

    // Clean up type before returning
    http.del(`${utils.getBaseDomain()}${type['@id']}`);
    return { type: null, status: null };
  }

  const status = JSON.parse(statusResponse.body);
  console.log(`Created status: ${status['@id']}`);

  console.log('=== Setup complete ===');

  return {
    type: type,
    status: status,
    createdCustomers: [], // Track created customers for cleanup
  };
}

/**
 * Default function runs once per iteration per VU
 * Uses GraphQL mutation for customer creation
 *
 * @param {Object} data - Data returned from setup()
 */
export default function graphQLCustomerExample(data) {
  // Skip if dependencies not created
  if (!data.type || !data.status) {
    console.error('Dependencies not available, skipping iteration');
    return;
  }

  // Build GraphQL mutation
  const mutation = buildCreateCustomerMutation(data);

  // Execute GraphQL mutation
  const response = utils.executeGraphQL(mutation);

  // Validate response
  utils.checkResponse(response, 'customer created via GraphQL', res => {
    // Check HTTP status
    if (res.status !== 200) {
      console.error(`HTTP error: ${res.status}`);
      console.error(`Response: ${res.body}`);
      return false;
    }

    try {
      const body = JSON.parse(res.body);

      // Check for GraphQL errors
      if (body.errors && body.errors.length > 0) {
        console.error('GraphQL errors:');
        body.errors.forEach(error => {
          console.error(`- ${error.message}`);
          if (error.extensions) {
            console.error(`  Extensions: ${JSON.stringify(error.extensions)}`);
          }
        });
        return false;
      }

      // Check for expected data structure
      if (!body.data) {
        console.error('Response missing data field');
        return false;
      }

      if (!body.data.createCustomer) {
        console.error('Response missing createCustomer field');
        return false;
      }

      const customer = body.data.createCustomer.customer;

      if (!customer) {
        console.error('Response missing customer field');
        return false;
      }

      // Validate customer structure
      if (!customer.id) {
        console.error('Customer missing id field');
        return false;
      }

      // Store IRI for cleanup
      data.createdCustomers.push(customer.id);

      return true;
    } catch (e) {
      console.error('Failed to parse response:', e);
      return false;
    }
  });
}

/**
 * Teardown function runs once after all iterations
 * Uses REST API for faster cleanup
 *
 * @param {Object} data - Data returned from setup()
 */
export function teardown(data) {
  console.log('=== Teardown: Cleaning up test data via REST ===');

  if (!data) {
    console.log('No data to clean up');
    return;
  }

  // Clean up created customers using REST (faster than GraphQL)
  if (data.createdCustomers && data.createdCustomers.length > 0) {
    console.log(`Cleaning up ${data.createdCustomers.length} customers...`);

    data.createdCustomers.forEach(customerIri => {
      const response = http.del(`${utils.getBaseDomain()}${customerIri}`);

      if (response.status === 204) {
        console.log(`Deleted customer: ${customerIri}`);
      } else {
        console.warn(`Failed to delete customer ${customerIri}: ${response.status}`);
      }
    });
  }

  // Clean up customer status
  if (data.status) {
    const response = http.del(`${utils.getBaseDomain()}${data.status['@id']}`);

    if (response.status === 204) {
      console.log(`Deleted status: ${data.status['@id']}`);
    } else {
      console.warn(`Failed to delete status: ${response.status}`);
    }
  }

  // Clean up customer type
  if (data.type) {
    const response = http.del(`${utils.getBaseDomain()}${data.type['@id']}`);

    if (response.status === 204) {
      console.log(`Deleted type: ${data.type['@id']}`);
    } else {
      console.warn(`Failed to delete type: ${response.status}`);
    }
  }

  console.log('=== Teardown complete ===');
}

/**
 * Build GraphQL mutation for customer creation
 *
 * @param {Object} data - Setup data containing type and status IRIs
 * @returns {Object} GraphQL query object with variables
 */
function buildCreateCustomerMutation(data) {
  const customerInput = generateCustomerInput(data);

  return {
    query: `mutation CreateCustomer($input: CreateCustomerInput!) {
      createCustomer(input: $input) {
        customer {
          id
          initials
          email
          phone
          leadSource
          type
          status
          confirmed
          createdAt
          updatedAt
        }
      }
    }`,
    variables: {
      input: customerInput,
    },
  };
}

/**
 * Generate realistic customer input data for GraphQL mutation
 *
 * @param {Object} data - Setup data containing type and status IRIs
 * @returns {Object} Customer input object
 */
function generateCustomerInput(data) {
  const domains = ['example.com', 'test.org', 'demo.net', 'sample.io'];
  const leadSources = ['Website', 'Referral', 'Social Media', 'Email Campaign', 'Direct'];
  const initials = `Customer_${randomString(8)}`;

  return {
    // Use timestamp + random string for unique email
    initials: initials,
    email: `${initials.toLowerCase()}_${Date.now()}@${domains[Math.floor(Math.random() * domains.length)]}`,

    // Generate realistic phone number
    phone: `+1-555-${Math.floor(Math.random() * 9000) + 1000}`,

    // Random lead source
    leadSource: leadSources[Math.floor(Math.random() * leadSources.length)],

    // IMPORTANT: Use full IRI format for GraphQL
    type: data.type['@id'], // e.g., "/api/customer_types/01234"
    status: data.status['@id'], // e.g., "/api/customer_statuses/56789"

    // Random confirmed status
    confirmed: Math.random() > 0.5,

    // ISO timestamps
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  };
}

/**
 * Example: Query customer after creation
 *
 * You can add this to the default function to test queries:
 */
function exampleQueryCustomer(customerId) {
  const query = {
    query: `query GetCustomer($id: ID!) {
      customer(id: $id) {
        id
        initials
        email
        phone
        type
        status
        confirmed
      }
    }`,
    variables: {
      id: customerId, // Full IRI: "/api/customers/01234"
    },
  };

  const response = utils.executeGraphQL(query);

  utils.checkResponse(response, 'customer fetched via GraphQL', res => {
    if (res.status !== 200) {
      return false;
    }

    const body = JSON.parse(res.body);

    if (body.errors) {
      console.error('GraphQL errors:', JSON.stringify(body.errors));
      return false;
    }

    return body.data?.customer !== undefined;
  });
}

/**
 * Example: Update customer
 *
 * You can add this to the default function to test updates:
 */
function exampleUpdateCustomer(customerId) {
  const mutation = {
    query: `mutation UpdateCustomer($input: UpdateCustomerInput!) {
      updateCustomer(input: $input) {
        customer {
          id
          initials
          updatedAt
        }
      }
    }`,
    variables: {
      input: {
        id: customerId, // Full IRI
        initials: `Updated_${randomString(8)}`,
      },
    },
  };

  const response = utils.executeGraphQL(mutation);

  utils.checkResponse(response, 'customer updated via GraphQL', res => {
    if (res.status !== 200) {
      return false;
    }

    const body = JSON.parse(res.body);

    if (body.errors) {
      console.error('GraphQL errors:', JSON.stringify(body.errors));
      return false;
    }

    return body.data?.updateCustomer?.customer !== undefined;
  });
}

/**
 * Example: Query collection with filters
 *
 * You can add this to test collection queries:
 */
function exampleQueryCustomers(statusIri) {
  const query = {
    query: `query GetCustomers($status: String, $page: Int, $itemsPerPage: Int) {
      customers(status: $status, page: $page, itemsPerPage: $itemsPerPage) {
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
      status: statusIri, // Filter by status IRI
      page: 1,
      itemsPerPage: 30,
    },
  };

  const response = utils.executeGraphQL(query);

  utils.checkResponse(response, 'customers collection fetched', res => {
    if (res.status !== 200) {
      return false;
    }

    const body = JSON.parse(res.body);

    if (body.errors) {
      console.error('GraphQL errors:', JSON.stringify(body.errors));
      return false;
    }

    return body.data?.customers?.collection !== undefined;
  });
}

/**
 * Configuration for this scenario
 *
 * Add this to tests/Load/config.json.dist:
 *
 * {
 *   "scenarios": {
 *     "graphQLCustomerExample": {
 *       "smoke": {
 *         "vus": 2,
 *         "duration": "10s"
 *       },
 *       "average": {
 *         "vus": 10,
 *         "duration": "2m"
 *       },
 *       "stress": {
 *         "vus": 50,
 *         "duration": "5m"
 *       },
 *       "spike": {
 *         "vus": 100,
 *         "duration": "1m"
 *       }
 *     }
 *   }
 * }
 */
