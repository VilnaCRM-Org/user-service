# PR 278 Vs Main Load Test Comparison

This report compares the PR 278 load-test results with an `origin/main` rerun on the same workstation.

## Methodology

- PR source: branch `codex/pr278-optimize`, measured on April 26, 2026.
- Optimized targeted PR rerun: branch `codex/pr278-optimize`, measured on April 27, 2026 after recovery-code hashing, bulk write/delete, lookup, and index changes. Raw logs are under `tests/Load/loadTestsResults/pr278-optimized-logs/*-after-argon2-tune.log`.
- Main source: `origin/main`, commit `e8437d752b438e0a497739946513fb089579efd9`, rerun locally on April 26, 2026.
- Load stack: `docker-compose.load-tests.yml`.
- Threshold mode: `LOAD_TEST_DISABLE_DURATION_THRESHOLDS=true`; functional check thresholds remained enabled.
- Threshold validation: targeted April 27 rerun also passed with duration thresholds enabled after tightening the 2FA/recovery p99 limits from `10000ms` to `1000-3000ms`.
- Load-test config source: k6 reads `tests/Load/config.json` when present and otherwise falls back to `tests/Load/config.json.dist`. `tests/Load/config.prod.json` is the alternate production-host profile and now carries the same 50 scenario key set.
- k6 safety limit: `LOAD_TEST_K6_MEMORY_LIMIT=4g` and `LOAD_TEST_K6_MEMORY_SWAP_LIMIT=4g`.
- Main raw logs: `/tmp/main-load/continuation/*.log`; memory watchdog log: `/tmp/main-load/memory-watch-20260426T200202Z.log`.
- Main result extraction used only the final k6 scenario block. `prepareUsers.js` summaries were not used as scenario metrics.

The main rerun attempted all 50 load scenarios. It produced usable metrics for 35 rows and failed 15 old-main scenarios with Docker/k6 `Error 137` at the 4 GiB memory cap. Those failures are reported as memory failures, not throughput numbers.

## Summary

- PR 278 completed **50/50** scenarios.
- Old main completed **35/50** scenarios under the same bounded local runner and failed **15/50** with `Error 137`.
- On the 34 rows with numeric request throughput on both branches, PR throughput increased in **12**, stayed flat within 1% in **21**, and decreased in **1**.
- On the rows with comparable p99 latency, PR p99 improved in **22**, regressed in **12**, and was effectively flat in **1**.
- The eight previously weak 2FA/recovery-code rows now all beat main on throughput and tail latency after the targeted April 27 optimization pass.
- The old-main memory failures are the major result: old scripts duplicate large `users.json` data into VUs; PR now uses `SharedArray`, avoids returning the full user list from `setup()`, and caps k6 by default.

## Where Performance Increased

- `oauth`: req/s improved from `46.168572` to `48.374117` (`+4.8%`), p99 improved from `2.22s` to `2.07s`, and dropped iterations fell from `787` to `389`.
- `createUser`: req/s improved from `24.121960` to `26.974562` (`+11.8%`), p99 improved from `3.56s` to `2.75s`, and dropped iterations fell from `1423` to `925`.
- `createUserBatch`: req/s improved from `4.124232` to `5.225969` (`+26.7%`), and p99 improved from `41.35s` to `20.82s`.
- `oauthSocialCallback`: req/s improved from `79.281494` to `87.644465` (`+10.5%`), p99 improved from `2.15s` to `1.62s`, and dropped iterations fell from `1906` to `1199`.
- `confirmTwoFactor`: req/s improved from `1.619441` to `3.654515` (`+125.7%`), p99 improved from `10.19s` to `852.72ms`, and dropped iterations fell from `120` to `0`.
- `disableTwoFactor`: req/s improved from `1.925533` to `3.654364` (`+89.8%`), p99 improved from `9.68s` to `574ms`, and dropped iterations fell from `131` to `0`.
- `signinTwoFactor`: req/s improved from `1.784215` to `4.809812` (`+169.6%`), p99 improved from `9.65s` to `519ms`, and dropped iterations fell from `110` to `0`.
- `regenerateRecoveryCodes`: req/s improved from `1.130708` to `4.841409` (`+328.2%`), p99 improved from `10.7s` to `837.33ms`, and dropped iterations fell from `168` to `2`.
- `graphQLCompleteTwoFactor`: req/s improved from `2.285527` to `5.196825` (`+127.4%`), and p99 improved from `10.79s` to `1.79s`.
- `graphQLConfirmTwoFactor`: req/s improved from `1.555889` to `3.66804` (`+135.8%`), p99 improved from `11.85s` to `581.64ms`, and dropped iterations fell from `125` to `0`.
- `graphQLDisableTwoFactor`: req/s improved from `1.985124` to `3.646914` (`+83.7%`), p99 improved from `9.82s` to `617.63ms`, and dropped iterations fell from `128` to `0`.
- `graphQLRegenerateRecoveryCodes`: req/s improved from `1.091036` to `4.589935` (`+320.7%`), p99 improved from `14.49s` to `1.95s`, and dropped iterations fell from `169` to `13`.
- Several fixed-rate scenarios held throughput flat but improved tail latency, including `apiContextUser`, `apiDocs`, `apiErrors400`, `oauthSocialInitiate`, `refreshToken`, and `resetPassword`.

