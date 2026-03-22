# K6 Load Test Configuration

> **K6 Version Compatibility**: Requires K6 >= 0.45.0. These examples are tested with K6 0.45.0+. Some features may not be available in older versions.

## Configuration File Structure

Load test scenarios are configured in `tests/Load/config.json.dist`:

```json
{
  "scenarios": {
    "scenarioName": {
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

## Load Test Levels

### Smoke Tests

**Purpose**: Basic functionality verification

```json
"smoke": {
  "vus": 2,
  "duration": "10s"
}
```

**Characteristics**:

- Minimal load (2-5 VUs)
- Short duration (10 seconds)
- 100% success rate expected
- Quick validation that endpoints work

**When to Use**:

- Verifying new test scripts work correctly
- CI/CD pipeline validation
- Pre-deployment smoke checks

### Average Tests

**Purpose**: Normal traffic simulation

```json
"average": {
  "vus": 10,
  "duration": "2m"
}
```

**Characteristics**:

- Normal load (10-20 VUs)
- Medium duration (2-3 minutes)
- > 99% success rate expected
- Simulates typical production load

**When to Use**:

- Regular performance monitoring
- Baseline performance measurement
- Regression testing

### Stress Tests

**Purpose**: Find breaking points

```json
"stress": {
  "vus": 50,
  "duration": "5m"
}
```

**Characteristics**:

- High load (30-80 VUs)
- Extended duration (5-15 minutes)
- > 95% success rate expected
- Identifies performance bottlenecks

**When to Use**:

- Capacity planning
- Finding system limits
- Performance optimization validation

### Spike Tests

**Purpose**: Test resilience under sudden load

```json
"spike": {
  "vus": 100,
  "duration": "1m"
}
```

**Characteristics**:

- Extreme load (100-200 VUs)
- Short duration (1-3 minutes)
- > 90% success rate expected
- Tests system recovery

**When to Use**:

- Testing auto-scaling
- Validating circuit breakers
- Simulating traffic spikes

## ScenarioUtils Integration

The `ScenarioUtils` class automatically loads configuration:

```javascript
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';

const scenarioName = 'createCustomer';
const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

// Automatically loads config for current scenario
export const options = scenarioUtils.getOptions();
```

## K6 Options

### Basic Options

```javascript
export const options = {
  vus: 10, // Number of virtual users
  duration: '2m', // Test duration
  thresholds: {
    http_req_duration: ['p(95)<500'], // 95% of requests under 500ms
    http_req_failed: ['rate<0.01'], // Less than 1% failure rate
  },
};
```

### Advanced Options

```javascript
export const options = {
  stages: [
    { duration: '30s', target: 10 }, // Ramp up to 10 VUs
    { duration: '2m', target: 10 }, // Stay at 10 VUs
    { duration: '30s', target: 0 }, // Ramp down to 0 VUs
  ],
  thresholds: {
    http_req_duration: ['p(95)<500', 'p(99)<1000'],
    http_req_failed: ['rate<0.01'],
    http_reqs: ['rate>10'], // Minimum 10 requests per second
  },
  noConnectionReuse: false,
  userAgent: 'K6LoadTest/1.0',
  insecureSkipTLSVerify: true, // For local testing with self-signed certs
  tags: {
    test_type: 'performance',
    environment: 'test',
  },
};
```

## Thresholds

### Response Time Thresholds

```javascript
thresholds: {
  // 95th percentile under 500ms
  'http_req_duration': ['p(95)<500'],

  // 99th percentile under 1000ms
  'http_req_duration': ['p(99)<1000'],

  // Average under 200ms
  'http_req_duration': ['avg<200'],

  // Max under 2000ms
  'http_req_duration': ['max<2000']
}
```

### Error Rate Thresholds

```javascript
thresholds: {
  // Less than 1% failure rate
  'http_req_failed': ['rate<0.01'],

  // Less than 5% failure rate for specific endpoint
  'http_req_failed{endpoint:create}': ['rate<0.05']
}
```

### Throughput Thresholds

```javascript
thresholds: {
  // Minimum 10 requests per second
  'http_reqs': ['rate>10'],

  // Minimum 100 iterations completed
  'iterations': ['count>100']
}
```

## Environment Variables

### Base Configuration

Create `tests/Load/.env` from the template (if `.env.dist` exists, copy it: `cp tests/Load/.env.dist tests/Load/.env`), or create a new `.env` file with the following configuration:

```bash
# API Base URL
BASE_URL=https://localhost

# Authentication (if needed)
API_KEY=your_api_key_here

# Database (for setup/teardown)
DB_URL=mongodb://localhost:27017/test
```

### Accessing in Tests

```javascript
import { BASE_URL } from '../config.js';

const baseUrl = __ENV.BASE_URL || BASE_URL || 'https://localhost';
```

## Custom Metrics

### Recording Custom Metrics

```javascript
import { Counter, Trend, Rate } from 'k6/metrics';

