Welcome to the **Performance and Optimization** GitHub page, which is dedicated to showcasing our comprehensive approach to enhancing the efficiency and speed of our User Service application. Our goal is to share insights, methodologies, and results from rigorous testing and optimization processes to help developers achieve peak performance in their own applications.

## Testing Environment

To ensure our User Service is optimized for high performance, we conducted extensive testing using standardized infrastructure on AWS. This approach allows us to identify bottlenecks, optimize code, and achieve significant performance improvements. By utilizing AWS for our testing environment, we ensure consistency and uniformity across all tests, enabling developers to work with the same setup and achieve comparable results. This unified testing framework provides a reliable foundation for evaluating performance, regardless of geographic location or hardware variations, ensuring all team members can collaborate effectively on optimization efforts. Below are the details of the hardware and software components used in our testing environment:

### Server Specifications:

- **Instance Type:** c6i.4xlarge
- **CPU:** 16 core
- **Memory:** 32 GB RAM
- **Storage:** 30 GB

### Software Specifications:

- **Operating System:** Ubuntu 24.04 LTS
- **User Service Version:** 2.3.0
- **PHP Version:** 8.3
- **Symfony Version:** 7.1/7.2
- **Database:** MariaDB 11.4
- **Load Testing Tools:** Grafana K6

## Recent Application-Level Optimizations

- `GET /api/users` now supports opt-in partial pagination through the `partial` query parameter, letting clients skip the extra pagination count query when they do not need a total item count.
- `POST /api/users/batch` now preloads existing users with a single bulk email lookup instead of performing one duplicate-email query per incoming user.
- Batch registration assembly now lives in a dedicated factory, which keeps the hot path explicit and makes the bulk-lookup behavior easier to validate with repository and cache tests.

## PR 278 Performance Report

This pull request focused on two hot paths that were still visible under load:

- Detached user cache hits were requerying the database instead of reattaching the cached user to the active unit of work.
- User writes (`save`, `delete`, batch writes) were not invalidating the read cache directly, which left short-lived stale user state in auth flows such as 2FA setup and confirm.

The optimized build now:

- Reattaches detached cached users before falling back to the database.
- Invalidates `user.collection`, `user.{id}`, and `user.email.{hash}` tags immediately after user writes.
- Uses profile-aware K6 user/message offsets for stateful confirmation, password reset, and 2FA scripts, so smoke, average, stress, and spike profiles do not reuse already-mutated users.

For the detailed same-PC comparison with a fresh `origin/main` rerun, see [PR 278 Vs Main Load Test Comparison](./pr278-main-load-test-comparison.md).

### Measurement Setup

- Date: 2026-04-26
- Worktree: `user-service-pr278-opt`
- Branch / commit: `codex/pr278-optimize` / `092f19f4`
- Load stack: `docker-compose.load-tests.yml`
- Compose project: `user-service-pr278-full-load`
- Base URL: `http://localhost:48081`
- Command shape: `make load-tests` and `make execute-load-tests-script scenario=<scenario>` with smoke, average, stress, and spike enabled together.
- Threshold mode: `LOAD_TEST_DISABLE_DURATION_THRESHOLDS=true`; check thresholds remained active.
- k6 safety limit: `LOAD_TEST_K6_MEMORY_LIMIT=4g` and `LOAD_TEST_K6_MEMORY_SWAP_LIMIT=4g`.
- Raw logs: `/tmp/pr278-load/full-load-20260426T105949Z.log`, `/tmp/pr278-load/full-load-resume3-20260426T120319Z.log`, `/tmp/pr278-load/patched-stateful-reruns-20260426T152750Z.log`, `/tmp/pr278-load/oauthSocialCallback-rerun-20260426T162535Z.log`.
- Same-PC baseline: `origin/main` commit `e8437d752b438e0a497739946513fb089579efd9`, compose project `user-service-main-local-load`, base URL `http://localhost:58081`, raw logs under `/tmp/main-load/continuation`.

### Before / After Smoke Results

