---
stepsCompleted: [init, problem-statement, goals, non-goals, scope, assumptions]
inputDocuments:
  [
    specs/frankenphp-worker-mode-memory-safety/research.md,
    Dockerfile,
    infrastructure/docker/php/worker.Caddyfile,
    composer.json,
  ]
workflowType: 'brief'
project_name: 'VilnaCRM User Service - FrankenPHP Worker Mode Memory Safety'
author: 'Codex'
date: '2026-04-12'
revision: '1 - planning baseline'
---

# Product Brief - FrankenPHP Worker Mode Memory Safety

## Problem Statement

User Service already benefits from FrankenPHP performance characteristics, but worker mode keeps the Symfony application booted and in memory across requests. That changes the safety model of the application. Mutable singleton services, managed Doctrine ODM documents, cached request data, security context helpers, and third-party client wrappers can now retain state between requests or slowly grow memory over time.

Traditional PHP assumptions do not protect the service here. A request finishing is no longer enough to guarantee cleanup. The repository therefore needs an explicit worker-mode safety plan before engineers expand or rely on FrankenPHP worker mode as a production default.

## Goals

- Safely adopt FrankenPHP worker mode as a first-class architectural constraint for User Service.
- Prevent request-to-request state contamination in long-running Symfony workers.
- Detect retained-object leaks early in CI and before production rollout.
- Define operational safeguards that reduce risk when legacy or third-party leaks are not fully eliminated yet.
- Keep the safety strategy compatible with the repository's Symfony, API Platform, PHPUnit, and delivery workflows.

## Non-Goals

- Guarantee mathematically perfect zero memory growth in every dependency or native extension.
- Rewrite the entire application before worker-mode rollout.
- Treat `MAX_REQUESTS` as the complete solution to memory safety.
- Depend on ad-hoc manual profiling as the only verification strategy.
- Implement production worker-mode fixes in this planning PR.

## Scope

### In scope

- rewriting the BMAD planning bundle for worker-mode memory safety
- defining service audit rules for mutable state
- specifying a Symfony-compatible leak-test strategy
- defining CI, staging, rollout, rollback, and observability guardrails
- selecting the primary and secondary tooling for leak detection

### Out of scope

- changing production worker bootstrap code in this PR
- implementing `ResetInterface` in application services
- adding the actual PHPUnit memory suite in this PR
- setting numeric memory thresholds before baselines exist

## Why Now

- The repository already has FrankenPHP-specific runtime and performance documentation.
- The missing piece is safety, not motivation.
- Worker mode without a memory-safety plan creates a realistic risk of retained request state, cross-request contamination, and gradual worker RSS growth that will be harder to debug after rollout.

## Delivery Expectation

This planning track should produce implementation-driving specifications, not exploratory notes. An engineer picking up the next implementation PR should not have to invent the worker cleanup model, the leak-test approach, or the rollout guardrails from scratch.

## Assumptions / Open Questions

- Worker mode is already part of the intended deployment path, not an optional experiment.
- The repository will keep Symfony runtime and API Platform as-is while adding safety measures.
- The exact staging environment for soak verification still needs human confirmation.
