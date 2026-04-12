---
stepsCompleted:
  [
    init,
    executive-summary,
    success-criteria,
    scope,
    journeys,
    functional-requirements,
    non-functional-requirements,
    assumptions,
  ]
inputDocuments:
  [
    specs/frankenphp-worker-mode-memory-safety/research.md,
    specs/frankenphp-worker-mode-memory-safety/product-brief.md,
    Dockerfile,
    infrastructure/docker/php/worker.Caddyfile,
    public/index.php,
    composer.json,
    config/api_platform/resources/User.yaml,
  ]
workflowType: 'prd'
project_name: 'VilnaCRM User Service - FrankenPHP Worker Mode Memory Safety'
author: 'Codex'
date: '2026-04-12'
revision: '1 - planning baseline'
---

# Product Requirements Document - FrankenPHP Worker Mode Memory Safety

## Executive Summary

User Service wants the performance benefits of FrankenPHP worker mode while preserving Symfony correctness. Because FrankenPHP worker mode keeps the application booted in memory across requests, the service must be designed, tested, and operated as a long-running process. The primary risk is not startup failure; it is retained request state, gradually increasing memory usage, and hard-to-debug contamination between requests.

This PRD defines the planning requirements for a safe migration track. It focuses on explicit cleanup expectations, `ResetInterface` service hygiene, repeated-request leak detection in PHPUnit, and staged rollout guardrails. This PR is planning-only and intentionally does not implement the runtime or test changes yet.

## Success Criteria

| ID    | Criterion                                  | Measurement                                                                      | Target |
| ----- | ------------------------------------------ | -------------------------------------------------------------------------------- | ------ |
| SC-01 | Worker constraints are explicit            | PRD, architecture, epics, and stories describe long-running worker behavior      | Pass   |
| SC-02 | Risky services are auditable               | A concrete audit inventory and checklist exist for mutable services              | Pass   |
| SC-03 | Leak-test strategy is implementation-ready | Dedicated PHPUnit approach, packages, and target flows are defined               | Pass   |
| SC-04 | CI gate is defined                         | Targeted retained-object leaks are CI-blocking for the migration track           | Pass   |
| SC-05 | Rollout guardrails are explicit            | Staging soak, rollback, `MAX_REQUESTS`, and restart observability are documented | Pass   |
| SC-06 | Baseline measurement is acknowledged       | Specs require a baseline before numeric thresholds are adopted                   | Pass   |

## Goals

- Safely adopt FrankenPHP worker mode for the Symfony application.
- Prevent request-to-request state contamination.
- Detect memory leaks early in CI and staging.
- Define operational safeguards for imperfect legacy code and third-party libraries.
- Keep the design compatible with Symfony testing and existing delivery workflows.

## Non-Goals

- Guarantee perfect zero-growth memory behavior for every dependency.
- Rewrite all application services before rollout begins.
- Depend on `MAX_REQUESTS` alone as the leak-management strategy.
- Replace repeatable leak testing with only manual profiling.

## Product Scope

### MVP for this planning track

- Define the long-running worker problem statement.
- Define service-audit rules for mutable singleton state.
- Define post-request cleanup expectations, including `gc_collect_cycles()` in the worker loop.
- Define a conservative `MAX_REQUESTS` worker restart fuse.
- Define the primary memory-safety test package and Symfony/PHPUnit integration pattern.
- Define targeted flows for repeated-request leak testing.
- Define CI, staging soak, rollout, rollback, and risk gates.

### Growth after planning

- Broaden coverage from initial hot paths to the rest of the REST and GraphQL surface.
- Add deeper forensic workflows with `memprof` when baseline leak tests are inconclusive.
- Tune `MAX_REQUESTS` after evidence, rather than choosing a permanent conservative value by instinct.

## Operational Journeys

### OJ-01: Safe worker-mode implementation preparation

1. Engineer audits long-lived services for mutable state.
2. Engineer classifies properties as per-request, bounded cache, or leaked state.
3. Engineer redesigns services or implements `ResetInterface`.
4. Engineer adds leak-focused tests for targeted flows.

### OJ-02: CI leak detection

1. CI runs targeted memory-safety tests in PHPUnit.
2. The suite uses retained-object detection to validate repeated requests.
3. CI fails when targeted flows retain unexplained objects or violate explicit expectations.

### OJ-03: Staging soak verification

1. Staging runs repeated requests through the worker-mode deployment.
2. Team observes worker RSS trends and restart behavior.
3. Rollout stops if memory growth is unbounded or if state bleeds between requests.

### OJ-04: Production safety fuse

1. Production starts with conservative `MAX_REQUESTS`.
2. Workers restart before long-lived defects can accumulate indefinitely.
3. The team tunes restart values only after evidence shows stable behavior.

