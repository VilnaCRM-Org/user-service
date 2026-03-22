/**
 * REST API Load Test Example: Customer CRUD Operations
 *
 * This is a complete working example demonstrating best practices for
 * REST API load testing with K6.
 *
 * Features:
 * - Setup function creates required dependencies
 * - Main function performs the target operation (create customer)
 * - Teardown function cleans up test dependencies (type and status)
 * - Note: Customer cleanup not tracked due to concurrency limitations
 *   Manual cleanup may be needed after test runs
 * - Proper IRI handling
 * - Realistic data generation
 * - Comprehensive response validation
 *
 * Usage:
 *   make execute-load-tests-script scenario=restCustomerExample
 *
 * Before using:
 * 1. Copy this file to tests/Load/scripts/restCustomerExample.js
 * 2. Add configuration to tests/Load/config.json.dist
 */

import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import { randomString } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';

// Scenario name must match the filename (without .js extension)
const scenarioName = 'restCustomerExample';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

// Load scenario configuration from config.json.dist
export const options = scenarioUtils.getOptions();

/**
 * Setup function runs once before all iterations
 * Creates dependencies needed for customer creation
 *
 * @returns {Object} Data passed to default function and teardown
 */
export function setup() {
  console.log('=== Setup: Creating dependencies ===');

  // Create customer type
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

  // Return data for use in default function
  return {
    type: type,
    status: status,
  };
}

/**
 * Default function runs once per iteration per VU
 * This is the main test logic
 *
 * @param {Object} data - Data returned from setup()
 */
export default function restCustomerExample(data) {
  // Skip if dependencies not created
  if (!data.type || !data.status) {
    console.error('Dependencies not available, skipping iteration');
    return;
  }

  // Generate realistic customer data
  const customerData = generateCustomerData(data);

  // Create customer via REST API
  const response = http.post(
    `${utils.getBaseUrl()}/customers`,
    JSON.stringify(customerData),
    utils.getJsonHeader()
  );

  // Validate response
  utils.checkResponse(response, 'customer created', res => {
    if (res.status === 201) {
      try {
        const customer = JSON.parse(res.body);

        // Validate customer structure
        if (!customer['@id']) {
          console.error('Customer missing @id field');
          return false;
        }

        if (!customer.email || customer.email !== customerData.email) {
          console.error('Customer email mismatch');
          return false;
        }

        return true;
      } catch (e) {
        console.error('Failed to parse response:', e);
        return false;
      }
    }

    console.error(`Unexpected status: ${res.status}`);
    console.error(`Response: ${res.body}`);
    return false;
  });
}

/**
 * Teardown function runs once after all iterations
 * Cleans up all test data
 *
 * @param {Object} data - Data returned from setup()
 */
export function teardown(data) {
  console.log('=== Teardown: Cleaning up test data ===');

  if (!data) {
    console.log('No data to clean up');
    return;
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
 * Generate realistic customer data for testing
 *
 * @param {Object} data - Setup data containing type and status IRIs
 * @returns {Object} Customer data object
 */
function generateCustomerData(data) {
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

    // Use IRIs from setup data
    type: data.type['@id'],
    status: data.status['@id'],

    // Random confirmed status
    confirmed: Math.random() > 0.5,

    // ISO timestamps
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  };
}

/**
 * Configuration for this scenario
 *
 * Add this to tests/Load/config.json.dist:
 *
 * {
 *   "scenarios": {
 *     "restCustomerExample": {
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
