---
stepsCompleted: [init, epic-list, coverage-map, acceptance-criteria]
inputDocuments:
  [
    specs/frankenphp-worker-mode-memory-safety/prd.md,
    specs/frankenphp-worker-mode-memory-safety/architecture.md,
  ]
workflowType: 'epic'
project_name: 'VilnaCRM User Service - FrankenPHP Worker Mode Memory Safety'
author: 'Codex'
date: '2026-04-12'
revision: '1 - planning baseline'
---

# Epic Plan - FrankenPHP Worker Mode Memory Safety

## Epic List

1. **Epic 1: Worker-Mode Safety Foundations**
   - Rewrite the runtime assumptions, cleanup contract, restart fuse guidance, and rollout criteria so worker mode is treated as a long-running process.
2. **Epic 2: Stateful Service Audit and Reset Strategy**
   - Audit mutable singleton services, classify their state, and define `ResetInterface` or redesign actions.
3. **Epic 3: Symfony Leak-Test Coverage**
   - Add targeted `KernelTestCase` and `WebTestCase` memory-safety coverage with retained-object checks and same-kernel repeated-request scenarios.
4. **Epic 4: Staging Soak and Operational Guardrails**
   - Establish rollout evidence, restart observability, rollback triggers, and production safety criteria.

## Requirement Coverage Map

| Requirement | Epic 1 | Epic 2 | Epic 3 | Epic 4 |
| ----------- | ------ | ------ | ------ | ------ |
| FR-01       | x      |        |        |        |
| FR-02       | x      |        |        | x      |
| FR-03       | x      |        |        | x      |
| FR-04       |        | x      | x      |        |
| FR-05       |        | x      |        |        |
| FR-06       |        | x      | x      |        |
| FR-07       |        |        | x      |        |
| FR-08       |        |        | x      |        |
| FR-09       |        |        | x      |        |
| FR-10       |        |        | x      |        |
| FR-11       |        |        | x      | x      |
| FR-12       |        |        | x      | x      |
| FR-13       | x      |        |        | x      |
| FR-14       | x      |        |        | x      |
| NFR-01      |        |        |        | x      |
| NFR-02      | x      |        |        | x      |
| NFR-03      | x      |        |        | x      |
| NFR-04      |        |        | x      |        |
| NFR-05      |        |        | x      |        |
| NFR-06      |        |        | x      |        |
| NFR-07      | x      |        |        |        |
| NFR-08      | x      |        |        | x      |
| NFR-09      | x      |        |        |        |

## Epic 1: Worker-Mode Safety Foundations

### Outcome

Engineers stop treating FrankenPHP worker mode as request-isolated PHP and instead implement against explicit long-running process rules.

### Acceptance Criteria

1. Specs state that the application is booted once and reused across requests.
2. Specs require post-request cleanup, including `gc_collect_cycles()` in the worker loop.
3. Specs define `MAX_REQUESTS` as a conservative safety fuse.
4. Specs define rollout blockers and rollback triggers for memory and state issues.

## Epic 2: Stateful Service Audit and Reset Strategy

### Outcome

The service has a concrete inventory of risky long-lived services plus a repeatable migration checklist for `ResetInterface` or redesign work.

### Acceptance Criteria

1. The audit lists concrete service categories and initial repository targets.
2. The migration checklist covers property identification, state classification, redesign or `reset()`, and leak-focused tests.
3. The spec explicitly forbids unbounded request data and uncontrolled static caches in singleton services.

## Epic 3: Symfony Leak-Test Coverage

### Outcome

The repository has an implementation-ready plan for PHPUnit memory-safety coverage that approximates worker reuse instead of reboot-per-request behavior.

### Acceptance Criteria

1. `shipmonk/memory-scanner` is the primary leak-detection package.
2. `ObjectDeallocationCheckerKernelTestCaseTrait` is part of the planned integration strategy.
3. Targeted flows cover public read, authenticated read, Doctrine-heavy write, serializer-heavy response, error path, and shared-cache or OAuth flows.
4. `disableReboot()`-based tests are planned with explicit caveats for security token storage and Doctrine ODM behavior.
5. CI failure behavior is defined for confirmed retained-object leaks.

## Epic 4: Staging Soak and Operational Guardrails

### Outcome

Worker rollout is evidence-driven, observable, and reversible.

### Acceptance Criteria

1. The rollout sequence is staged from planning through production.
2. Staging soak verification requires worker RSS trend observation and restart visibility.
3. Production starts with conservative restart behavior and tunes later.
4. `memprof` is documented as the escalation path for hard leaks.
