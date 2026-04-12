---
stepsCompleted: [init, current-state-discovery, risk-surface, candidate-flows, assumptions]
inputDocuments:
  [
    Dockerfile,
    infrastructure/docker/php/worker.Caddyfile,
    public/index.php,
    composer.json,
    docs/performance-frankenphp.md,
    docs/php-fpm-vs-frankenphp.md,
    config/api_platform/resources/User.yaml,
    config/services.yaml,
    config/services_test.yaml,
    tests/Integration/IntegrationTestCase.php,
  ]
workflowType: 'technical-research'
project_name: 'VilnaCRM User Service - FrankenPHP Worker Mode Memory Safety'
author: 'Codex'
date: '2026-04-12'
revision: '1 - planning baseline'
---

# Technical Research - FrankenPHP Worker Mode Memory Safety

## Executive Summary

User Service already ships FrankenPHP in production images and explicitly enables worker mode through `FRANKENPHP_CONFIG="import worker.Caddyfile"` plus `Runtime\FrankenPhpSymfony\Runtime`. That gives the service a real performance upside, but it also means request-isolated PHP assumptions are no longer sufficient. The service must be treated as a long-running Symfony process whose container and singleton services can survive across requests.

The repository currently has performance evidence for FrankenPHP throughput and latency, but it does not yet document or test worker-mode memory safety. There is no dedicated retained-object test suite, no current application services implementing `ResetInterface`, and no existing use of `disableReboot()` to simulate same-kernel repeated requests in Symfony functional tests.

## Current Runtime Shape

- The production Docker image is based on `dunglas/frankenphp:1.4-php8.4-alpine`.
- Production enables FrankenPHP worker mode via `infrastructure/docker/php/worker.Caddyfile`.
- The worker entrypoint points at `public/index.php` and `Runtime\FrankenPhpSymfony\Runtime`.
- `public/index.php` returns the Symfony kernel through the runtime bootstrap and does not currently document any explicit post-request cleanup policy.
- The repository already positions FrankenPHP as a performance optimization in `docs/performance-frankenphp.md` and `docs/php-fpm-vs-frankenphp.md`.

## What Is Missing Today

- No planning artifacts define long-running worker constraints as first-class architecture requirements.
- No planning artifacts define a mandatory post-request cleanup contract that includes `gc_collect_cycles()` in the worker loop.
- No planning artifacts define a conservative `MAX_REQUESTS` style restart fuse for worker safety.
- No repository evidence shows application services currently implementing `ResetInterface` for worker reuse.
- No dedicated PHPUnit leak-detection package is installed.
- No `KernelTestCase` or `WebTestCase` memory-safety suite exists.
- No `disableReboot()`-based repeated-request tests currently exist.
- No rollout document explains how to distinguish expected warm-up from unbounded worker growth.

## High-Risk Service Categories In This Codebase

The service inventory already points to several categories that should be audited first for request-to-request retention:

### Cache and state holders

- `App\User\Infrastructure\Repository\CachedUserRepository`
- `App\User\Infrastructure\Repository\RedisTokenRepository`
- `App\OAuth\Infrastructure\Repository\RedisOAuthStateRepository`
- `App\User\Infrastructure\Provider\RedisAccountLockoutProvider`

### Security and request-context services

- `App\Shared\Infrastructure\Adapter\DualAuthenticator`
- `App\User\Application\Resolver\CurrentUserIdentityResolver`
- API Platform processors and resolvers that read the security token or request payload

### Doctrine ODM and persistence-adjacent services

- `MongoDBUserRepository`
- `MongoDBAuthSessionRepository`
- `MongoDBAuthRefreshTokenRepository`
- `MongoDBPendingTwoFactorRepository`
- `MongoDBRecoveryCodeRepository`
- Services that interact with `DocumentManager` or managed documents through caches

### OAuth and third-party integration services

- `App\OAuth\Application\Provider\OAuthProviderRegistry`
- `GitHubOAuthProvider`, `GoogleOAuthProvider`, `FacebookOAuthProvider`, `TwitterOAuthProvider`
- `App\OAuth\Infrastructure\Publisher\OAuthPublisher`
- `App\OAuth\Infrastructure\Factory\ResilientHttpClientFactory`

### Serializer and normalization-heavy helpers

- `JsonBodyConverter`
- `StringableArrayNormalizer`
- `SchemathesisPayloadConverter`
- API Platform normalizers and exception normalizers

## Candidate Memory-Safety Test Flows

The planning bundle should cover real flows already present in the service instead of generic Symfony endpoints.

### Flow 1: Public simple read

- `GET /api/health`
- Purpose: establish a low-complexity baseline for a worker that should not retain request objects or error context.

### Flow 2: Authenticated read through shared security services

- `GET /api/users/{id}`
- Purpose: exercise `DualAuthenticator`, security token resolution, `CachedUserRepository`, ODM identity-map reuse, and API Platform serialization.

### Flow 3: Doctrine ODM-heavy write

- `POST /api/users`
- `PATCH /api/users/{id}`
- Purpose: cover persist/flush, document management, validators, command dispatch, event subscribers, and cache invalidation.

### Flow 4: Serializer-heavy collection response

- `GET /api/users?page=1&itemsPerPage=50`
- GraphQL user collection query
- Purpose: stress API Platform serialization, normalizers, pagination, and collection-level caching paths.

### Flow 5: Error and exception path

- invalid sign-in attempt
- invalid token / unauthorized access
- malformed PATCH payload
- Purpose: ensure exception normalizers, error providers, and security responses do not retain request-scoped state.

### Flow 6: Shared-cache and OAuth flow

- `POST /oauth/token`
- social initiate/callback flow
- Purpose: cover Redis-backed OAuth state, provider registries, HTTP clients, and publisher/logging infrastructure.

## Testing and Tooling Gap

The current dependency set includes PHPUnit `^10.5`, Symfony BrowserKit, and Symfony PHPUnit Bridge, which is compatible with adding dedicated functional memory tests. The repository does not currently include:

- `shipmonk/memory-scanner`
- `arnaud-lb/memprof`
- a project-level trait or helper for retained-object checks

That means the migration plan must add a new test layer instead of trying to infer memory safety from the current unit/integration/load suites.

## Working Assumptions

- FrankenPHP worker bootstrap already exists for production and is not purely theoretical in this repository.
- The service will continue using Symfony runtime bootstrap instead of switching to a custom front controller.
- `shipmonk/memory-scanner` should be the primary leak-detection package for CI because it aligns with PHPUnit and Symfony functional tests.
- `memprof` should be documented as an optional forensic tool for staging or local debugging, not as a default CI dependency.
- `roave/no-leaks` should not be positioned as the primary solution for this migration.

## Open Questions

- Does the team already have a production or staging soak environment that can expose worker RSS trends over repeated requests?
- Is there an existing FrankenPHP worker-loop extension point in this deployment, or will cleanup guidance need to be expressed as runtime configuration plus Symfony termination behavior?
- Are there already known hotspot services beyond `CachedUserRepository`, security token resolution, and OAuth providers?
- Does the team want memory-safety coverage to start with REST only, or should GraphQL repeated-request tests be required in the first rollout batch?
