# Passkey Authentication NFR Catalog Evidence

This file maps passkey authentication to the NonFunctionals.com catalog model.
It records standards, automated evidence, manual evidence, and not-applicable
rationale without fabricating runtime results.

## Performance

Scope: response time, throughput, latency, resource use, concurrency,
scalability, representative load/stress/spike/volume/endurance/baseline
testing, bottleneck analysis, and performance monitoring.

Evidence and standards:

- `tests/Load/scripts/rest-api/passkeySignupOptions.js`,
  `passkeySigninOptions.js`, and `passkeyRegistrationOptions.js` exercise the
  server-side WebAuthn start-ceremony paths under smoke, average, stress, and
  spike profiles.
- `tests/Load/config.json.dist` and `tests/Load/config.prod.json` define p99
  thresholds of `1500ms` for smoke/average, `3000ms` for stress, and `5000ms`
  for spike, with `checks` required above `99%`.
- Completion ceremonies depend on browser-created WebAuthn attestation or
  assertion payloads. They are covered by integration tests and manual browser
  evidence, not by k6, because k6 does not operate a browser authenticator.
- Bottleneck checks focus on the `passkey_challenges.expires_at` TTL index,
  challenge insert/claim paths, `passkey_credentials.credential_id` lookup, and
  WebAuthn serialization/verification cost.
- Production monitoring must track p95/p99 latency, request rate, 4xx/5xx
  rates, and challenge backlog for the six passkey REST operations and matching
  GraphQL mutations.

Current PR smoke evidence, collected on 2026-05-25 UTC against an isolated
local load-test stack (`user-service-passkey-nfr-load`) with the MongoDB 7
override from `var/ai-review/load-test-mongo7.compose.yml`:

- `passkeySignupOptions`: `checks=100%`, p99 `1.17s`, threshold
  `p(99)<1500ms`.
- `passkeySigninOptions`: `checks=100%`, p99 `44.92ms`, threshold
  `p(99)<1500ms`.
- `passkeyRegistrationOptions`: `checks=100%`, p99 `65.03ms`, threshold
  `p(99)<1500ms`.

Suggested repeatable validation targets:

- `make smoke-load-tests`
- `make load-tests`
- `make execute-load-tests-script scenario=passkeySignupOptions`
- `make execute-load-tests-script scenario=passkeySigninOptions`
- `make execute-load-tests-script scenario=passkeyRegistrationOptions`

## Usability

Scope: task success, efficiency, error recovery, learnability, accessibility,
clear feedback, human error tolerance, familiar patterns,
developer/operator usability for APIs, and reduced cognitive load.

Evidence and standards:

- REST and GraphQL contracts expose the same ceremony sequence: options first,
  browser WebAuthn call, completion second.
- `docs/passkey-authentication.md` documents browser JSON parsing with
  `PublicKeyCredential.parseCreationOptionsFromJSON()`,
  `PublicKeyCredential.parseRequestOptionsFromJSON()`, and `credential.toJSON()`.
- Validation, conflict, and invalid-challenge responses use existing API
  problem/error patterns so clients can recover without custom parsing.
- Sign-in options intentionally omit credential descriptors, reducing privacy
  risk and frontend branching.
- Manual browser evidence records successful signup, enrollment, sign-in, 2FA
  parity, replay rejection, and expiry behavior.

## Maintainability

Scope: complexity, technical debt, test coverage, change impact,
documentation accuracy, modularity, loose coupling, naming, DRY, static
analysis, dependency mapping, and build efficiency.

Evidence and standards:

- Domain entities remain framework-free and validation stays in DTO/YAML config.
- WebAuthn library integration is isolated behind application factories,
  validators, and transformers.
- MongoDB mappings live in XML under `config/doctrine`.
- Tests cover command handlers, processors, factories, repositories, OpenAPI
  schema transformation, GraphQL resolver wiring, replay, rollback, 2FA parity,
  and browser-safe JSON.
- Quality gates remain unchanged: Psalm, Psalm security, Deptrac, PHPInsights,
  PHPMD, PHP-CS-Fixer, Infection, OpenAPI, Spectral, Schemathesis, and tests.

## Availability

Scope: uptime/SLO relevance, MTBF/MTTR/RPO where applicable, fault tolerance,
graceful degradation, retry/queue/recovery behavior, health checks, monitoring,
alerting, runbooks, capacity planning, and single-point failure avoidance.

Evidence and standards:

- Passkey authentication shares the authentication API availability target and
  existing health-check surface.
- A failed or expired passkey challenge does not disable password, social OAuth,
  password reset, or TOTP flows.
- Challenge records are ephemeral and retryable; no separate passkey RPO applies
  to challenges. Credential records inherit service MongoDB backup and restore.
- MongoDB TTL cleanup avoids application sweep workers as a single point of
  failure for expired challenges.
- MTTD target is `15m` through passkey endpoint error/latency alerts. MTTR
  target is `30m` for RP/origin misconfiguration, TTL-index drift, or
  passkey-only endpoint incidents.
- Runbook and alert guidance are documented in
  `docs/passkey-authentication.md`.

