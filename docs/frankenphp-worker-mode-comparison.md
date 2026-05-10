# FrankenPHP Worker Mode Vs Non-Worker Mode

This report compares the PR 275 runtime in two local benchmark modes:

- **Worker mode enabled**: `FRANKENPHP_CONFIG="import worker.Caddyfile"`
- **Worker mode disabled**: `FRANKENPHP_CONFIG=""`

## Methodology

- Environment: PR 275 worktree on the local Docker-based load-test stack.
- Benchmark profile: fixed VUs, `10` concurrent virtual users, `30s` duration.
- Warmup: each stack was warmed before the benchmark sweep began.
- Measurement columns use `RPS | avg / p95 / p99` latency in milliseconds.
- Latency values prefer k6's `expected_response:true` submetric when available, so teardown traffic does not skew endpoint timing.
- Scenarios cover every endpoint and operation configured in `tests/Load/config.json.dist`.

## Summary

- Worker mode improved throughput on 45 of 50 measured scenarios.
- The median throughput improvement was **5.63x**, and the median average-latency reduction was **6.33x**.
- The largest throughput win was **GET /api/connect/{provider}** at **44.50x** more RPS.
- The largest average-latency win was **GET /api/connect/{provider}** at **45.78x** lower average latency.

### Biggest Throughput Wins

- GET /api/connect/{provider}: 1445.32 RPS with worker mode vs 32.48 RPS without it (44.50x).
- POST /api/users/{id}/resend-confirmation-email: 471.31 RPS with worker mode vs 17.17 RPS without it (27.46x).
- GET /api/oauth/authorize: 1790.93 RPS with worker mode vs 111.27 RPS without it (16.10x).
- GET /api/errors/400: 1749.06 RPS with worker mode vs 115.64 RPS without it (15.13x).
- GET /api/.well-known/context/User: 1634.79 RPS with worker mode vs 111.03 RPS without it (14.72x).

### Biggest Latency Wins

- GET /api/connect/{provider}: 6.70 ms average with worker mode vs 306.83 ms without it (45.78x lower).
- POST /api/users/{id}/resend-confirmation-email: 20.66 ms average with worker mode vs 637.19 ms without it (30.85x lower).
- GET /api/oauth/authorize: 5.38 ms average with worker mode vs 89.51 ms without it (16.65x lower).
- GET /api/errors/400: 5.50 ms average with worker mode vs 86.10 ms without it (15.64x lower).
- GET /api/.well-known/context/User: 5.90 ms average with worker mode vs 89.69 ms without it (15.20x lower).

## Public REST

- **GET /api/.well-known/context/User** (`apiContextUser`): worker mode enabled delivered 1634.79 RPS, avg 5.90 ms, p95 11.44 ms, p99 17.99 ms. Worker mode disabled delivered 111.03 RPS, avg 89.69 ms, p95 127.06 ms, p99 154.46 ms. That is 14.72x more RPS with 15.20x lower average latency.
- **GET /api/docs** (`apiDocs`): worker mode enabled delivered 1461.65 RPS, avg 6.63 ms, p95 11.95 ms, p99 17.38 ms. Worker mode disabled delivered 112.54 RPS, avg 88.49 ms, p95 118.59 ms, p99 143.51 ms. That is 12.99x more RPS with 13.35x lower average latency.
- **GET /api** (`apiEntrypoint`): worker mode enabled delivered 1673.95 RPS, avg 5.76 ms, p95 10.54 ms, p99 16.06 ms. Worker mode disabled delivered 117.38 RPS, avg 84.84 ms, p95 109.79 ms, p99 129.43 ms. That is 14.26x more RPS with 14.73x lower average latency.
- **GET /api/errors/400** (`apiErrors400`): worker mode enabled delivered 1749.06 RPS, avg 5.50 ms, p95 10.16 ms, p99 15.06 ms. Worker mode disabled delivered 115.64 RPS, avg 86.10 ms, p95 111.60 ms, p99 134.43 ms. That is 15.13x more RPS with 15.64x lower average latency.
- **GET /api/validation_errors/400** (`apiValidationErrors`): worker mode enabled delivered 1735.42 RPS, avg 5.55 ms, p95 10.23 ms, p99 15.64 ms. Worker mode disabled delivered 118.30 RPS, avg 84.15 ms, p95 109.49 ms, p99 134.30 ms. That is 14.67x more RPS with 15.16x lower average latency.
- **GET /api/.well-known/genid/{id}** (`apiWellKnownGenid`): worker mode enabled delivered 1124.38 RPS, avg 8.66 ms, p95 16.04 ms, p99 23.69 ms. Worker mode disabled delivered 183.08 RPS, avg 54.30 ms, p95 90.47 ms, p99 115.17 ms. That is 6.14x more RPS with 6.27x lower average latency.
- **GET /api/health** (`health`): worker mode enabled delivered 338.94 RPS, avg 29.28 ms, p95 41.11 ms, p99 53.54 ms. Worker mode disabled delivered 90.76 RPS, avg 109.70 ms, p95 164.31 ms, p99 209.96 ms. That is 3.73x more RPS with 3.75x lower average latency.