The table below compares the retained targeted smoke latency block for the same scenarios before and after the cache changes. RPS was not retained in these targeted before/after logs, so throughput is reported in the full-suite table below.

| Scenario          | Avg Before | Avg After | Avg Change | P99 Before | P99 After  | P99 Change |
| ----------------- | ---------- | --------- | ---------: | ---------- | ---------- | ---------: |
| `apiContextUser`  | `4.37ms`   | `2.37ms`  | `-45.8%`   | `14.48ms`  | `8.91ms`   | `-38.5%`   |
| `getUser`         | `11.89ms`  | `5.82ms`  | `-51.1%`   | `63.23ms`  | `17.63ms`  | `-72.1%`   |
| `getUsers`        | `28.43ms`  | `21.43ms` | `-24.6%`   | `66.94ms`  | `45.10ms`  | `-32.6%`   |
| `graphQLGetUser`  | `22.13ms`  | `14.20ms` | `-35.8%`   | `97.21ms`  | `33.77ms`  | `-65.3%`   |
| `graphQLGetUsers` | `319.49ms` | `56.46ms` | `-82.3%`   | `1.25s`    | `133.87ms` | `-89.3%`   |
| `updateUser`      | `149.62ms` | `67.63ms` | `-54.8%`   | `387.10ms` | `94.28ms`  | `-75.6%`   |
| `replaceUser`     | `177.16ms` | `61.86ms` | `-65.1%`   | `335.19ms` | `72.43ms`  | `-78.4%`   |
| `deleteUser`      | `18.25ms`  | `5.48ms`  | `-70.0%`   | `51.54ms`  | `9.19ms`   | `-82.2%`   |
| `signin`          | `34.18ms`  | `9.65ms`  | `-71.8%`   | `104.45ms` | `23.17ms`  | `-77.8%`   |
| `refreshToken`    | `29.95ms`  | `8.91ms`  | `-70.3%`   | `117.98ms` | `15.52ms`  | `-86.8%`   |

### Full-Suite Load Results

Final scenario status: **50/50 passed**. Each row is an all-profile run with smoke, average, stress, and spike enabled in the same K6 invocation. The `Notes` column identifies which clean result superseded earlier interrupted or pre-patch output.

