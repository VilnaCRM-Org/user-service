# Load Testing Troubleshooting

## Common Issues and Solutions

### Test Script Issues

#### Issue: Script Not Found

**Symptoms**:

```text
Error: scenario 'createCustomer' not found in config
```

**Diagnosis**:

```bash
./tests/Load/get-load-test-scenarios.sh
```

**Solutions**:

1. Verify script exists in `tests/Load/scripts/`:

   ```bash
   ls -la tests/Load/scripts/createCustomer.js
   ```

2. Check configuration in `tests/Load/config.json.dist`:

   ```json
   {
     "scenarios": {
       "createCustomer": {
         "smoke": { "vus": 2, "duration": "10s" }
       }
     }
   }
   ```

3. Ensure script follows naming convention:
   - REST: `operationResource.js` (e.g., `createCustomer.js`)
   - GraphQL: `graphQLOperationResource.js` (e.g., `graphQLCreateCustomer.js`)

#### Issue: Import Path Errors

**Symptoms**:

```text
Error: Cannot find module '../utils/utils.js'
```

**Solution**:

Verify relative paths from script location:

```javascript
// For scripts in tests/Load/scripts/
import Utils from '../utils/utils.js'; // ✅ Correct
import ScenarioUtils from '../utils/scenarioUtils.js'; // ✅ Correct

// ❌ Wrong
import Utils from './utils/utils.js';
import Utils from 'utils/utils.js';
```

#### Issue: Invalid JavaScript Syntax

**Symptoms**:

```text
SyntaxError: Unexpected token
```

**Common Causes**:

1. Missing semicolons or commas
2. Unmatched brackets or parentheses
3. Invalid string escaping

**Solution**:

Run syntax validation:

```bash
node --check tests/Load/scripts/yourScript.js
```

### Setup/Teardown Issues

#### Issue: Setup Returns Null/Undefined

**Symptoms**:

```text
TypeError: Cannot read property '@id' of null
```

**Diagnosis**:

Add logging to setup function:

```javascript
export function setup() {
  const response = utils.createDependency(data);

  console.log('Setup response status:', response.status);
  console.log('Setup response body:', response.body);

  if (response.status === 201) {
    return { dependency: JSON.parse(response.body) };
  }

  console.error('Setup failed - no dependency created');
  return { dependency: null };
}
```

**Solutions**:

1. Check API endpoint availability
2. Verify request payload format
3. Check authentication/authorization
4. Ensure database is accessible

#### Issue: Teardown Doesn't Clean Up

**Symptoms**:

- Test data remains in database after tests
- Database fills up over time

**Solution**:

Ensure teardown handles all cases:

```javascript
export function teardown(data) {
  // Check data exists before cleanup
  if (!data) {
    console.log('No data to clean up');
    return;
  }

  // Clean up dependencies
  if (data.dependency) {
    const response = http.del(`${utils.getBaseHttpUrl()}${data.dependency['@id']}`);
    console.log(`Cleanup dependency: ${response.status}`);
  }

  // Clean up created resources
  if (data.createdResources && Array.isArray(data.createdResources)) {
    data.createdResources.forEach(iri => {
      const response = http.del(`${utils.getBaseHttpUrl()}${iri}`);
      console.log(`Cleanup resource ${iri}: ${response.status}`);
    });
  }
}
```

### Response Validation Issues

#### Issue: All Requests Failing

**Symptoms**:

```text
✗ is status 201
  ↳  0% — ✓ 0 / ✗ 100
```

**Diagnosis**:

Add detailed response logging:

```javascript
const response = http.post(url, payload, headers);

console.log('Response status:', response.status);
console.log('Response body:', response.body);
console.log('Request URL:', url);
console.log('Request payload:', payload);
```

**Common Causes**:

1. **Wrong Base URL**:

   ```javascript
   // Check BASE_URL environment variable
   console.log('Base URL:', utils.getBaseHttpUrl());
   ```

2. **Invalid Payload**:

   ```javascript
   // Validate JSON structure
   try {
     JSON.parse(payload);
   } catch (e) {
     console.error('Invalid JSON:', e);
   }
   ```

3. **Missing Headers**:

   ```javascript
   // Ensure Content-Type header is set
   console.log('Headers:', JSON.stringify(headers));
   ```

4. **Authentication Issues**:

   ```bash
   # Check if endpoint requires authentication (REST API)
   curl -X POST https://localhost/api/customers \
     -H "Content-Type: application/ld+json" \
     -d '{"email":"test@example.com"}'

   # For GraphQL endpoint
   curl -X POST https://localhost/api/graphql \
     -H "Content-Type: application/json" \
     -d '{"query":"{ customers { collection { id } } }"}'
   ```

#### Issue: Intermittent Failures

**Symptoms**:

```text
✗ is status 201
  ↳  95% — ✓ 95 / ✗ 5
```

**Common Causes**:

1. **Database Connection Pool Exhausted**:
   - Solution: Increase connection pool size or reduce VUs

2. **Rate Limiting**:

   ```javascript
   // Add delay between requests
   import { sleep } from 'k6';

   export default function () {
     createCustomer();
     sleep(0.1); // 100ms delay
   }
   ```

3. **Resource Contention**:
   - Solution: Use unique test data per iteration

   ```javascript
   // ✅ GOOD: Unique data
   const email = `test_${Date.now()}_${__ITER}@example.com`;

   // ❌ BAD: Same data
   const email = 'test@example.com';
   ```

4. **Timeout Issues**:

   ```javascript
   export const options = {
     thresholds: {
       http_req_duration: ['p(95)<5000'], // Increase threshold
     },
   };
   ```

### GraphQL-Specific Issues

#### Issue: GraphQL Returns Errors

**Symptoms**:

```javascript
{
  "errors": [
    {
      "message": "Field 'customer' doesn't exist on type 'Query'",
      "locations": [{"line": 2, "column": 3}]
    }
  ]
}
```

**Diagnosis**:

Always check for GraphQL errors:

```javascript
const response = utils.executeGraphQL(query);

if (response.status !== 200) {
  console.error('HTTP error:', response.status);
  console.error('Response:', response.body);
  return;
}

const body = JSON.parse(response.body);

if (body.errors) {
  console.error('GraphQL errors:', JSON.stringify(body.errors, null, 2));
  body.errors.forEach(error => {
    console.error(`- ${error.message}`);
    if (error.locations) {
      console.error(`  at line ${error.locations[0].line}, column ${error.locations[0].column}`);
    }
  });
  return;
}

if (!body.data) {
  console.error('No data in response');
  return;
}
```

**Common Causes**:

1. **Field Name Typo**:

   ```graphql
   # ❌ Wrong
   query {
     costumer {
       id
     }
   }

   # ✅ Correct
   query {
     customer {
       id
     }
   }
   ```

2. **Missing Required Variables**:

   ```javascript
   // ❌ Missing variable
   const query = {
     query: `mutation CreateCustomer($input: CreateCustomerInput!) { ... }`,
     variables: {}, // Missing input variable
   };

   // ✅ Correct
   const query = {
     query: `mutation CreateCustomer($input: CreateCustomerInput!) { ... }`,
     variables: {
       input: {
         /* ... */
       },
     },
   };
   ```

3. **Wrong Variable Type**:

   ```javascript
   // ❌ String instead of ID
   variables: {
     id: 'customer-123';
   }

   // ✅ Use IRI format
   variables: {
     id: '/api/customers/01234';
   }
   ```

#### Issue: GraphQL Response Validation Fails

**Symptoms**:

```text
✗ GraphQL operation successful
  ↳  100% — ✓ 0 / ✗ 100
```

**Solution**:

Check response structure:

```javascript
utils.checkResponse(response, 'GraphQL operation successful', res => {
  if (res.status !== 200) {
    console.error('HTTP status:', res.status);
    return false;
  }

  try {
    const body = JSON.parse(res.body);

    if (body.errors) {
      console.error('GraphQL errors:', JSON.stringify(body.errors));
      return false;
    }

    if (!body.data) {
      console.error('Missing data field');
      return false;
    }

    // Check for expected data structure
    console.log('Response data:', JSON.stringify(body.data));

    return true;
  } catch (e) {
    console.error('Parse error:', e);
    return false;
  }
});
```

### Performance Issues

#### Issue: Tests Run Too Slowly

**Symptoms**:

- Tests take much longer than configured duration
- Low request throughput

**Diagnosis**:

Check K6 metrics:

```text
http_req_duration.............: avg=5000ms  p(95)=8000ms
http_reqs.....................: 10/s
iterations....................: 100
```

**Solutions**:

1. **Reduce Validation Overhead**:

   ```javascript
   // ❌ BAD: Parse body on every check
   utils.checkResponse(response, 'check', r => JSON.parse(r.body).id);

   // ✅ GOOD: Parse once
   const body = response.status === 200 ? JSON.parse(response.body) : null;
   utils.checkResponse(response, 'check', r => body?.id);
   ```

2. **Use Connection Pooling**:

   ```javascript
   export const options = {
     noConnectionReuse: false, // Enable connection reuse
   };
   ```

3. **Reduce Setup Overhead**:

   ```javascript
   // ✅ Create dependencies once in setup
   export function setup() {
     return createDependencies();
   }

   // ❌ Don't create dependencies in default function
   export default function () {
     const dep = createDependency(); // Created every iteration!
   }
   ```