## Interoperability

Scope: API contracts, standards compliance, data formats,
protocol/auth compatibility, backward compatibility, contract tests, semantic
validation, versioning, adapters/gateways, idempotency, schema governance,
error handling, and integration monitoring.

Evidence and standards:

- The implementation uses `web-auth/webauthn-lib` for W3C WebAuthn
  attestation/assertion verification rather than custom cryptography.
- REST contracts are documented in API Platform YAML and generated OpenAPI.
- GraphQL mutations use the existing `AuthPayload` surface and API Platform's
  `Iterable` scalar for nested browser credential JSON.
- Passkey endpoints are additive and do not break password, OAuth, refresh
  token, sign-out, or TOTP contracts.
- Challenge completion is idempotency-safe through atomic single-use claim and
  replay rejection.

## Security

Scope: confidentiality, integrity, availability, authentication,
authorization, encryption, privacy, accountability, least privilege, input
validation, replay resistance, rate limiting, audit logging, secure defaults,
dependency risk, threat/security review, vulnerability scanning, monitoring,
and incident-response path.

Evidence and standards:

- Challenges are server generated from random bytes, short lived, and claimed
  atomically before credential verification.
- WebAuthn verification enforces relying party, origin, challenge, credential,
  and user-verification requirements.
- Authenticated registration requires `ROLE_USER`; sign-in completion cannot
  authenticate unknown users or users without matching passkey credentials.
- Public passkey endpoints participate in the API rate-limit resolver.
- Passkey GraphQL sign-up mutations are mapped to the same registration
  limiter, and passkey GraphQL sign-in mutations are mapped to the same sign-in
  IP/email limiters, so GraphQL does not bypass the REST endpoint-specific
  abuse controls.
- Sign-in options do not expose credential descriptors or user existence beyond
  the existing username-first sign-in pattern.
- Successful passkey sign-in uses existing session issuance and sign-in event
  publishing; failed attempts use the generic invalid-credential path.
- Dependency and vulnerability checks remain part of CI.

## Manageability

Scope: monitoring coverage, MTTD, config deployment/drift, automation ratio,
alert accuracy, health/metrics endpoints, structured logs, correlation
identifiers, centralized logging, feature flags/configuration, IaC/GitOps fit,
tracing, golden signals, runbooks, capacity forecasting, and user-centric SLOs.

Evidence and standards:

- `PASSKEY_RP_ID`, `PASSKEY_RP_NAME`, `PASSKEY_ALLOWED_ORIGINS`,
  `PASSKEY_TIMEOUT_SECONDS`, and `PASSKEY_CHALLENGE_TTL_SECONDS` are explicit
  environment configuration.
- `EndpointInvocations` EMF metrics are emitted for passkey API operations
  through `ApiEndpointBusinessMetricsSubscriber`.
- Platform/AppRunner metrics must track latency, traffic, errors, and
  saturation for passkey routes; centralized logs must retain request metadata
  and status codes for the same operations.
- Operators must monitor expired challenge backlog and total active challenge
  count against `observed_options_rps * PASSKEY_CHALLENGE_TTL_SECONDS`.
- Alert thresholds, runbook steps, and operational evidence collection are
  documented in `docs/passkey-authentication.md`.
- Passkey-specific tracing or correlation identifiers are not introduced by
  this feature; they inherit the service-wide logging/tracing configuration.

## Automatability

Scope: automation coverage, documented stable APIs, integration density,
deterministic/headless execution, CI/CD/IaC fit, task execution stability, audit
logs, human-in-the-loop controls for risky automation, and immutable
automated-decision evidence.

Evidence and standards:

- Unit, integration, Behat/API access, OpenAPI/Spectral/Schemathesis, mutation,
  and K6 option-ceremony checks are repeatable through make targets.
- Load scripts use stable public APIs and deterministic scenario selection.
- Browser completion remains human/browser controlled because the authenticator
  is the security boundary; sanitized transcript files provide immutable manual
  evidence for that non-headless step.
- CI and GitHub gate evidence is live current-head evidence, not static source
  documentation. The BMAD gate must verify `gh auth status`, PR #286
  `reviewDecision`, unresolved review threads, branch protection visibility, and
  current-head check rollup immediately before posting a final result. The
  ignored evidence file `var/ai-review/current-head-evidence.md` is used for the
  exact timestamped command output.

## Dependability

Scope: availability, reliability, safety, integrity, maintainability,
correctness, data integrity, consistency, safe failure, rollback/compensation,
idempotency/replay protection, truth/drift observability,
regression/mutation/edge-case tests, and trustworthy evidence.

Evidence and standards:

- Atomic challenge claim prevents replay races.
- Expired, reused, unknown, or mismatched challenges fail safely without token
  issuance.
- Signup rollback removes persisted user/credential state on downstream
  session issuance failures.
- Duplicate credential IDs are rejected.
- Credential counter/record updates occur after successful assertion
  verification.
- Manual browser evidence and automated tests are linked from
  `specs/passkey-authentication/run-summary.md`.