| Scenario | Surface | Req/s | Iter/s | Avg | P95 | P99 | Dropped | Checks | Status | Notes |
| --- | --- | ---: | ---: | ---: | ---: | ---: | ---: | --- | --- | --- |
| `oauth` | REST | 48.374117 | 48.368562 | 413.62ms | 1.95s | 2.07s | 389 | 8708/8708 | Pass | full-load |
| `apiContextUser` | REST | 39.438543 | 39.438543 | 1.72ms | 2.73ms | 3.67ms | - | 7099/7099 | Pass | Expected 401; full-load |
| `apiDocs` | REST | 39.432863 | 39.432863 | 1.73ms | 2.23ms | 2.63ms | - | 7098/7098 | Pass | full-load |
| `apiEntrypoint` | REST | 39.433034 | 39.433034 | 2.31ms | 4.67ms | 5.95ms | - | 7098/7098 | Pass | Expected 401; full-load |
| `apiErrors400` | REST | 39.433084 | 39.433084 | 1.65ms | 2.24ms | 2.85ms | - | 7098/7098 | Pass | Expected 401; full-load |
| `apiValidationErrors` | REST | 39.438497 | 39.438497 | 2.38ms | 4.71ms | 7.12ms | - | 7099/7099 | Pass | Expected 401; full-load |
| `apiWellKnownGenid` | REST | 39.427433 | 39.427433 | 2.56ms | 3.47ms | 6.77ms | - | 7097/7097 | Pass | Expected 404; full-load |
| `cachePerformance` | REST | - | - | 182.22ms | 950.25ms | 1167.82ms | - | custom | Pass | Custom cache summary; 76.63% <100ms; full-load |
| `confirmTwoFactor` | REST | 1.318487 | 0.437695 | 2.37s | 8.96s | 10.8s | 139 | 405/405 | Pass | patched rerun |
| `confirmUser` | REST | 75.48563 | 37.740141 | 13.22ms | 80.25ms | 216.7ms | 42 | 7056/7056 | Pass | patched rerun |
| `createUser` | REST | 26.974562 | 26.969084 | 941.31ms | 2.53s | 2.75s | 925 | 4923/4923 | Pass | resume3 |
| `createUserBatch` | REST | 5.225969 | 5.221282 | 9.01s | 20.38s | 20.82s | 4734 | 1114/1114 | Pass | resume3 |
| `deleteUser` | REST | 45.347703 | 45.342584 | 309.58ms | 892.56ms | 998.85ms | 240 | 17716/17716 | Pass | resume3 |
| `disableTwoFactor` | REST | 1.293421 | 0.429351 | 2.38s | 8.94s | 11.35s | 139 | 320/320 | Pass | patched rerun |
| `getUser` | REST | 43.646282 | 43.641436 | 314.89ms | 1.12s | 1.31s | 3844 | 18010/18010 | Pass | resume3 |
| `getUsers` | REST | 7.454378 | 7.450069 | 11.76s | 26.51s | 26.7s | 11119 | 3458/3458 | Pass | resume3 |
| `graphQLCompleteTwoFactor` | GraphQL | 1.896128 | 0.378145 | 1.58s | 8.01s | 10.74s | 148 | 490/490 | Pass | patched rerun |
| `graphQLConfirmPasswordReset` | GraphQL | 25.661271 | 6.718462 | 451.44ms | 2.81s | 3.52s | 2804 | 12882/12882 | Pass | patched rerun |
| `graphQLConfirmTwoFactor` | GraphQL | 1.253784 | 0.41615 | 2.58s | 10.31s | 11.64s | 141 | 468/468 | Pass | patched rerun |
| `graphQLConfirmUser` | GraphQL | 60.827284 | 30.41098 | 452.17ms | 1.7s | 2.07s | 1385 | 11426/11426 | Pass | patched rerun |
| `graphQLCreateUser` | GraphQL | 20.452036 | 20.435987 | 1.57s | 4.57s | 4.79s | 2028 | 3821/3821 | Pass | resume3 |
| `graphQLDeleteUser` | GraphQL | 34.746163 | 34.741008 | 1s | 2.84s | 3.13s | 2360 | 13478/13478 | Pass | resume3 |
| `graphQLDisableTwoFactor` | GraphQL | 1.163208 | 0.385924 | 2.58s | 9.67s | 12.49s | 148 | 355/355 | Pass | patched rerun |
| `graphQLGetUser` | GraphQL | 32.494856 | 32.490108 | 1.33s | 3.43s | 3.62s | 6006 | 13684/13684 | Pass | resume3 |
| `graphQLGetUsers` | GraphQL | 3.138399 | 3.134553 | 18.18s | 40.6s | 45.08s | 11739 | 1925/1925 | Pass | resume3 |
| `graphQLRefreshToken` | GraphQL | 8.328528 | 4.161489 | 16.38ms | 24.56ms | 31.03ms | - | 3750/3750 | Pass | resume3 |
| `graphQLRegenerateRecoveryCodes` | GraphQL | 0.92329 | 0.223684 | 4.31s | 20.99s | 29.17s | 172 | 286/286 | Pass | resume3 |
| `graphQLRequestPasswordReset` | GraphQL | 3.196824 | 3.191274 | 58.93ms | 65.57ms | 75.14ms | - | 1725/1725 | Pass | resume3 |
| `graphQLResendEmailToUser` | GraphQL | 24.440627 | 24.435638 | 1.24s | 2.85s | 3.41s | 2199 | 9796/9796 | Pass | resume3 |
| `graphQLSetupTwoFactor` | GraphQL | 5.97883 | 2.986639 | 14.44ms | 19.54ms | 25.07ms | - | 2690/2690 | Pass | resume3 |
| `graphQLSignin` | GraphQL | 4.158057 | 4.152505 | 15.25ms | 21.01ms | 26.17ms | - | 2244/2244 | Pass | resume3 |
| `graphQLSignout` | GraphQL | 8.327075 | 4.160764 | 15.3ms | 23.83ms | 36.12ms | - | 3000/3000 | Pass | resume3 |
| `graphQLSignoutAll` | GraphQL | 8.32043 | 4.15744 | 16.22ms | 24.02ms | 28.01ms | - | 2996/2996 | Pass | resume3 |
| `graphQLUpdateUser` | GraphQL | 18.978093 | 18.973119 | 1.82s | 3.53s | 3.95s | 3283 | 7630/7630 | Pass | resume3 |
| `health` | REST | 39.433037 | 39.433037 | 8.58ms | 13.08ms | 97.7ms | - | 7098/7098 | Pass | resume3 |
| `oauthAuthorize` | REST | 39.433097 | 39.433097 | 1.76ms | 2.39ms | 3.28ms | - | 7098/7098 | Pass | resume3 |
| `oauthSocialCallback` | REST | 87.644465 | 43.822232 | 443.25ms | 1.38s | 1.62s | 1199 | 15800/15800 | Pass | final rerun |
| `oauthSocialInitiate` | REST | 50.549411 | 50.549411 | 2.34ms | 3.14ms | 4.17ms | - | 9099/9099 | Pass | resume3 |
| `refreshToken` | REST | 8.320536 | 4.157493 | 8.9ms | 11.62ms | 13.83ms | - | 3745/3745 | Pass | resume3 |
| `regenerateRecoveryCodes` | REST | 1.087341 | 0.270509 | 3.05s | 9.77s | 11.28s | 170 | 306/306 | Pass | resume3 |
| `replaceUser` | REST | 22.469206 | 22.464322 | 1.34s | 3.3s | 4.12s | 2499 | 9200/9200 | Pass | resume3 |
| `resendEmailToUser` | REST | 26.274168 | 26.269293 | 1.11s | 1.97s | 2.08s | 1709 | 10778/10778 | Pass | resume3 |
| `resetPassword` | REST | 3.191142 | 3.185592 | 53.75ms | 63.93ms | 72.07ms | - | 1148/1148 | Pass | resume3 |
| `resetPasswordConfirm` | REST | 27.895229 | 7.768972 | 375.43ms | 2.13s | 2.51s | 2186 | 9824/9824 | Pass | patched rerun |
| `setupTwoFactor` | REST | 6.000656 | 2.997553 | 7.96ms | 12.77ms | 23.26ms | - | 2700/2700 | Pass | resume3 |
| `signin` | REST | 4.163738 | 4.158186 | 9.35ms | 13.03ms | 18.12ms | - | 1498/1498 | Pass | resume3 |
| `signinTwoFactor` | REST | 1.865781 | 0.372078 | 1.29s | 6.2s | 7.8s | 106 | 483/483 | Pass | resume3 |
| `signout` | REST | 8.331509 | 4.162979 | 8.8ms | 14.2ms | 18.69ms | - | 2250/2250 | Pass | resume3 |
| `signoutAll` | REST | 8.32185 | 4.158149 | 8.57ms | 12.05ms | 15.64ms | - | 2247/2247 | Pass | resume3 |
| `updateUser` | REST | 23.002379 | 22.99748 | 1.37s | 2.59s | 2.69s | 2405 | 9388/9388 | Pass | resume3 |