4. **Use REST for Setup/Teardown**:

   ```javascript
   // ✅ GOOD: REST is faster
   export function setup() {
     const response = http.post(url, data, headers);
     return JSON.parse(response.body);
   }

   // ❌ BAD: GraphQL adds overhead
   export function setup() {
     const response = utils.executeGraphQL(mutation);
     return JSON.parse(response.body).data;
   }
   ```

#### Issue: High Memory Usage

**Symptoms**:

- K6 crashes with "out of memory" errors
- Host system becomes unresponsive

**Solutions**:

1. **Limit Data Storage**:

   ```javascript
   // ❌ BAD: Store all responses
   const responses = [];
   export default function() {
     responses.push(http.get(url)); // Memory leak!
   }

   // ✅ GOOD: Don't store responses
   export default function() {
     const response = http.get(url);
     // Process and discard
   }
   ```

2. **Reduce VU Count**:

   ```bash
   # Start with lower VUs
   make smoke-load-tests  # 2-5 VUs
   ```

3. **Use Smaller Payloads**:

   ```javascript
   // Only request fields you need
   query {
     customers {
       id
       email
     }
   }
   ```

### Database Issues

#### Issue: Connection Refused

**Symptoms**:

```text
Error: connect ECONNREFUSED 127.0.0.1:27017
```

**Solutions**:

1. Verify database is running:

   ```bash
   docker compose ps mongodb
   ```

2. Check connection string:

   ```bash
   echo $DB_URL
   # Should be: mongodb://localhost:27017/test
   ```

3. Test database connectivity:

   ```bash
   docker compose exec php vendor/bin/doctrine mongodb:schema:validate
   ```

#### Issue: Database Fills Up

**Symptoms**:

- Test database grows over time
- Queries slow down

**Solutions**:

1. Verify teardown runs:

   ```javascript
   export function teardown(data) {
     console.log('Teardown running...');
     // Clean up code
     console.log('Teardown complete');
   }
   ```

2. Manual cleanup if needed:

   ```bash
   make setup-test-db  # Drops and recreates test database
   ```

3. Add cleanup to test script:

   ```javascript
   export function teardown(data) {
     // Clean up all created resources
     if (data.createdResources) {
       data.createdResources.forEach(iri => {
         http.del(`${utils.getBaseHttpUrl()}${iri}`);
       });
     }
   }
   ```

### Configuration Issues

#### Issue: Scenario Not in Config

**Symptoms**:

```text
Error: Scenario 'myScenario' not found in config
```

**Solution**:

Add scenario to `tests/Load/config.json.dist`:

```json
{
  "scenarios": {
    "myScenario": {
      "smoke": {
        "vus": 2,
        "duration": "10s"
      },
      "average": {
        "vus": 10,
        "duration": "2m"
      },
      "stress": {
        "vus": 50,
        "duration": "5m"
      },
      "spike": {
        "vus": 100,
        "duration": "1m"
      }
    }
  }
}
```

#### Issue: Invalid Configuration Format

**Symptoms**:

```text
SyntaxError: Unexpected token in JSON
```

**Solution**:

Validate JSON:

```bash
cat tests/Load/config.json.dist | jq .
```

Ensure proper format:

```json
{
  "scenarios": {
    "scenarioName": {
      "smoke": { "vus": 2, "duration": "10s" }
    }
  }
}
```

## Debugging Tips

### Enable Verbose Logging

```javascript
import { options } from './scenarioUtils.js';

// Override options for debugging
export const options = {
  ...options,
  thresholds: {}, // Disable thresholds temporarily
  vus: 1, // Single VU for cleaner logs
  duration: '10s', // Short duration
};
```

### Use K6 Debug Output

```bash
k6 run --verbose tests/Load/scripts/yourScript.js
```

### Test Individual Functions

```javascript
// Add temporary test code
export default function (data) {
  console.log('Testing data generation...');
  const testData = generateCustomerData(data);
  console.log('Generated:', JSON.stringify(testData, null, 2));

  // Comment out actual HTTP calls for testing
  // const response = http.post(...);
}
```

### Use curl to Replicate Requests

```bash
# REST API request (uses application/ld+json)
curl -X POST https://localhost/api/customers \
  -H "Content-Type: application/ld+json" \
  -d '{"email":"test@example.com","type":"/api/customer_types/01234"}'

# GraphQL request (uses application/json)
curl -X POST https://localhost/api/graphql \
  -H "Content-Type: application/json" \
  -d '{"query":"mutation { createCustomer(input: {email:\"test@example.com\"}) { customer { id } } }"}'
```

## Getting Help

If issues persist:

1. Check K6 documentation: [https://k6.io/docs/](https://k6.io/docs/)
2. Review load-testing skill files in `.claude/skills/load-testing/`
3. Examine working examples in `tests/Load/scripts/`
4. Check project-specific README in `tests/Load/`
5. Review CI logs for similar failures
