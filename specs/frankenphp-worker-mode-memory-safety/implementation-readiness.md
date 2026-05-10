---
stepsCompleted: [init, document-check, gap-review, readiness-decision, next-step]
inputDocuments:
  [
    specs/frankenphp-worker-mode-memory-safety/prd.md,
    specs/frankenphp-worker-mode-memory-safety/architecture.md,
    specs/frankenphp-worker-mode-memory-safety/epic.md,
    specs/frankenphp-worker-mode-memory-safety/stories.md,
  ]
workflowType: 'implementation-readiness'
project_name: 'VilnaCRM User Service - FrankenPHP Worker Mode Memory Safety'
author: 'Codex'
date: '2026-04-12'
revision: '1 - planning baseline'
---

# Implementation Readiness - FrankenPHP Worker Mode Memory Safety

## Readiness Decision

Status: ready for service audit and test-implementation planning, not ready for production worker rollout.

## Alignment Check

- PRD defines the problem, goals, non-goals, requirements, and acceptance criteria.
- Architecture defines the long-running runtime model, reset strategy, testing strategy, and rollout safeguards.
- Epics break the work into foundations, audit/reset, test coverage, and operational rollout.
- Stories define the actionable backlog for the first implementation wave.

## What Is Ready

- The worker-mode risk model is explicit.
- The primary leak-detection tooling and Symfony/PHPUnit integration path are specified.
- The initial hot-path coverage set is concrete.
- The rollout sequence and rollback triggers are documented.

## What Still Needs Human Confirmation

- Exact staging or soak environment availability.
- Preferred first-wave endpoint list if GraphQL must be deferred or included immediately.
- Exact runtime hook for worker-loop cleanup in this deployment.
- Compatibility confirmation for `shipmonk/memory-scanner` with the repository's exact test stack.

## Blocking Gaps Before Implementation

- No completed audit of mutable services yet.
- No memory baseline for staging yet.
- No actual `ResetInterface` implementations in application services yet.
- No actual `KernelTestCase` or `WebTestCase` leak suite yet.

## Recommended Next Step

Start with Story 1.1 and Story 1.2 in the next implementation PR, then move directly into Story 3.1 and Story 3.2 so the audit and tests evolve together.