### Outcome

- The biggest retained before/after win was the GraphQL user collection path: `319.49ms` average / `1.25s` p99 dropped to `56.46ms` average / `133.87ms` p99.
- The final all-profile run passed every configured load scenario after the patched stateful reruns and the clean `oauthSocialCallback` rerun.
- Highest final throughput was `oauthSocialCallback` at `87.64` req/s, followed by `confirmUser` at `75.49` req/s and `graphQLConfirmUser` at `60.83` req/s.
- The slowest local-stack p99 values were concentrated in collection and recovery-code flows: `graphQLGetUsers` at `45.08s`, `graphQLRegenerateRecoveryCodes` at `29.17s`, `getUsers` at `26.7s`, and `createUserBatch` at `20.82s`.
- The previously failing `graphQLCompleteTwoFactor` all-profile script now passes with `490/490` checks after profile-aware user selection.
- In the same-PC comparison, PR 278 completed `50/50` scenarios. Old main produced usable metrics for `35/50` scenarios and failed `15/50` with Docker/k6 `Error 137` at the 4 GiB safety cap.
- Across the 34 rows with numeric request throughput on both branches, PR throughput improved in `5`, was flat within 1% in `21`, and decreased in `8`. Comparable p99 latency improved in `17`, regressed in `17`, and was effectively flat in `1`.
- The largest same-PC throughput and tail-latency wins were `createUserBatch` (`+26.7%` req/s, p99 `41.35s` to `20.82s`), `createUser` (`+11.8%` req/s, p99 `3.56s` to `2.75s`), `oauthSocialCallback` (`+10.5%` req/s, p99 `2.15s` to `1.62s`), `oauth` (`+4.8%` req/s, p99 `2.22s` to `2.07s`), and `signinTwoFactor` (`+4.6%` req/s, p99 `9.65s` to `7.8s`).

