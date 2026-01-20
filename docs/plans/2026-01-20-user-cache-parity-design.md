# User Cache Parity Design

**Goal:** Align user-service caching behavior, invalidation, and HTTP cache headers with core-service best practices while preserving localization and existing messaging flows.

**Scope:** User read caches (by id/email), cache invalidation for create/update/delete, HTTP cache headers for REST GET endpoints, cache key consistency, test configuration and coverage.

**Non-goals:** Add `updatedAt` to User or introduce last-modified headers; add new repository-level collection caching.

## Architecture Overview

### Domain Events

- Introduce `UserUpdatedEvent` with `userId`, `currentEmail`, and optional `previousEmail`.
- Introduce `UserDeletedEvent` with `userId` and `userEmail`.
- Continue emitting existing `EmailChangedEvent` and `PasswordChangedEvent` for notification workflows.

### Event Publication Points

- `UpdateUserCommandHandler` captures previous email, updates user, saves, and publishes `UserUpdatedEvent`.
- `ConfirmUserCommandHandler` publishes `UserUpdatedEvent` after confirmation and save.
- `ConfirmPasswordResetCommandHandler` publishes `UserUpdatedEvent` after password change and save.
- New `DeleteUserCommandHandler` deletes user and publishes `UserDeletedEvent`.
- Registration remains unchanged and still publishes `UserRegisteredEvent`.

### Cache Invalidation Subscribers

- Keep `UserRegisteredCacheInvalidationSubscriber` but include `user.collection` tag in invalidation.
- Add `UserUpdatedCacheInvalidationSubscriber` to invalidate:
  - `user.{id}`
  - `user.email.{currentHash}`
  - `user.email.{previousHash}` when email changed
  - `user.collection`
- Add `UserDeletedCacheInvalidationSubscriber` to invalidate:
  - `user.{id}`
  - `user.email.{emailHash}`
  - `user.collection`
- Remove legacy cache invalidation subscribers tied to `EmailChangedEvent`, `PasswordChangedEvent`, and `UserConfirmedEvent` to match core-service (single update path for cache invalidation).

## Cache Policy and Keys

### Read Cache Policy

- `find` / `findById`:
  - Key: `user.{id}`
  - TTL: 600s
  - Consistency: SWR (`beta: 1.0`)
  - Tags: `user`, `user.{id}`
- `findByEmail`:
  - Key: `user.email.{hash}`
  - TTL: 300s
  - Consistency: Eventual
  - Tags: `user`, `user.email`, `user.email.{hash}`

### Collection Key

- Add `buildUserCollectionKey(array $filters)` to `CacheKeyBuilder`:
  - Sort filters by key
  - Hash JSON-encoded filters
  - Key: `user.collection.{hash}`
- No collection caching added yet, but `user.collection` tag enables correct invalidation.

## HTTP Cache Headers

Update `config/api_platform/resources/User.yaml` to match core-service:

- `GetCollection`:
  - `cacheHeaders`: `max_age: 300`, `shared_max_age: 600`
  - `vary`: `Accept`, `Authorization`, `Accept-Language`
- `Get`:
  - `cacheHeaders`: `max_age: 600`, `shared_max_age: 600`, `etag: true`
  - `vary`: `Accept`, `Authorization`, `Accept-Language`

`Accept-Language` stays to prevent serving cached content in the wrong locale.

## API Delete Flow

- Add `DeleteUserCommand` and `DeleteUserCommandHandler` (publish `UserDeletedEvent`).
- Add `DeleteUserProcessor` (mirrors core-service delete processor) and wire to REST and GraphQL delete operations.
- Update `SchemathesisCleanupListener` to dispatch the delete command instead of calling the repository directly.

## Configuration Changes

- `config/packages/test/cache.yaml`:
  - Use array adapters for `app` and `cache.user` pools
  - `tags: true` on `cache.user` pool

## Test Plan

### Unit Tests

- `CacheKeyBuilderTest`: add coverage for `buildUserCollectionKey`.
- Add tests for:
  - `UserUpdatedCacheInvalidationSubscriber`
  - `UserDeletedCacheInvalidationSubscriber`
- Remove tests for removed invalidation subscribers.

### Integration Tests

- Add `UserHttpCacheTest` (ApiPlatform `ApiTestCase`):
  - `GET /api/users/{id}` returns `Cache-Control` and `ETag`.
  - `GET /api/users` returns collection cache headers.
  - `PATCH /api/users/{id}` changes initials and yields a different `ETag`.
- Use Faker for all test data and hash passwords via `PasswordHasherFactoryInterface` to satisfy password checks.

## Observability

- Continue cache hit/miss/error logging in `CachedUserRepository`.
- Log cache invalidation events in new subscribers with `operation: cache.invalidation` and reason tags.

## Rollout Notes

- No DB schema change required.
- Event payloads remain compatible with async processing (serialization via `DomainEventEnvelope`).
- The new update/delete events centralize cache invalidation without altering notification workflows.
