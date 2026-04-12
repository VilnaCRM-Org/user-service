---
stepsCompleted:
  [
    init,
    context,
    worker-lifecycle,
    reset-strategy,
    test-strategy,
    observability,
    rollout,
    assumptions,
  ]
inputDocuments:
  [
    specs/frankenphp-worker-mode-memory-safety/research.md,
    specs/frankenphp-worker-mode-memory-safety/prd.md,
    Dockerfile,
    infrastructure/docker/php/worker.Caddyfile,
    public/index.php,
    composer.json,
    config/services.yaml,
    config/services_test.yaml,
  ]
workflowType: 'architecture'
project_name: 'VilnaCRM User Service - FrankenPHP Worker Mode Memory Safety'
author: 'Codex'
date: '2026-04-12'
revision: '1 - planning baseline'
---

# Architecture Decision Document - FrankenPHP Worker Mode Memory Safety

## Context and Constraints

User Service is a Symfony 7.4 + API Platform application deployed with FrankenPHP. Production already imports `worker.Caddyfile`, which means the service is not merely "FrankenPHP-compatible"; it is already positioned to run as a long-lived worker process.

That changes the architecture contract:

- the application boots once and can serve many requests
- singleton services can outlive the request that first populated them
- the Doctrine ODM identity map can carry managed documents longer than request-isolated PHP would suggest
- caches, serializers, token helpers, registries, and third-party SDK wrappers must be reviewed for retained state

This design therefore treats worker mode as a first-class constraint, not as a deployment toggle added after implementation.

## ADR-01: Worker Mode Runtime Model

**Decision:** Model FrankenPHP worker mode as a long-running process where the Symfony application container is reused across requests.

### Implications

- Request completion is not sufficient evidence of cleanup.
- Any mutable property on a long-lived service is a potential cross-request retention point.
- Services must not assume that process lifetime equals request lifetime.

### Required runtime behavior

1. Terminate request work cleanly after each handled request.
2. Execute explicit post-request cleanup hooks for stateful services.
3. Recommend `gc_collect_cycles()` in the worker loop after each handled request as a pragmatic cleanup step.
4. Configure a conservative `MAX_REQUESTS` style worker restart fuse so legacy or third-party leaks cannot grow forever.

`MAX_REQUESTS` is a safety fuse. It is not proof that the service is safe, and it must not be used as a reason to skip retained-object fixes.

## ADR-02: Stateful Service Design Rules

**Decision:** Any service that may accumulate per-request or unbounded internal state must either be redesigned to remain stateless or implement `ResetInterface` and clear state in `reset()`.

### Service design rules

- Do not retain unbounded request data in service properties.
- Do not keep `Request`, user, session, token, entity, document, or payload objects on singleton services after the request finishes.
- Do not use static caches without strict bounds and an explicit reset strategy.
- Keep memoization bounded and reset-aware.
- Prefer passing per-request data through method scope instead of storing it on properties.

### Risk categories for this repository

- custom caches and memoizers
- serializer-heavy helpers and normalizers
- Doctrine ODM state holders or wrappers around `DocumentManager`
- security and request-context helpers
- long-lived OAuth or SDK clients that may capture request data
- static registries and factories

### Initial audit targets

- `CachedUserRepository`
- `RedisTokenRepository`
- `RedisOAuthStateRepository`
- `RedisAccountLockoutProvider`
- `DualAuthenticator`
- `CurrentUserIdentityResolver`
- OAuth provider registry and provider wrappers
- API Platform processors and resolvers for user write flows

### Required migration checklist

1. Identify mutable properties.
2. Classify each property as per-request data, bounded cache, or leaked state.
3. Redesign the service or implement `reset()`.
4. Add leak-focused tests that prove the service does not retain targeted objects between requests.

## ADR-03: Symfony Reset Semantics

**Decision:** Use Symfony's service reset mechanism as the primary framework-aligned cleanup path for worker reuse.

### Rules

- Services that can accumulate state between requests must implement `Symfony\Contracts\Service\ResetInterface`.
- The implementation must clear accumulated state in `reset()`.
- Services selected for reset must be wired so Symfony applies `kernel.reset` behavior in long-running contexts and in tests that use `disableReboot()`.

### Important caveat