## Functional Requirements

| ID    | Requirement                                                                                                                                                                  | Priority |
| ----- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------- |
| FR-01 | The specs must state that FrankenPHP worker mode keeps the application booted across requests and therefore changes the runtime safety model.                                | P0       |
| FR-02 | The specs must require explicit post-request cleanup and recommend `gc_collect_cycles()` in the worker loop.                                                                 | P0       |
| FR-03 | The specs must require a `MAX_REQUESTS` style worker restart fuse as an operational safeguard.                                                                               | P0       |
| FR-04 | The specs must require `ResetInterface` for services that accumulate request-derived or mutable state between requests.                                                      | P0       |
| FR-05 | The specs must require an audit of singleton services that store arrays, entities, request objects, closures, callbacks, or caches on properties.                            | P0       |
| FR-06 | The specs must define a service-migration checklist: identify state, classify it, redesign or reset it, then add leak-focused tests.                                         | P0       |
| FR-07 | The specs must define `shipmonk/memory-scanner` as the primary dev dependency for leak detection.                                                                            | P0       |
| FR-08 | The specs must define `KernelTestCase` and `WebTestCase` memory checks using `ObjectDeallocationCheckerKernelTestCaseTrait` where applicable.                                | P0       |
| FR-09 | The specs must define `disableReboot()` repeated-request tests for selected flows and document the associated Symfony caveats.                                               | P0       |
| FR-10 | The specs must define targeted flows covering public read, authenticated read, Doctrine-heavy write, serializer-heavy response, error path, and shared-cache or OAuth flows. | P0       |
| FR-11 | The specs must define CI failure behavior for confirmed retained-object leaks in targeted tests.                                                                             | P0       |
| FR-12 | The specs must document `memprof` as an optional deep-debug path for staging or local investigation.                                                                         | P1       |
| FR-13 | The specs must define a staged rollout path from planning to production.                                                                                                     | P0       |
| FR-14 | The specs must define rollback triggers based on unbounded memory growth, cross-request bleed, broken security or Doctrine reset behavior, and unstable worker restarts.     | P0       |

## Non-Functional Requirements

| ID     | Requirement                                                                                                                                                | Category      |
| ------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------- |
| NFR-01 | No numeric memory threshold may be declared before a repository baseline is established.                                                                   | Measurement   |
| NFR-02 | Observability must distinguish normal warm-up from unbounded worker growth.                                                                                | Observability |
| NFR-03 | Worker restarts must be measurable or logged so restart loops are visible.                                                                                 | Operations    |
| NFR-04 | The memory-safety suite must integrate with existing PHPUnit workflows rather than introduce an unrelated runner.                                          | Testing       |
| NFR-05 | Repeated-request functional tests must approximate same-kernel behavior using `disableReboot()` where that is the right fit.                               | Testing       |
| NFR-06 | The test design must acknowledge that `disableReboot()` changes security token storage and Doctrine behavior and may require test-environment adjustments. | Testing       |
| NFR-07 | `roave/no-leaks` must not be positioned as the primary migration solution.                                                                                 | Tooling       |
| NFR-08 | The rollout plan must start conservative and tune later based on evidence.                                                                                 | Operations    |
| NFR-09 | Planning artifacts must stay implementation-driving and specific to Symfony, FrankenPHP, and PHPUnit.                                                      | Documentation |

## Acceptance Criteria

1. Specs explicitly describe long-running worker constraints and reject request-isolated assumptions.
2. Risky stateful services are listed and a repeatable audit checklist is defined.
3. A memory-safety test suite is defined around `shipmonk/memory-scanner` and `ObjectDeallocationCheckerKernelTestCaseTrait`.
4. Repeated-request tests are planned for public read, authenticated read, Doctrine-heavy write, serializer-heavy response, error path, and shared-cache or OAuth flows.
5. `ResetInterface` and service reset strategy are specified for mutable services.
6. Worker loop cleanup and `gc_collect_cycles()` are documented as explicit design requirements.
7. `MAX_REQUESTS` is documented as a safety fuse, not a substitute for fixing leaks.
8. `memprof` is documented as the escalation path for hard leaks.
9. The rollout path defines staging soak verification, production safeguards, and rollback triggers.

## Assumptions / Open Questions

- Worker bootstrap already exists through FrankenPHP runtime and Caddy worker configuration.
- The exact staging soak environment and memory metrics sink still need human confirmation.
- Compatibility of `shipmonk/memory-scanner` with the repository's exact PHPUnit/Symfony combination must be verified during implementation.
- Specific service hotspots beyond the current cache, auth, and OAuth inventory still need confirmation from a full container audit.