## Where Performance Did Not Increase

- `graphQLCreateUser` remains the only numeric-throughput row with a clear decrease, dropping `3.4%` req/s while still improving p99 from `4.89s` to `4.79s`.
- Some fixed-rate lightweight endpoints held req/s flat but had worse p99, including `apiEntrypoint`, `apiValidationErrors`, `apiWellKnownGenid`, `health`, and `oauthAuthorize`.
- `cachePerformance` improved average duration slightly (`187.57ms` to `182.22ms`) but p99 was slightly worse (`1122.14ms` to `1167.82ms`).

## Memory Failures On Main

These old-main scenarios failed at the 4 GiB k6 cap and therefore do not have safe same-PC throughput numbers:

`confirmUser`, `deleteUser`, `getUser`, `getUsers`, `graphQLConfirmPasswordReset`, `graphQLConfirmUser`, `graphQLDeleteUser`, `graphQLGetUser`, `graphQLGetUsers`, `graphQLResendEmailToUser`, `graphQLUpdateUser`, `replaceUser`, `resendEmailToUser`, `resetPasswordConfirm`, `updateUser`.

The watchdog never had to kill tests for host-level pressure. The lowest important samples still had more than 12 GiB host memory available while Docker killed k6 inside its 4 GiB container limit. The dangerous behavior was contained to the load-generator container.

## Detailed Results

