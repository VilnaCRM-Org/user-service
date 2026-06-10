# Stories — 2FA brute-force + TOTP replay hardening (#314)

Verification commands (one-off containers; never `make start`):

```bash
# Unit (focused). Replace ${PROJECT_ROOT} / ${VENDOR_DIR} with your local paths.
docker run --rm -v ${PROJECT_ROOT}:/app \
  -v ${VENDOR_DIR}:/app/vendor:ro -w /app -e APP_ENV=test \
  --entrypoint sh secfix-312-php:latest -lc \
  'php -d memory_limit=-1 vendor/bin/phpunit --testsuite=Unit --filter "TwoFactor|TOTP" --no-coverage'

# Deptrac / Psalm / CS-Fixer — see PR description for full invocations.
```

## Story 1 — Server-side 2FA brute-force counter (FR-1, FR-2, FR-3)

Add a `failedAttempts` counter to `PendingTwoFactor` and enforce it in
`CompleteTwoFactorCommandHandler`, invalidating the pending session after
`MAX_FAILED_ATTEMPTS`.

Files: `src/User/Domain/Entity/PendingTwoFactor.php`,
`src/User/Application/CommandHandler/CompleteTwoFactorCommandHandler.php`,
`config/doctrine/User/PendingTwoFactor.mongodb.xml`.

Test mapping:

- Positive: `PendingTwoFactorTest::testRecordFailedAttemptIncrementsCounter`,
  `::testNewSessionHasNoFailedAttempts`.
- Negative/edge: `PendingTwoFactorTest::testHasExhaustedAttemptsAtMaxFailedAttempts`,
  `::testSetFailedAttemptsClampsNegativeValuesToZero`.
- Handler positive: `CompleteTwoFactorCommandHandlerTest::testInvokeIncrementsAndPersistsFailedAttemptOnInvalidCode`.
- Handler negative: `::testInvokeInvalidatesPendingSessionAfterMaxFailedAttempts`.
- Handler edge: `::testInvokeRejectsPendingSessionThatAlreadyExhaustedAttempts`.

## Story 2 — TOTP replay protection (FR-4, FR-5)

Track the last accepted TOTP time-step on `User`; reject replays and advance the
counter atomically in `TwoFactorCodeValidator`. Remove the future window from
`TOTPValidator` and expose the matched time-step.

Files: `src/User/Domain/Entity/User.php`,
`src/User/Application/Validator/TwoFactorCodeValidator.php`,
`src/User/Application/Validator/TOTPValidatorInterface.php`,
`src/User/Infrastructure/Validator/TOTPValidator.php`,
`config/doctrine/User/User.mongodb.xml`.

Test mapping:

- Positive: `UserTest::testRecordAcceptedTotpTimestepStoresLatestCounter`,
  `TOTPValidatorTest::testResolveAcceptedTimestepReturnsCurrentWindowTimestep`,
  `::testResolveAcceptedTimestepReturnsPreviousWindowTimestep`,
  `TwoFactorCodeVerifierTest::testVerifyTotpOrFailAdvancesAndPersistsAcceptedTimestep`.
- Negative: `UserTest::testIsTotpTimestepReplayRejectsCurrentAndOlderTimesteps`,
  `TwoFactorCodeVerifierTest::testVerifyAndConsumeOrFailRejectsReplayedTotpCode`,
  `::testVerifyAndResolveMethodReturnsNullForReplayedTotpCode`,
  `TOTPValidatorTest::testResolveAcceptedTimestepReturnsNullForFutureWindow`.
- Edge: `UserTest::testTotpTimestepIsNotReplayWhenNoneAccepted`,
  `::testDisableTwoFactor` (clears stored time-step),
  `TOTPValidatorTest::testVerifyAcceptsPreviousWindowAtUnixEpochBoundary`,
  `::testVerifyAcceptsCurrentAndPreviousTimeWindowsOnly` (future window rejected).

## Story 3 — GraphQL rate-limiter parity for completeTwoFactor (FR-6)

Apply the `twofa_verification_ip` / `twofa_verification_user` limiters to the
GraphQL `completeTwoFactor` mutation at `/api/graphql`; resolve the
`pendingSessionId` from nested GraphQL `variables.input`.

Files: `src/Shared/Application/Resolver/RateLimit/ApiRateLimitAuthTargetResolver.php`,
`src/Shared/Application/Resolver/RateLimit/ApiRateLimitClientIdentityResolver.php`,
`src/Shared/Application/Resolver/RateLimit/ApiRateLimitPayloadValueResolver.php`.

Test mapping:

- Positive: `ApiRateLimitAuthTargetResolverTest::testResolveAppliesTwoFactorIpLimiterToGraphQlCompleteTwoFactor`,
  `::testResolveAppliesBothLimitersToGraphQlCompleteTwoFactor`,
  `ApiRateLimitPayloadValueResolverTest::testResolveNestedReturnsValueFromNestedPath`.
- Negative: `ApiRateLimitAuthTargetResolverTest::testResolveIgnoresUnrelatedGraphQlMutation`,
  `ApiRateLimitPayloadValueResolverTest::testResolveNestedReturnsNullWhenPathSegmentMissing`,
  `::testResolveNestedReturnsNullWhenSegmentIsNotArray`.
- Edge: `ApiRateLimitPayloadValueResolverTest::testResolveNestedReturnsNullForMalformedJson`.