### Regression Sweep Notes

- The initial uninterrupted `make load-tests` run was interrupted by load-test infrastructure during a MailCatcher-heavy setup step after `cachePerformance`. The final table therefore uses clean per-scenario resumed/rerun results rather than a single whole-suite elapsed-time aggregate.
- Whole-suite total runtime and aggregate throughput are intentionally not reported. The per-scenario RPS, latency, checks, and dropped-iteration values are comparable by scenario only.
- Dropped iterations indicate that the local Docker stack saturated the configured arrival-rate/VU caps for that scenario; they are not functional check failures.
- `LOAD_TEST_DISABLE_DURATION_THRESHOLDS=true` was used to keep latency threshold relaxation separate from functional check validation. Check thresholds remained enabled and passed for all final scenario rows.
- The lower-throughput same-PC comparison rows were concentrated in stateful 2FA and recovery-code flows, where slower responses saturated the fixed arrival-rate/VU configuration and caused more dropped iterations.
- Old-main `Error 137` rows show load-generator memory amplification from duplicating large `users.json` fixtures across VUs. PR 278 now loads inserted users with `SharedArray`, avoids returning full user lists from `setup()`, and runs k6 with a default Docker memory cap.
- These numbers are local Docker regression evidence for PR 278, not production capacity guidance.

## Benchmarks

Here you will find the results of load tests for each User Service endpoint, with a graph, that shows how execution parameters were changing over time for different load scenarios. Also, the metric for Spike testing will be provided, alongside a table, that will show the most important of them.

For the dedicated FrankenPHP runtime comparison used during the worker-mode rollout, see [FrankenPHP Worker Mode Vs Non-Worker Mode](./frankenphp-worker-mode-comparison.md). That report compares every configured REST, GraphQL, and OAuth load scenario under the same fixed-VU benchmark profile with worker mode enabled and disabled.