const customersCreated = new Counter('customers_created');
const customerCreationTime = new Trend('customer_creation_time');
const customerCreationErrors = new Rate('customer_creation_errors');

export default function () {
  const startTime = Date.now();
  const response = createCustomer();

  if (response.status === 201) {
    customersCreated.add(1);
    customerCreationTime.add(Date.now() - startTime);
    customerCreationErrors.add(0);
  } else {
    customerCreationErrors.add(1);
  }
}
```

### Viewing Custom Metrics

Custom metrics appear in K6 output:

```bash
customers_created..................: 1523
customer_creation_time.............: avg=125ms p(95)=250ms
customer_creation_errors...........: 0.32%
```

## Tags and Groups

### Using Tags

```javascript
import { group, check } from 'k6';
import http from 'k6/http';

export default function () {
  group('Customer Creation', () => {
    const response = http.post(url, payload, { tags: { endpoint: 'create' } });

    check(
      response,
      {
        'status is 201': r => r.status === 201,
      },
      { endpoint: 'create' }
    );
  });

  group('Customer Retrieval', () => {
    const response = http.get(url, { tags: { endpoint: 'get' } });

    check(
      response,
      {
        'status is 200': r => r.status === 200,
      },
      { endpoint: 'get' }
    );
  });
}
```

### Thresholds by Tag

```javascript
export const options = {
  thresholds: {
    'http_req_duration{endpoint:create}': ['p(95)<500'],
    'http_req_duration{endpoint:get}': ['p(95)<200'],
    'http_req_failed{endpoint:create}': ['rate<0.01'],
  },
};
```

## Configuration Best Practices

### 1. Progressive Load Testing

Start with smoke, then increase:

```bash
# Step 1: Smoke test
make smoke-load-tests

# Step 2: Average load
make average-load-tests

# Step 3: Stress test
make stress-load-tests

# Step 4: Spike test
make spike-load-tests
```

### 2. Scenario-Specific Configuration

Different endpoints have different performance characteristics:

```json
{
  "scenarios": {
    "createCustomer": {
      "smoke": { "vus": 2, "duration": "10s" },
      "average": { "vus": 10, "duration": "2m" }
    },
    "getCustomer": {
      "smoke": { "vus": 5, "duration": "10s" },
      "average": { "vus": 20, "duration": "2m" }
    }
  }
}
```

### 3. Realistic Load Profiles

Use stages for realistic ramp-up/down:

```javascript
export const options = {
  stages: [
    { duration: '1m', target: 10 }, // Ramp up
    { duration: '5m', target: 10 }, // Steady state
    { duration: '1m', target: 50 }, // Spike
    { duration: '2m', target: 50 }, // Sustained spike
    { duration: '1m', target: 10 }, // Recovery
    { duration: '1m', target: 0 }, // Ramp down
  ],
};
```

### 4. Comprehensive Thresholds

Define thresholds for all critical metrics:

```javascript
export const options = {
  thresholds: {
    // Response times
    http_req_duration: ['p(95)<500', 'p(99)<1000'],

    // Error rates
    http_req_failed: ['rate<0.01'],

    // Throughput
    http_reqs: ['rate>10'],

    // Checks
    checks: ['rate>0.99'],
  },
};
```

## Configuration Validation

Before running load tests:

1. Verify scenario exists in `config.json.dist`
2. Check VU counts are appropriate for environment
3. Ensure duration allows meaningful results
4. Validate thresholds match SLAs
5. Confirm cleanup works properly

## Common Configuration Issues

### Issue: Tests Timeout

**Solution**: Increase duration or reduce VUs

```json
// Before
"stress": { "vus": 100, "duration": "1m" }

// After
"stress": { "vus": 50, "duration": "5m" }
```

### Issue: High Failure Rate

**Solution**: Start with lower VUs

```json
// Before
"average": { "vus": 50, "duration": "2m" }

// After
"average": { "vus": 10, "duration": "2m" }
```

### Issue: Insufficient Data

**Solution**: Increase duration or VUs

```json
// Before
"smoke": { "vus": 1, "duration": "5s" }

// After
"smoke": { "vus": 2, "duration": "10s" }
```

## Metrics Export and Monitoring

K6 supports exporting metrics to various backends for monitoring and analysis:

- **InfluxDB**: Time-series database for K6 metrics. Use `--out influxdb=http://localhost:8086/k6` flag
- **Prometheus**: Export metrics with `k6-remote` extension or InfluxDB bridge
- **Grafana**: Visualize K6 metrics with official K6 dashboards

For detailed setup instructions, see:

- [K6 InfluxDB integration](https://k6.io/docs/results-output/real-time/influxdb/)
- [K6 Prometheus integration](https://k6.io/docs/results-output/real-time/prometheus-remote-write/)
- [K6 Grafana dashboards](https://k6.io/docs/results-output/grafana-dashboards/)
