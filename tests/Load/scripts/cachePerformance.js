import http from 'k6/http';
import { check } from 'k6';
import { Trend, Rate, Counter } from 'k6/metrics';
import counter from 'k6/x/counter';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import InsertUsersUtils from '../utils/insertUsersUtils.js';
import MailCatcherUtils from '../utils/mailCatcherUtils.js';

const scenarioName = 'cachePerformance';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);

const users = insertUsersUtils.loadInsertedUsers();

// Custom metrics for cache performance analysis
const requestDuration = new Trend('cache_request_duration', true);
const successRate = new Rate('cache_success_rate');
const totalRequests = new Counter('cache_total_requests');
const fastResponses = new Counter('cache_fast_responses'); // < 100ms suggests cache hit

// Threshold for "fast" response (likely cache hit)
// Note: This is heuristic-based. Real cache verification is done in integration tests.
const FAST_RESPONSE_THRESHOLD_MS = 100;

export function setup() {
  // Warm up phase: access each user once to populate cache
  console.log('Cache warmup: populating cache with initial reads...');

  const warmupCount = Math.min(users.length, 100);
  let warmupSuccesses = 0;

  for (let i = 0; i < warmupCount; i++) {
    const user = users[i];
    const response = http.get(`${utils.getBaseHttpUrl()}/${user.id}`, utils.getJsonHeader());

    if (response.status === 200 && response.body && response.body.length > 0) {
      warmupSuccesses++;
    }
  }

  console.log(`Cache warmup complete. ${warmupSuccesses}/${warmupCount} users cached.`);

  if (warmupSuccesses === 0) {
    throw new Error('Cache warmup failed - no successful responses. Check if API is running.');
  }

  return {
    users: users,
    warmupCount: warmupCount,
  };
}

export const options = scenarioUtils.getOptions();

export default function cachePerformance(data) {
  const userIndex = counter.up() % data.warmupCount;
  const user = data.users[userIndex];
  utils.checkUserIsDefined(user);

  const { id } = user;

  const response = http.get(`${utils.getBaseHttpUrl()}/${id}`, utils.getJsonHeader());

  // Record metrics
  totalRequests.add(1);
  requestDuration.add(response.timings.duration);

  const isSuccess = check(response, {
    'status is 200': r => r.status === 200,
    'response has data': r => r.body && r.body.length > 0,
  });

  successRate.add(isSuccess ? 1 : 0);

  // Track fast responses (likely cache hits)
  if (isSuccess && response.timings.duration < FAST_RESPONSE_THRESHOLD_MS) {
    fastResponses.add(1);
  }
}

export function handleSummary(data) {
  const total = data.metrics.cache_total_requests
    ? data.metrics.cache_total_requests.values.count
    : 0;
  const fast = data.metrics.cache_fast_responses
    ? data.metrics.cache_fast_responses.values.count
    : 0;
  const fastRatio = total > 0 ? ((fast / total) * 100).toFixed(2) : 0;

  const avgDuration = data.metrics.cache_request_duration
    ? data.metrics.cache_request_duration.values.avg.toFixed(2)
    : 'N/A';
  const p95Duration = data.metrics.cache_request_duration
    ? data.metrics.cache_request_duration.values['p(95)'].toFixed(2)
    : 'N/A';
  const p99Duration = data.metrics.cache_request_duration
    ? data.metrics.cache_request_duration.values['p(99)'].toFixed(2)
    : 'N/A';

  const successRateValue = data.metrics.cache_success_rate
    ? (data.metrics.cache_success_rate.values.rate * 100).toFixed(2)
    : 'N/A';

  console.log('\n=== CACHE PERFORMANCE LOAD TEST SUMMARY ===');
  console.log(`Total Requests: ${total}`);
  console.log(`Success Rate: ${successRateValue}%`);
  console.log(`Fast Responses (<${FAST_RESPONSE_THRESHOLD_MS}ms): ${fast} (${fastRatio}%)`);
  console.log(`Avg Duration: ${avgDuration}ms`);
  console.log(`P95 Duration: ${p95Duration}ms`);
  console.log(`P99 Duration: ${p99Duration}ms`);
  console.log('============================================');
  console.log('Note: Cache correctness is verified by integration tests.');
  console.log('This load test measures performance under concurrent load.\n');

  // Fail only on actual errors, not on heuristic-based cache detection
  const httpFailRate = data.metrics.http_req_failed ? data.metrics.http_req_failed.values.rate : 0;

  if (httpFailRate > 0.01) {
    // More than 1% failure rate
    console.error(
      `FAIL: HTTP error rate (${(httpFailRate * 100).toFixed(2)}%) exceeds 1% threshold`
    );
    return { stdout: JSON.stringify({ failed: true, reason: 'High error rate' }) };
  }

  return {};
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}