`disableReboot()` does not simulate a raw process without cleanup. It keeps the kernel alive and resets services tagged for `kernel.reset` instead of fully rebuilding the container. That makes it the correct same-kernel approximation for functional tests, but it also means:

- security token storage behavior can differ from reboot-per-request tests
- Doctrine behavior can differ because the same service instances and persistence infrastructure remain alive
- tests may require explicit test-environment adjustments instead of copy-pasting standard BrowserKit expectations

The design must therefore treat `disableReboot()` as an intentional test mode with setup requirements, not as a drop-in flag.

## ADR-04: Memory-Safety Test Strategy

**Decision:** Use `shipmonk/memory-scanner` as the primary leak-detection package and integrate it into Symfony `KernelTestCase` and `WebTestCase` based tests.

### Primary tooling

- Primary package: `shipmonk/memory-scanner`
- Primary integration: `ObjectDeallocationCheckerKernelTestCaseTrait`
- Primary test styles:
  - `KernelTestCase` for service-level retained-object checks
  - `WebTestCase` for repeated-request same-kernel flows

### Required test scenarios

1. Public simple read endpoint
   - candidate: `GET /api/health-check`
2. Authenticated endpoint
   - candidate: `GET /api/users/{id}`
3. Doctrine ODM-heavy write endpoint
   - candidate: `POST /api/users` and `PATCH /api/users/{id}`
4. Serializer-heavy endpoint
   - candidate: `GET /api/users?page=1&itemsPerPage=50` and GraphQL collection query
5. Error path
   - candidate: invalid sign-in, malformed payload, unauthorized request
6. Endpoint using custom caches or shared services
   - candidate: cached user reads and OAuth state/token flows

### Same-kernel repeated-request design

- Use `disableReboot()` in `WebTestCase` where repeated requests need to exercise the same kernel and service instances.
- Expect service resets for `kernel.reset` services instead of full container rebuild.
- Design the test environment so security token storage and Doctrine ODM behavior remain observable and intentional.
- Do not assume that a passing reboot-per-request BrowserKit test says anything about worker-mode safety.

### Leak acceptance rule

Targeted repeated-request tests should not show unexplained retained objects for the selected flows. Numeric thresholds are intentionally deferred until staging baselines exist.

### Deep-debug path

When CI leak tests are inconclusive or when the retained-object signal points to native allocations, use `arnaud-lb/memprof` as the optional forensic tool in local or staging environments. This is not the primary CI mechanism.

### Explicit non-decision

`roave/no-leaks` is not the primary solution for this migration and should not be framed as the default path in the implementation docs.

## ADR-05: Observability and Soak Verification

**Decision:** Treat worker memory trends and restart behavior as first-class rollout signals.

### Required observability

- measure worker RSS or equivalent process memory trend during repeated requests
- record worker restarts triggered by the configured restart fuse
- distinguish normal warm-up from unbounded growth
- capture rollout-blocking symptoms:
  - unbounded memory growth
  - cross-request state bleed
  - authentication or Doctrine corruption caused by improper resets
  - repeated worker restarts caused by instability

### Baseline rule

The team must establish a staging baseline before declaring memory thresholds. The spec deliberately avoids inventing numbers without evidence.

## ADR-06: Rollout and Risk Mitigation

**Decision:** Use a staged rollout path with conservative worker restart behavior first and tuning later.

### Rollout sequence

1. Spec rewrite
2. Service audit
3. Leak-focused test implementation
4. Staging soak verification
5. Production rollout with conservative `MAX_REQUESTS`
6. Later tuning after evidence

### Rollback path

If leak indicators appear, revert to non-worker mode or a safer configuration while fixes are implemented. Do not keep worker mode enabled solely because `MAX_REQUESTS` masks the symptom temporarily.

## Assumptions / Open Questions

- The runtime integration point for post-request cleanup may live in FrankenPHP worker configuration, Symfony termination behavior, or both; implementation must confirm the exact hook.
- The repository currently uses Doctrine ODM rather than Doctrine ORM for core persistence, but the same long-lived identity-map and managed-object concerns still apply.
- `shipmonk/memory-scanner` compatibility with the exact test stack must be verified before coding begins.
- The implementation should confirm whether GraphQL repeated-request coverage belongs in the first rollout batch or a second wave after REST.