Each endpoint was tested for smoke, average, stress, and spike load scenarios. You can learn more about them [here](https://grafana.com/docs/k6/latest/testing-guides/test-types/).
Also, you can find HTML files with load test reports [here](https://github.com/VilnaCRM-Org/user-service/tree/main/tests/Load/results/fpm)

The most important metrics for each test, which you'll find in tables include:

- **Target rps:** This number specifies the max requests per second rate, that will be reached during the test.
- **Real rps:** This number specifies the average requests per second rate, that was reached during a testing scenario.
- **Virtual users:** The number of simulated users accessing the service simultaneously. This helps in understanding how the application performs under different levels of user concurrency.
- **Rise duration:** The time period over which the load is gradually increased from zero to the desired number of requests per second or virtual users. This helps to observe how the system scales with increasing load.
- **Plateau duration:** The time period over which the load is holding the peak load. This helps to monitor the system's ability to handle the constant load gracefully.
- **Fall duration:** The time period over which the load is gradually decreased back to zero from the peak load. This helps to monitor the system's ability to gracefully handle the reduction in load and ensure there are no residual issues.
- **P(99):** This number specifies the time, which it took for 99% of requests to receive a successful response.

### REST API

- [Get User](#Get-User-Test)
- [Get User Collection](#Get-User-Test)
- [Create User](#Create-User-Test)
- [Create User Batch](#Create-User-Batch-Test)
- [Confirm User](#Confirm-User-Test)
- [Replace User](#Replace-User-Test)
- [Update User](#Update-User-Test)
- [Delete User](#Delete-User-Test)
- [Resend Confirmation Email To User](#Resend-Confirmation-Email-To-User-Test)
- [OAuth](#OAuth-Test)
- [Health](#Health-Test)

### GraphQL

- [Get User](#GraphQL-Get-User-Test)
- [Get User Collection](#GraphQL-Get-User-Collection-Test)
- [Create User](#GraphQL-Create-User-Test)
- [Confirm User](#GraphQL-Confirm-User-Test)
- [Update User](#GraphQL-Update-User-Test)
- [Delete User](#GraphQL-Delete-User-Test)
- [Resend Confirmation Email To User](#GraphQL-Resend-Confirmation-Email-To-User-Test)

### Get User Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 62       | 400           | 10s           | 10s           | 6ms   |

![image](https://github.com/user-attachments/assets/08978579-a4f1-43ae-ad49-b2c3b58a4e3f)
![image](https://github.com/user-attachments/assets/e25787b6-3b11-4b92-95c6-b5f812207f04)

[Go back to navigation](#REST-API)

### Get User Collection Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | Users retrieved with each request | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | --------------------------------- | ----- |
| 400        | 62       | 400           | 10s           | 10s           | 50                                | 13ms  |

![image](https://github.com/user-attachments/assets/b0a53b62-4dc7-4774-8006-2a57cb0d7e3c)
![image](https://github.com/user-attachments/assets/57cca0d9-5c97-4150-a5a5-26106820416d)

[Go back to navigation](#REST-API)

### Create User Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 24       | 200           | 10s           | 10s           | 3s    |

![image](https://github.com/user-attachments/assets/695725de-94e9-48a4-a835-72b9a6619b22)
![image](https://github.com/user-attachments/assets/a9b4829b-2787-4704-af11-e07e29fcd0ec)

[Go back to navigation](#REST-API)

### Create User Batch Test

| Target RPS | BatchSize | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | --------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 10        | 9        | 200           | 10s           | 10s           | 8s    |

![image](https://github.com/user-attachments/assets/27137601-44c4-4673-ac2b-6dde91c6b30d)
![image](https://github.com/user-attachments/assets/8ed4d76a-700d-43a6-bf64-c878d404105e)

[Go back to navigation](#REST-API)

### Confirm User Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 75       | 200           | 10s           | 10s           | 10ms  |

![image](https://github.com/user-attachments/assets/4b11b4c8-71b7-431d-a962-fda920d679f1)
![image](https://github.com/user-attachments/assets/b07f8fd5-814d-4c7c-8e4a-93c33b262c2a)

[Go back to navigation](#REST-API)

### Replace User Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 23       | 200           | 10s           | 10s           | 3s    |

![image](https://github.com/user-attachments/assets/afc13378-58bf-40c3-997c-3d84bf475469)
![image](https://github.com/user-attachments/assets/eaf59243-fec3-455c-add7-e9c880fa53e6)

[Go back to navigation](#REST-API)

### Update User Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 23       | 200           | 10s           | 10s           | 4s    |

![image](https://github.com/user-attachments/assets/d116710f-8b45-485d-950f-b211d8489463)
![image](https://github.com/user-attachments/assets/7033be7b-cabc-4903-b68e-3c38cf14046d)

[Go back to navigation](#REST-API)

### Delete User Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 47       | 400           | 10s           | 10s           | 10ms  |

![image](https://github.com/user-attachments/assets/ffb83454-c5a9-4c1a-b656-11b47b9d1a36)
![image](https://github.com/user-attachments/assets/10bc0498-3c3e-46c0-9d5b-5771904dd58e)

[Go back to navigation](#REST-API)

### Resend Confirmation Email To User Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 22       | 200           | 10s           | 10s           | 4s    |

![image](https://github.com/user-attachments/assets/9d4f5b33-b13b-4462-9efc-f2c07456d2c7)
![image](https://github.com/user-attachments/assets/5954f769-ee45-4e4c-9fc7-089d2f017a21)

[Go back to navigation](#REST-API)

### OAuth Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 48       | 400           | 10s           | 10s           | 87ms  |

![image](https://github.com/user-attachments/assets/308a1efc-5205-4e64-bfdd-f4ee3151d5a2)
![image](https://github.com/user-attachments/assets/2962ab51-7c68-496c-ad05-de0be3593b3d)

[Go back to navigation](#REST-API)

### Health Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 40       | 200           | 10s           | 10s           | 8ms   |

![image](https://github.com/user-attachments/assets/f14f5897-a2a5-463f-8331-abbdbc6e427d)
![image](https://github.com/user-attachments/assets/a879272b-3169-469d-b792-458a0374e83d)

[Go back to navigation](#REST-API)

### GraphQL Get User Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 62       | 400           | 10s           | 10s           | 25ms  |

![image](https://github.com/user-attachments/assets/0676fa54-27dc-4107-914e-f89e99eec7ca)
![image](https://github.com/user-attachments/assets/f45ac6c7-20b6-439c-a013-95cafbe7b841)

[Go back to navigation](#REST-API)

### GraphQL Get User Collection Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | Users retrieved with each request | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | --------------------------------- | ----- |
| 400        | 62       | 400           | 10s           | 10s           | 50                                | 15ms  |

![image](https://github.com/user-attachments/assets/b244ca63-b4cf-4474-a588-514a9482386d)
![image](https://github.com/user-attachments/assets/9ade9e0f-6d21-4b4d-9426-5c6310e3aa72)

[Go back to navigation](#REST-API)

### GraphQL Create User Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 24       | 200           | 10s           | 10s           | 3s    |

![image](https://github.com/user-attachments/assets/b41ed804-2817-40ab-b482-4cafd2bc748f)
![image](https://github.com/user-attachments/assets/9193a707-7f22-43c5-935b-9ddd4aa29b6e)

[Go back to navigation](#REST-API)

### GraphQL Confirm User Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 76       | 200           | 10s           | 10s           | 14ms  |

![image](https://github.com/user-attachments/assets/e766ae54-f008-45e2-a899-e074187b453d)
![image](https://github.com/user-attachments/assets/c83d1e28-4f43-4e74-b057-5a872da758e9)

[Go back to navigation](#REST-API)

### GraphQL Update User Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 23       | 200           | 10s           | 10s           | 3s    |

![image](https://github.com/user-attachments/assets/ff06fac5-1316-48d6-a10d-3befa6ca56a3)
![image](https://github.com/user-attachments/assets/42824035-e35b-4b61-8fb2-7ef9e9a06b57)

[Go back to navigation](#REST-API)

### GraphQL Delete User Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 47       | 400           | 10s           | 10s           | 11ms  |

![image](https://github.com/user-attachments/assets/8e7603ee-c3a6-4877-88dc-7eb255926d9f)
![image](https://github.com/user-attachments/assets/2f1f84e3-913e-42c4-bf0c-50d4fb389ac8)

[Go back to navigation](#REST-API)

### GraphQL Resend Confirmation Email To User Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 22       | 200           | 10s           | 10s           | 3s    |

![image](https://github.com/user-attachments/assets/7a492b2e-dba5-4b46-8bff-6c81b1453ecb)
![image](https://github.com/user-attachments/assets/ee19a537-72ee-47fa-9aeb-1a3c23da6937)

[Go back to navigation](#REST-API)

Learn more about [Performance and Optimization (frankenphp)](performance-frankenphp.md).