| Scenario                         | Main status  | Main req/s |  PR req/s | Req/s change | Main avg |   PR avg |  Main p99 |    PR p99 | P99 change | Dropped main -> PR | Result                        |
| -------------------------------- | ------------ | ---------: | --------: | -----------: | -------: | -------: | --------: | --------: | ---------: | ------------------ | ----------------------------- |
| `oauth`                          | `ok-initial` |  46.168572 | 48.374117 |        +4.8% | 569.35ms | 413.62ms |     2.22s |     2.07s |      -6.8% | 787 -> 389         | better throughput, better p99 |
| `apiContextUser`                 | `ok-initial` |  39.433021 | 39.438543 |        +0.0% |   2.10ms |   1.72ms |    5.25ms |    3.67ms |     -30.1% | - -> -             | flat throughput               |
| `apiDocs`                        | `ok-initial` |  39.427552 | 39.432863 |        +0.0% |   2.58ms |   1.73ms |    6.66ms |    2.63ms |     -60.5% | - -> -             | flat throughput               |
| `apiEntrypoint`                  | `ok-initial` |  39.433039 | 39.433034 |        -0.0% |   1.81ms |   2.31ms |    3.23ms |    5.95ms |     +84.2% | - -> -             | flat throughput               |
| `apiErrors400`                   | `ok-initial` |  39.433010 | 39.433084 |        +0.0% |   2.04ms |   1.65ms |    4.43ms |    2.85ms |     -35.7% | - -> -             | flat throughput               |
| `apiValidationErrors`            | `ok-initial` |  39.433038 | 39.438497 |        +0.0% |   1.94ms |   2.38ms |    3.55ms |    7.12ms |    +100.6% | - -> -             | flat throughput               |
| `apiWellKnownGenid`              | `ok-initial` |  39.427568 | 39.427433 |        -0.0% |   2.62ms |   2.56ms |    4.41ms |    6.77ms |     +53.5% | - -> -             | flat throughput               |
| `cachePerformance`               | `ok-custom`  |          - |         - |            - | 187.57ms | 182.22ms | 1122.14ms | 1167.82ms |      +4.1% | - -> -             | custom metric                 |
| `confirmTwoFactor`               | `ok`         |   1.619441 |  3.654515 |      +125.7% |    1.88s | 140.56ms |    10.19s | 852.72ms |     -91.6% | 120 -> 0           | better throughput, better p99 |
| `confirmUser`                    | `oom137`     |          - |  75.48563 |            - |        - |  13.22ms |         - |   216.7ms |          - | - -> 42            | OOM on main                   |
| `createUser`                     | `ok`         |   24.12196 | 26.974562 |       +11.8% |    1.13s | 941.31ms |     3.56s |     2.75s |     -22.8% | 1423 -> 925        | better throughput, better p99 |
| `createUserBatch`                | `ok`         |   4.124232 |  5.225969 |       +26.7% |   11.45s |    9.01s |    41.35s |    20.82s |     -49.6% | 4891 -> 4734       | better throughput, better p99 |
| `deleteUser`                     | `oom137`     |          - | 45.347703 |            - |        - | 309.58ms |         - |  998.85ms |          - | - -> 240           | OOM on main                   |
| `disableTwoFactor`               | `ok`         |   1.925533 |  3.654364 |       +89.8% |    1.53s | 106.73ms |     9.68s |    574ms |     -94.1% | 131 -> 0           | better throughput, better p99 |
| `getUser`                        | `oom137`     |          - | 43.646282 |            - |        - | 314.89ms |         - |     1.31s |          - | - -> 3844          | OOM on main                   |
| `getUsers`                       | `oom137`     |          - |  7.454378 |            - |        - |   11.76s |         - |     26.7s |          - | - -> 11119         | OOM on main                   |
| `graphQLCompleteTwoFactor`       | `ok`         |   2.285527 |  5.196825 |      +127.4% |     1.3s | 288.92ms |    10.79s |     1.79s |     -83.4% | 136 -> 32          | better throughput, better p99 |
| `graphQLConfirmPasswordReset`    | `oom137`     |          - | 25.661271 |            - |        - | 451.44ms |         - |     3.52s |          - | - -> 2804          | OOM on main                   |
| `graphQLConfirmTwoFactor`        | `ok`         |   1.555889 |   3.66804 |      +135.8% |    1.88s | 151.88ms |    11.85s | 581.64ms |     -95.1% | 125 -> 0           | better throughput, better p99 |
| `graphQLConfirmUser`             | `oom137`     |          - | 60.827284 |            - |        - | 452.17ms |         - |     2.07s |          - | - -> 1385          | OOM on main                   |
| `graphQLCreateUser`              | `ok`         |  21.182024 | 20.452036 |        -3.4% |    1.45s |    1.57s |     4.89s |     4.79s |      -2.0% | 1914 -> 2028       | lower throughput              |
| `graphQLDeleteUser`              | `oom137`     |          - | 34.746163 |            - |        - |       1s |         - |     3.13s |          - | - -> 2360          | OOM on main                   |
| `graphQLDisableTwoFactor`        | `ok`         |   1.985124 |  3.646914 |       +83.7% |    1.49s | 134.96ms |     9.82s | 617.63ms |     -93.7% | 128 -> 0           | better throughput, better p99 |
| `graphQLGetUser`                 | `oom137`     |          - | 32.494856 |            - |        - |    1.33s |         - |     3.62s |          - | - -> 6006          | OOM on main                   |
| `graphQLGetUsers`                | `oom137`     |          - |  3.138399 |            - |        - |   18.18s |         - |    45.08s |          - | - -> 11739         | OOM on main                   |
| `graphQLRefreshToken`            | `ok`         |   8.321577 |  8.328528 |        +0.1% |  15.88ms |  16.38ms |   26.29ms |   31.03ms |     +18.0% | - -> -             | flat throughput               |
| `graphQLRegenerateRecoveryCodes` | `ok`         |   1.091036 |  4.589935 |      +320.7% |     3.2s | 296.81ms |    14.49s |     1.95s |     -86.5% | 169 -> 13          | better throughput, better p99 |
| `graphQLRequestPasswordReset`    | `ok`         |   3.200478 |  3.196824 |        -0.1% |  60.27ms |  58.93ms |   78.24ms |   75.14ms |      -4.0% | - -> -             | flat throughput               |
| `graphQLResendEmailToUser`       | `oom137`     |          - | 24.440627 |            - |        - |    1.24s |         - |     3.41s |          - | - -> 2199          | OOM on main                   |
| `graphQLSetupTwoFactor`          | `ok`         |   5.991738 |   5.97883 |        -0.2% |  12.86ms |  14.44ms |   25.43ms |   25.07ms |      -1.4% | - -> -             | flat throughput               |
| `graphQLSignin`                  | `ok`         |   4.163099 |  4.158057 |        -0.1% |  14.69ms |  15.25ms |   24.37ms |   26.17ms |      +7.4% | - -> -             | flat throughput               |
| `graphQLSignout`                 | `ok`         |   8.321556 |  8.327075 |        +0.1% |  15.19ms |   15.3ms |   38.38ms |   36.12ms |      -5.9% | - -> -             | flat throughput               |
| `graphQLSignoutAll`              | `ok`         |   8.320281 |   8.32043 |        +0.0% |  14.83ms |  16.22ms |    24.3ms |   28.01ms |     +15.3% | - -> -             | flat throughput               |
| `graphQLUpdateUser`              | `oom137`     |          - | 18.978093 |            - |        - |    1.82s |         - |     3.95s |          - | - -> 3283          | OOM on main                   |
| `health`                         | `ok`         |  39.432992 | 39.433037 |        +0.0% |   5.26ms |   8.58ms |     8.9ms |    97.7ms |    +997.8% | - -> -             | flat throughput               |
| `oauthAuthorize`                 | `ok`         |  39.433093 | 39.433097 |        +0.0% |   1.61ms |   1.76ms |    2.79ms |    3.28ms |     +17.6% | - -> -             | flat throughput               |
| `oauthSocialCallback`            | `ok`         |  79.281494 | 87.644465 |       +10.5% | 580.42ms | 443.25ms |     2.15s |     1.62s |     -24.7% | 1906 -> 1199       | better throughput, better p99 |
| `oauthSocialInitiate`            | `ok`         |  50.549213 | 50.549411 |        +0.0% |   4.84ms |   2.34ms |    53.2ms |    4.17ms |     -92.2% | - -> -             | flat throughput               |
| `refreshToken`                   | `ok`         |   8.319847 |  8.320536 |        +0.0% |   9.41ms |    8.9ms |    16.1ms |   13.83ms |     -14.1% | - -> -             | flat throughput               |
| `regenerateRecoveryCodes`        | `ok`         |   1.130708 |  4.841409 |      +328.2% |    2.87s | 159.21ms |     10.7s | 837.33ms |     -92.2% | 168 -> 2           | better throughput, better p99 |
| `replaceUser`                    | `oom137`     |          - | 22.469206 |            - |        - |    1.34s |         - |     4.12s |          - | - -> 2499          | OOM on main                   |
| `resendEmailToUser`              | `oom137`     |          - | 26.274168 |            - |        - |    1.11s |         - |     2.08s |          - | - -> 1709          | OOM on main                   |
| `resetPassword`                  | `ok`         |   3.189913 |  3.191142 |        +0.0% |  54.59ms |  53.75ms |   82.52ms |   72.07ms |     -12.7% | - -> -             | flat throughput               |
| `resetPasswordConfirm`           | `oom137`     |          - | 27.895229 |            - |        - | 375.43ms |         - |     2.51s |          - | - -> 2186          | OOM on main                   |
| `setupTwoFactor`                 | `ok`         |   6.002015 |  6.000656 |        -0.0% |   8.89ms |   7.96ms |   19.05ms |   23.26ms |     +22.1% | - -> -             | flat throughput               |
| `signin`                         | `ok`         |   4.168414 |  4.163738 |        -0.1% |  10.08ms |   9.35ms |   17.77ms |   18.12ms |      +2.0% | - -> -             | flat throughput               |
| `signinTwoFactor`                | `ok`         |   1.784215 |  4.809812 |      +169.6% |    1.33s | 102.19ms |     9.65s |    519ms |     -94.6% | 110 -> 0           | better throughput, better p99 |
| `signout`                        | `ok`         |   8.308501 |  8.331509 |        +0.3% |   9.76ms |    8.8ms |   19.77ms |   18.69ms |      -5.5% | - -> -             | flat throughput               |
| `signoutAll`                     | `ok`         |   8.309469 |   8.32185 |        +0.1% |   9.64ms |   8.57ms |   15.37ms |   15.64ms |      +1.8% | - -> -             | flat throughput               |
| `updateUser`                     | `oom137`     |          - | 23.002379 |            - |        - |    1.37s |         - |     2.69s |          - | - -> 2405          | OOM on main                   |

## Why Throughput Degraded In Some Rows

Most scenarios use fixed arrival rates. When the application or local Docker stack cannot finish iterations quickly enough within the scenario VU cap, k6 drops scheduled iterations, and observed req/s falls below the target. That is why lower throughput usually appears together with high p95/p99 and dropped iterations.

The earlier degraded rows were concentrated in stateful auth, 2FA, and recovery-code flows. The April 27 optimization pass removed that bottleneck by reducing Argon2id recovery-code cost to an explicit service setting, batching recovery-code writes, deleting/counting recovery codes with MongoDB queries, revoking sessions/tokens in bulk, resolving UUID subjects before email lookup, and adding compound indexes for the hot MongoDB filters.

The remaining throughput decrease is `graphQLCreateUser` at `-3.4%`, where p99 still improved slightly. The old-main memory failures are a separate issue in the load generator: old scripts load and pass large user fixtures in a way that multiplies memory per VU. PR 278 now avoids that pattern with shared fixture data and default Docker memory limits for k6.
