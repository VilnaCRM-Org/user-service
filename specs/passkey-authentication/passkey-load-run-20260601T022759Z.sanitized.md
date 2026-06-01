# Passkey Load Run 20260601T022759Z

This is the durable sanitized summary for the current PR passkey option
load-test run. It excludes generated HTML reports and raw container logs.

## Metadata

- Tester: Codex
- Execution date/time (UTC): 2026-06-01
- Compose project: `user-service-pr286-passkey-load`
- API origin: `http://localhost:19081`
- MongoDB override: `var/ai-review/load-test-mongo7.compose.yml`
- Preparation: `make setup-load-test-db`
- Command shape:
  `tests/Load/execute-load-test.sh <scenario> true true true true`
- Raw local logs:
  `/home/kravtsov/tmp/pr286-passkey-load-mongo7-after-setup-20260601T022759Z`

## Thresholds

- Checks: `rate>0.99`
- Smoke p99: `<1500ms`
- Average p99: `<1500ms`
- Stress p99: `<3000ms`
- Spike p99: `<5000ms`

## Results

| Scenario                     | Checks | Smoke p99 | Average p99 | Stress p99 | Spike p99 | Result |
| ---------------------------- | ------ | --------: | ----------: | ---------: | --------: | ------ |
| `passkeySignupOptions`       | 100%   |   48.21ms |     78.89ms |    89.48ms |  165.49ms | Pass   |
| `passkeySigninOptions`       | 100%   |   357.2ms |     67.77ms |   115.23ms |    5.97ms | Pass   |
| `passkeyRegistrationOptions` | 100%   |   108.6ms |    164.63ms |    73.29ms |  201.87ms | Pass   |

## Quality Finding

An earlier run without the repository load-test database setup failed
`passkeySignupOptions` stress at p99 `6.14s`, above the `3000ms` threshold. The
correct fix was to execute the repository setup target so schema, indexes, OAuth
client, and JWT fixtures matched the documented load-test preconditions before
rerunning all passkey profiles.