## REST User Read And Write

- **GET /api/users/{id} cached repository read** (`cachePerformance`): worker mode enabled delivered 612.83 RPS, avg 15.34 ms, p95 25.26 ms, p99 35.72 ms. Worker mode disabled delivered 93.33 RPS, avg 99.53 ms, p95 133.91 ms, p99 159.73 ms. That is 6.57x more RPS with 6.49x lower average latency.
- **PATCH /api/users/confirm** (`confirmUser`): worker mode enabled delivered 118.54 RPS, avg 25.75 ms, p95 77.38 ms, p99 114.05 ms. Worker mode disabled delivered 55.92 RPS, avg 176.17 ms, p95 410.05 ms, p99 468.15 ms. That is 2.12x more RPS with 6.84x lower average latency.
- **POST /api/users** (`createUser`): worker mode enabled delivered 64.96 RPS, avg 150.51 ms, p95 237.93 ms, p99 311.99 ms. Worker mode disabled delivered 25.01 RPS, avg 397.24 ms, p95 511.02 ms, p99 573.65 ms. That is 2.60x more RPS with 2.64x lower average latency.
- **POST /api/users/batch** (`createUserBatch`): worker mode enabled delivered 6.91 RPS, avg 1329.85 ms, p95 1592.79 ms, p99 1749.59 ms. Worker mode disabled delivered 7.86 RPS, avg 1255.23 ms, p95 1493.43 ms, p99 1601.57 ms. That is 1.14x fewer RPS with 1.06x higher average latency.
- **DELETE /api/users/{id}** (`deleteUser`): worker mode enabled delivered 91.60 RPS, avg 34.34 ms, p95 116.12 ms, p99 171.05 ms. Worker mode disabled delivered 27.56 RPS, avg 350.66 ms, p95 427.33 ms, p99 474.85 ms. That is 3.32x more RPS with 10.21x lower average latency.
- **GET /api/users/{id}** (`getUser`): worker mode enabled delivered 404.51 RPS, avg 24.06 ms, p95 42.02 ms, p99 87.20 ms. Worker mode disabled delivered 95.47 RPS, avg 103.39 ms, p95 139.06 ms, p99 192.82 ms. That is 4.24x more RPS with 4.30x lower average latency.
- **GET /api/users** (`getUsers`): worker mode enabled delivered 19.79 RPS, avg 494.06 ms, p95 637.43 ms, p99 720.99 ms. Worker mode disabled delivered 20.43 RPS, avg 484.56 ms, p95 622.12 ms, p99 684.93 ms. That is 1.03x fewer RPS with 1.02x higher average latency.
- **PUT /api/users/{id}** (`replaceUser`): worker mode enabled delivered 79.10 RPS, avg 123.10 ms, p95 207.86 ms, p99 243.34 ms. Worker mode disabled delivered 19.06 RPS, avg 513.17 ms, p95 680.78 ms, p99 751.81 ms. That is 4.15x more RPS with 4.17x lower average latency.
- **POST /api/users/{id}/resend-confirmation-email** (`resendEmailToUser`): worker mode enabled delivered 471.31 RPS, avg 20.66 ms, p95 33.34 ms, p99 60.66 ms. Worker mode disabled delivered 17.17 RPS, avg 637.19 ms, p95 855.31 ms, p99 892.24 ms. That is 27.46x more RPS with 30.85x lower average latency.
- **PATCH /api/users/{id}** (`updateUser`): worker mode enabled delivered 80.60 RPS, avg 121.20 ms, p95 206.45 ms, p99 248.19 ms. Worker mode disabled delivered 17.66 RPS, avg 552.57 ms, p95 931.84 ms, p99 1219.21 ms. That is 4.57x more RPS with 4.56x lower average latency.

