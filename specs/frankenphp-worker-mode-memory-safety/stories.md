---
stepsCompleted: [init, story-details, task-breakdown, dev-notes]
inputDocuments:
  [
    specs/frankenphp-worker-mode-memory-safety/prd.md,
    specs/frankenphp-worker-mode-memory-safety/architecture.md,
    specs/frankenphp-worker-mode-memory-safety/epic.md,
  ]
workflowType: 'stories'
project_name: 'VilnaCRM User Service - FrankenPHP Worker Mode Memory Safety'
author: 'Codex'
date: '2026-04-12'
revision: '1 - planning baseline'
---

# FrankenPHP Worker Mode Memory Safety - Implementation Stories

# Story 1.1: Audit long-lived services for mutable state

Status: ready-for-dev

## Story

As an engineer preparing worker-mode rollout,
I want an inventory of long-lived services that can retain request state,
so that the migration targets are explicit before runtime changes are made.

## Acceptance Criteria

1. The service container is audited for singleton services with mutable properties.
2. The audit lists state holders that keep arrays, entities, request objects, closures, callbacks, or caches on properties.
3. The first-pass inventory includes cache, security, Doctrine ODM, OAuth, serializer, and event-subscriber categories.
4. Every risky service is classified as per-request state, bounded cache, or leaked state candidate.

## Tasks / Subtasks

- [ ] Audit `CachedUserRepository`, `RedisTokenRepository`, and `RedisOAuthStateRepository`.
- [ ] Audit `DualAuthenticator` and `CurrentUserIdentityResolver`.
- [ ] Audit OAuth provider registry and provider wrappers.
- [ ] Audit API Platform processors and resolvers on user read and write paths.
- [ ] Record findings in an implementation issue or checklist document.

## Dev Notes

- Start with services that hold `DocumentManager`, cache adapters, OAuth clients, or security context.
- Treat static properties and in-memory registries as suspect until proven bounded.

# Story 1.2: Define worker-loop cleanup and restart safeguards

Status: ready-for-dev

## Story

As a platform engineer,
I want the worker cleanup contract and restart fuse documented concretely,
so that runtime behavior does not rely on guesswork.

## Acceptance Criteria

1. The implementation plan specifies post-request cleanup.
2. The plan recommends `gc_collect_cycles()` in the worker loop.
3. The plan defines a conservative `MAX_REQUESTS` style restart fuse for staging and early production.
4. The plan states that `MAX_REQUESTS` is a safety fuse, not a replacement for fixing leaks.

## Tasks / Subtasks

- [ ] Identify the exact worker-loop or post-response integration point in the FrankenPHP deployment.
- [ ] Document the cleanup call order.
- [ ] Define the initial conservative restart policy for staging.
- [ ] Define restart logging or metrics expectations.

## Dev Notes

- Confirm whether the runtime hook lives in FrankenPHP worker configuration, Symfony termination behavior, or both.

# Story 2.1: Introduce ResetInterface where mutable services survive requests

Status: ready-for-dev

## Story

As an engineer,
I want mutable long-lived services to reset their internal state between requests,
so that worker reuse does not leak request context.

## Acceptance Criteria

1. Services that accumulate request-derived state implement `ResetInterface`.
2. `reset()` clears accumulated state instead of partially mutating it.
3. The implementation avoids storing user, session, request, or document objects on singleton services beyond the request.
4. The migration checklist is applied to each targeted service before worker rollout.

## Tasks / Subtasks

- [ ] Convert stateful services to `ResetInterface` where redesign is not enough.
- [ ] Remove request-derived properties from services that can stay stateless.
- [ ] Verify Symfony reset wiring for long-running contexts and tests.
- [ ] Add targeted tests that prove state is released after reset.

## Dev Notes

- Bounded memoization is allowed only when reset-aware and explicitly sized.

# Story 3.1: Add primary PHPUnit leak-detection tooling

Status: ready-for-dev

## Story