## REST Authentication And Recovery

- **POST /api/confirm-two-factor** (`confirmTwoFactor`): worker mode enabled delivered 3.52 RPS, avg 2647.13 ms, p95 9203.30 ms, p99 10094.75 ms. Worker mode disabled delivered 3.06 RPS, avg 3118.06 ms, p95 9355.36 ms, p99 9643.63 ms. That is 1.15x more RPS with 1.18x lower average latency.
- **POST /api/disable-two-factor** (`disableTwoFactor`): worker mode enabled delivered 3.21 RPS, avg 2640.88 ms, p95 10901.00 ms, p99 11897.45 ms. Worker mode disabled delivered 3.89 RPS, avg 2361.50 ms, p95 8487.46 ms, p99 8957.92 ms. That is 1.21x fewer RPS with 1.12x higher average latency.
- **POST /api/oauth/token** (`oauth`): worker mode enabled delivered 232.30 RPS, avg 42.71 ms, p95 59.47 ms, p99 84.00 ms. Worker mode disabled delivered 33.37 RPS, avg 298.32 ms, p95 358.41 ms, p99 404.15 ms. That is 6.96x more RPS with 6.99x lower average latency.
- **GET /api/oauth/authorize** (`oauthAuthorize`): worker mode enabled delivered 1790.93 RPS, avg 5.38 ms, p95 9.66 ms, p99 14.52 ms. Worker mode disabled delivered 111.27 RPS, avg 89.51 ms, p95 121.38 ms, p99 149.07 ms. That is 16.10x more RPS with 16.65x lower average latency.
- **GET /api/connect/{provider}/check** (`oauthSocialCallback`): worker mode enabled delivered 329.36 RPS, avg 29.95 ms, p95 70.38 ms, p99 96.89 ms. Worker mode disabled delivered 25.78 RPS, avg 386.41 ms, p95 502.86 ms, p99 621.13 ms. That is 12.77x more RPS with 12.90x lower average latency.
- **GET /api/connect/{provider}** (`oauthSocialInitiate`): worker mode enabled delivered 1445.32 RPS, avg 6.70 ms, p95 11.27 ms, p99 16.25 ms. Worker mode disabled delivered 32.48 RPS, avg 306.83 ms, p95 399.53 ms, p99 505.98 ms. That is 44.50x more RPS with 45.78x lower average latency.
- **POST /api/refresh-token** (`refreshToken`): worker mode enabled delivered 179.71 RPS, avg 54.60 ms, p95 84.45 ms, p99 120.15 ms. Worker mode disabled delivered 18.60 RPS, avg 528.07 ms, p95 837.30 ms, p99 953.30 ms. That is 9.66x more RPS with 9.67x lower average latency.
- **POST /api/regenerate-recovery-codes** (`regenerateRecoveryCodes`): worker mode enabled delivered 2.32 RPS, avg 3847.27 ms, p95 9804.16 ms, p99 10424.28 ms. Worker mode disabled delivered 1.84 RPS, avg 5194.10 ms, p95 12175.09 ms, p99 13145.20 ms. That is 1.26x more RPS with 1.35x lower average latency.
- **POST /api/reset-password** (`resetPassword`): worker mode enabled delivered 110.53 RPS, avg 89.66 ms, p95 149.14 ms, p99 201.21 ms. Worker mode disabled delivered 20.97 RPS, avg 472.07 ms, p95 635.22 ms, p99 753.40 ms. That is 5.27x more RPS with 5.26x lower average latency.
- **POST /api/reset-password/confirm** (`resetPasswordConfirm`): worker mode enabled delivered 32.85 RPS, avg 58.22 ms, p95 137.08 ms, p99 164.39 ms. Worker mode disabled delivered 10.82 RPS, avg 135.61 ms, p95 456.73 ms, p99 628.67 ms. That is 3.04x more RPS with 2.33x lower average latency.
- **POST /api/setup-two-factor** (`setupTwoFactor`): worker mode enabled delivered 280.15 RPS, avg 34.85 ms, p95 68.34 ms, p99 130.07 ms. Worker mode disabled delivered 21.51 RPS, avg 457.89 ms, p95 704.47 ms, p99 859.55 ms. That is 13.02x more RPS with 13.14x lower average latency.
- **POST /api/signin** (`signin`): worker mode enabled delivered 191.17 RPS, avg 51.55 ms, p95 78.73 ms, p99 136.66 ms. Worker mode disabled delivered 22.85 RPS, avg 433.64 ms, p95 550.97 ms, p99 636.43 ms. That is 8.36x more RPS with 8.41x lower average latency.
- **POST /api/signin/two-factor** (`signinTwoFactor`): worker mode enabled delivered 4.59 RPS, avg 2002.02 ms, p95 8963.90 ms, p99 9884.31 ms. Worker mode disabled delivered 3.48 RPS, avg 2624.17 ms, p95 10390.95 ms, p99 11509.59 ms. That is 1.32x more RPS with 1.31x lower average latency.
- **POST /api/signout** (`signout`): worker mode enabled delivered 286.19 RPS, avg 33.94 ms, p95 67.71 ms, p99 140.54 ms. Worker mode disabled delivered 24.58 RPS, avg 398.69 ms, p95 570.18 ms, p99 683.18 ms. That is 11.64x more RPS with 11.75x lower average latency.
- **POST /api/signout-all** (`signoutAll`): worker mode enabled delivered 167.71 RPS, avg 57.65 ms, p95 137.14 ms, p99 205.05 ms. Worker mode disabled delivered 22.17 RPS, avg 439.75 ms, p95 590.36 ms, p99 673.78 ms. That is 7.56x more RPS with 7.63x lower average latency.

## GraphQL User Read And Write

- **GraphQL confirmUser mutation** (`graphQLConfirmUser`): worker mode enabled delivered 324.28 RPS, avg 29.92 ms, p95 80.70 ms, p99 152.00 ms. Worker mode disabled delivered 47.34 RPS, avg 205.93 ms, p95 506.87 ms, p99 597.02 ms. That is 6.85x more RPS with 6.88x lower average latency.
- **GraphQL createUser mutation** (`graphQLCreateUser`): worker mode enabled delivered 67.64 RPS, avg 145.46 ms, p95 311.26 ms, p99 417.76 ms. Worker mode disabled delivered 22.56 RPS, avg 437.89 ms, p95 634.33 ms, p99 769.53 ms. That is 3.00x more RPS with 3.01x lower average latency.
- **GraphQL deleteUser mutation** (`graphQLDeleteUser`): worker mode enabled delivered 194.47 RPS, avg 49.33 ms, p95 85.13 ms, p99 155.73 ms. Worker mode disabled delivered 25.58 RPS, avg 377.32 ms, p95 466.87 ms, p99 573.83 ms. That is 7.60x more RPS with 7.65x lower average latency.
- **GraphQL user query** (`graphQLGetUser`): worker mode enabled delivered 243.84 RPS, avg 40.32 ms, p95 63.24 ms, p99 108.42 ms. Worker mode disabled delivered 77.47 RPS, avg 127.78 ms, p95 183.46 ms, p99 231.17 ms. That is 3.15x more RPS with 3.17x lower average latency.
- **GraphQL users query** (`graphQLGetUsers`): worker mode enabled delivered 44.39 RPS, avg 222.47 ms, p95 319.41 ms, p99 405.88 ms. Worker mode disabled delivered 11.09 RPS, avg 889.17 ms, p95 1080.13 ms, p99 1234.49 ms. That is 4.00x more RPS with 4.00x lower average latency.
- **GraphQL resendEmailToUser mutation** (`graphQLResendEmailToUser`): worker mode enabled delivered 235.58 RPS, avg 41.77 ms, p95 68.61 ms, p99 111.58 ms. Worker mode disabled delivered 30.23 RPS, avg 327.58 ms, p95 421.18 ms, p99 515.74 ms. That is 7.79x more RPS with 7.84x lower average latency.
- **GraphQL updateUser mutation** (`graphQLUpdateUser`): worker mode enabled delivered 74.85 RPS, avg 130.58 ms, p95 209.19 ms, p99 248.76 ms. Worker mode disabled delivered 21.43 RPS, avg 455.74 ms, p95 581.93 ms, p99 684.03 ms. That is 3.49x more RPS with 3.49x lower average latency.

## GraphQL Authentication And Recovery