As a maintainer,
I want a dedicated PHPUnit leak-detection package integrated into the repository,
so that memory regressions are caught in CI instead of staging or production.

## Acceptance Criteria

1. `shipmonk/memory-scanner` is added as the primary dev dependency.
2. The repository documents or implements shared helpers based on `ObjectDeallocationCheckerKernelTestCaseTrait`.
3. The suite integrates with existing PHPUnit workflows.
4. `roave/no-leaks` is not introduced as the primary migration solution.

## Tasks / Subtasks

- [ ] Verify package compatibility with PHPUnit 10.5 and Symfony 7.4.
- [ ] Add shared base helpers for memory checks.
- [ ] Wire the suite into PHPUnit config and CI.
- [ ] Document `memprof` as the optional forensic tool.

## Dev Notes

- Prefer the smallest integration that works with existing test bases instead of inventing a custom runner.

# Story 3.2: Add same-kernel repeated-request tests

Status: ready-for-dev

## Story

As an engineer,
I want repeated-request tests that reuse the same kernel,
so that the suite approximates FrankenPHP worker behavior.

## Acceptance Criteria

1. `WebTestCase` coverage uses `disableReboot()` for targeted repeated-request flows.
2. The tests explicitly account for service resets applied through `kernel.reset`.
3. The design documents that security token storage and Doctrine ODM behavior may require test-environment adjustments.
4. Tests fail on confirmed retained objects for targeted flows.

## Tasks / Subtasks

- [ ] Create a `WebTestCase` memory suite for repeated requests.
- [ ] Add helper setup for authenticated requests under `disableReboot()`.
- [ ] Adjust the test environment where security token storage or Doctrine ODM state needs deterministic handling.
- [ ] Record known limitations so failures are actionable.

## Dev Notes

- Reboot-per-request BrowserKit tests are not enough for worker-mode verification.

# Story 3.3: Cover the first hot-path flow set

Status: ready-for-dev

## Story

As a maintainer,
I want leak-focused coverage on the highest-risk current flows,
so that the first rollout wave targets real application behavior.

## Acceptance Criteria

1. The suite covers a public simple read endpoint.
2. The suite covers an authenticated cached read endpoint.
3. The suite covers a Doctrine ODM-heavy write endpoint.
4. The suite covers a serializer-heavy response path.
5. The suite covers an error path.
6. The suite covers a shared-cache or OAuth flow.

## Tasks / Subtasks

- [ ] Add a public read test for `GET /api/health-check`.
- [ ] Add an authenticated read test for `GET /api/users/{id}`.
- [ ] Add write-path tests for `POST /api/users` and `PATCH /api/users/{id}`.
- [ ] Add serializer-heavy coverage for `GET /api/users?page=1&itemsPerPage=50` and the GraphQL collection query.
- [ ] Add error-path coverage for unauthorized and malformed requests.
- [ ] Add OAuth or shared-cache coverage for `/oauth/token` or social sign-in state handling.

## Dev Notes

- Favor a small set of representative flows first, then widen after the baseline is understood.

# Story 4.1: Stage the rollout and soak verification

Status: ready-for-dev

## Story

As a release owner,
I want a staged rollout with soak verification and rollback triggers,
so that worker mode is enabled based on evidence instead of confidence alone.

## Acceptance Criteria

1. The rollout plan includes spec rewrite, service audit, leak-test implementation, staging soak, conservative production rollout, and later tuning.
2. Staging soak verifies worker RSS trend and restart visibility.
3. Rollout stops on unbounded memory growth, cross-request state bleed, auth or Doctrine reset corruption, or repeated worker instability.
4. Rollback to non-worker mode or safer configuration is documented.

## Tasks / Subtasks

- [ ] Define the soak script or repeated-request verification procedure.
- [ ] Define the memory and restart signals to watch.
- [ ] Define rollout blockers and rollback instructions.
- [ ] Define when `memprof` escalation is mandatory.

## Dev Notes

- Do not declare numeric pass thresholds until the team has a baseline from staging.