- **GraphQL completeTwoFactor mutation** (`graphQLCompleteTwoFactor`): worker mode enabled delivered 2.88 RPS, avg 2963.33 ms, p95 12528.11 ms, p99 14098.42 ms. Worker mode disabled delivered 4.16 RPS, avg 2345.84 ms, p95 8723.22 ms, p99 9429.59 ms. That is 1.44x fewer RPS with 1.26x higher average latency.
- **GraphQL confirmPasswordReset mutation** (`graphQLConfirmPasswordReset`): worker mode enabled delivered 22.35 RPS, avg 81.18 ms, p95 263.84 ms, p99 370.38 ms. Worker mode disabled delivered 13.88 RPS, avg 152.60 ms, p95 482.27 ms, p99 556.08 ms. That is 1.61x more RPS with 1.88x lower average latency.
- **GraphQL confirmTwoFactor mutation** (`graphQLConfirmTwoFactor`): worker mode enabled delivered 2.10 RPS, avg 4270.09 ms, p95 16762.36 ms, p99 17660.09 ms. Worker mode disabled delivered 3.32 RPS, avg 2871.60 ms, p95 8430.98 ms, p99 8899.73 ms. That is 1.58x fewer RPS with 1.49x higher average latency.
- **GraphQL disableTwoFactor mutation** (`graphQLDisableTwoFactor`): worker mode enabled delivered 4.50 RPS, avg 2132.18 ms, p95 8249.20 ms, p99 8806.49 ms. Worker mode disabled delivered 3.79 RPS, avg 2463.13 ms, p95 8790.86 ms, p99 9570.20 ms. That is 1.19x more RPS with 1.16x lower average latency.
- **GraphQL refreshToken mutation** (`graphQLRefreshToken`): worker mode enabled delivered 147.18 RPS, avg 66.55 ms, p95 98.87 ms, p99 138.80 ms. Worker mode disabled delivered 22.96 RPS, avg 425.98 ms, p95 538.65 ms, p99 600.32 ms. That is 6.41x more RPS with 6.40x lower average latency.
- **GraphQL regenerateRecoveryCodes mutation** (`graphQLRegenerateRecoveryCodes`): worker mode enabled delivered 2.61 RPS, avg 3449.28 ms, p95 8171.56 ms, p99 8709.38 ms. Worker mode disabled delivered 2.28 RPS, avg 4143.99 ms, p95 8917.54 ms, p99 9280.92 ms. That is 1.15x more RPS with 1.20x lower average latency.
- **GraphQL requestPasswordReset mutation** (`graphQLRequestPasswordReset`): worker mode enabled delivered 97.11 RPS, avg 102.03 ms, p95 161.71 ms, p99 203.20 ms. Worker mode disabled delivered 23.87 RPS, avg 415.99 ms, p95 544.46 ms, p99 622.14 ms. That is 4.07x more RPS with 4.08x lower average latency.
- **GraphQL setupTwoFactor mutation** (`graphQLSetupTwoFactor`): worker mode enabled delivered 174.65 RPS, avg 56.33 ms, p95 92.63 ms, p99 153.65 ms. Worker mode disabled delivered 24.28 RPS, avg 406.90 ms, p95 504.94 ms, p99 560.27 ms. That is 7.19x more RPS with 7.22x lower average latency.
- **GraphQL signin mutation** (`graphQLSignin`): worker mode enabled delivered 137.98 RPS, avg 71.54 ms, p95 110.90 ms, p99 159.74 ms. Worker mode disabled delivered 23.06 RPS, avg 430.80 ms, p95 568.41 ms, p99 657.40 ms. That is 5.98x more RPS with 6.02x lower average latency.
- **GraphQL signout mutation** (`graphQLSignout`): worker mode enabled delivered 163.90 RPS, avg 59.51 ms, p95 101.44 ms, p99 169.87 ms. Worker mode disabled delivered 23.05 RPS, avg 424.78 ms, p95 567.13 ms, p99 651.25 ms. That is 7.11x more RPS with 7.14x lower average latency.
- **GraphQL signoutAll mutation** (`graphQLSignoutAll`): worker mode enabled delivered 163.05 RPS, avg 59.21 ms, p95 98.35 ms, p99 163.29 ms. Worker mode disabled delivered 23.05 RPS, avg 425.35 ms, p95 569.35 ms, p99 622.52 ms. That is 7.07x more RPS with 7.18x lower average latency.

## Notes

- These are local-machine numbers. They are useful for relative comparison between worker mode states, not for claiming production capacity.
- Stateful scenarios use fresh seeded users per scenario so token invalidation, confirmation, signout, and deletion flows remain valid under load.
- `oauthSocialCallback` remained slightly unstable with worker mode enabled on the final isolated rerun, where 4 callback assertions failed out of 9,916 checks while still staying above the scenario's `>99%` success threshold. The worker-off rerun for the same endpoint completed cleanly.
