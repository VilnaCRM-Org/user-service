# User Cache Parity Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Align user-service caching, cache invalidation, and HTTP cache headers with core-service best practices while preserving localization.

**Architecture:** Add update/delete domain events and cache invalidation subscribers, route delete through a command/processor, extend cache keys/tags for collections, and align HTTP cache headers with ETag. Tests cover cache keys, invalidation, delete flow, schemathesis cleanup, and HTTP caching.

**Tech Stack:** PHP 8.3, Symfony 7.3, API Platform 4.1, Redis cache pools, PHPUnit, API Platform Test Client.

### Task 1: CacheKeyBuilder Collection Key

**Files:**
- Modify: `src/Shared/Infrastructure/Cache/CacheKeyBuilder.php`
- Modify: `tests/Unit/Shared/Infrastructure/Cache/CacheKeyBuilderTest.php`

**Step 1: Write the failing test**

```php
public function testBuildUserCollectionKeyNormalizesAndHashesFilters(): void
{
    $key = $this->builder->buildUserCollectionKey(['b' => 2, 'a' => 1]);
    $expected = 'user.collection.' . hash('sha256', json_encode(['a' => 1, 'b' => 2], JSON_THROW_ON_ERROR));

    self::assertSame($expected, $key);
}
```

**Step 2: Run test to verify it fails**

Run: `make unit-tests`
Expected: FAIL with undefined method `buildUserCollectionKey`.

**Step 3: Write minimal implementation**

```php
public function buildUserCollectionKey(array $filters): string
{
    ksort($filters);

    return $this->build(
        'user',
        'collection',
        hash('sha256', json_encode($filters, JSON_THROW_ON_ERROR))
    );
}
```

**Step 4: Run test to verify it passes**

Run: `make unit-tests`
Expected: PASS.

**Step 5: Commit**

```bash
git add src/Shared/Infrastructure/Cache/CacheKeyBuilder.php tests/Unit/Shared/Infrastructure/Cache/CacheKeyBuilderTest.php
git commit -m "feat: add user collection cache key builder"
```

### Task 2: User Updated/Deleted Domain Events

**Files:**
- Create: `src/User/Domain/Event/UserUpdatedEvent.php`
- Create: `src/User/Domain/Event/UserDeletedEvent.php`
- Create: `tests/Unit/User/Domain/Event/UserUpdatedEventTest.php`
- Create: `tests/Unit/User/Domain/Event/UserDeletedEventTest.php`

**Step 1: Write the failing tests**

```php
public function testUserUpdatedEventSerializesAndDetectsEmailChange(): void
{
    $event = new UserUpdatedEvent('id-1', 'new@example.com', 'old@example.com', 'event-1', '2025-01-01T00:00:00+00:00');

    self::assertSame('user.updated', $event::eventName());
    self::assertTrue($event->emailChanged());
    self::assertSame('id-1', $event->userId());
    self::assertSame('new@example.com', $event->currentEmail());
    self::assertSame('old@example.com', $event->previousEmail());

    $roundTrip = UserUpdatedEvent::fromPrimitives(
        $event->toPrimitives(),
        'event-1',
        '2025-01-01T00:00:00+00:00'
    );

    self::assertSame('id-1', $roundTrip->userId());
    self::assertSame('new@example.com', $roundTrip->currentEmail());
    self::assertSame('old@example.com', $roundTrip->previousEmail());
}
```

```php
public function testUserDeletedEventSerializes(): void
{
    $event = new UserDeletedEvent('id-1', 'user@example.com', 'event-1', '2025-01-01T00:00:00+00:00');

    self::assertSame('user.deleted', $event::eventName());
    self::assertSame('id-1', $event->userId());
    self::assertSame('user@example.com', $event->userEmail());

    $roundTrip = UserDeletedEvent::fromPrimitives(
        $event->toPrimitives(),
        'event-1',
        '2025-01-01T00:00:00+00:00'
    );

    self::assertSame('id-1', $roundTrip->userId());
    self::assertSame('user@example.com', $roundTrip->userEmail());
}
```

**Step 2: Run test to verify it fails**

Run: `make unit-tests`
Expected: FAIL with missing classes.

**Step 3: Write minimal implementation**

```php
final class UserUpdatedEvent extends DomainEvent
{
    public function __construct(
        private readonly string $userId,
        private readonly string $currentEmail,
        private readonly ?string $previousEmail,
        string $eventId,
        ?string $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
    }

    public static function fromPrimitives(array $body, string $eventId, string $occurredOn): self
    {
        return new self(
            userId: $body['user_id'],
            currentEmail: $body['current_email'],
            previousEmail: $body['previous_email'] ?? null,
            eventId: $eventId,
            occurredOn: $occurredOn
        );
    }

    public static function eventName(): string
    {
        return 'user.updated';
    }

    public function toPrimitives(): array
    {
        return [
            'user_id' => $this->userId,
            'current_email' => $this->currentEmail,
            'previous_email' => $this->previousEmail,
        ];
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function currentEmail(): string
    {
        return $this->currentEmail;
    }

    public function previousEmail(): ?string
    {
        return $this->previousEmail;
    }

    public function emailChanged(): bool
    {
        return $this->previousEmail !== null && $this->previousEmail !== $this->currentEmail;
    }
}
```

```php
final class UserDeletedEvent extends DomainEvent
{
    public function __construct(
        private readonly string $userId,
        private readonly string $userEmail,
        string $eventId,
        ?string $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
    }

    public static function fromPrimitives(array $body, string $eventId, string $occurredOn): self
    {
        return new self(
            userId: $body['user_id'],
            userEmail: $body['user_email'],
            eventId: $eventId,
            occurredOn: $occurredOn
        );
    }

    public static function eventName(): string
    {
        return 'user.deleted';
    }

    public function toPrimitives(): array
    {
        return [
            'user_id' => $this->userId,
            'user_email' => $this->userEmail,
        ];
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function userEmail(): string
    {
        return $this->userEmail;
    }
}
```

**Step 4: Run test to verify it passes**

Run: `make unit-tests`
Expected: PASS.

**Step 5: Commit**

```bash
git add src/User/Domain/Event/UserUpdatedEvent.php src/User/Domain/Event/UserDeletedEvent.php tests/Unit/User/Domain/Event/UserUpdatedEventTest.php tests/Unit/User/Domain/Event/UserDeletedEventTest.php
git commit -m "feat: add user updated/deleted domain events"
```

### Task 3: Cache Invalidation Subscribers

**Files:**
- Modify: `src/User/Application/EventSubscriber/UserRegisteredCacheInvalidationSubscriber.php`
- Create: `src/User/Application/EventSubscriber/UserUpdatedCacheInvalidationSubscriber.php`
- Create: `src/User/Application/EventSubscriber/UserDeletedCacheInvalidationSubscriber.php`
- Delete: `src/User/Application/EventSubscriber/EmailChangedCacheInvalidationSubscriber.php`
- Delete: `src/User/Application/EventSubscriber/PasswordChangedCacheInvalidationSubscriber.php`
- Delete: `src/User/Application/EventSubscriber/UserConfirmedCacheInvalidationSubscriber.php`
- Modify: `tests/Unit/User/Application/EventSubscriber/UserRegisteredCacheInvalidationSubscriberTest.php`
- Create: `tests/Unit/User/Application/EventSubscriber/UserUpdatedCacheInvalidationSubscriberTest.php`
- Create: `tests/Unit/User/Application/EventSubscriber/UserDeletedCacheInvalidationSubscriberTest.php`
- Delete: `tests/Unit/User/Application/EventSubscriber/EmailChangedCacheInvalidationSubscriberTest.php`
- Delete: `tests/Unit/User/Application/EventSubscriber/PasswordChangedCacheInvalidationSubscriberTest.php`
- Delete: `tests/Unit/User/Application/EventSubscriber/UserConfirmedCacheInvalidationSubscriberTest.php`

**Step 1: Write the failing tests**

```php
public function testInvokeInvalidatesCacheIncludingCollection(): void
{
    // expect invalidateTags with user.{id}, user.email.{hash}, user.collection
}
```

```php
public function testUserUpdatedInvalidatesCurrentAndPreviousEmail(): void
{
    // expect invalidateTags with user.{id}, user.email.{currentHash}, user.email.{previousHash}, user.collection
}
```

```php
public function testUserDeletedInvalidatesIdEmailAndCollection(): void
{
    // expect invalidateTags with user.{id}, user.email.{hash}, user.collection
}
```

**Step 2: Run test to verify it fails**

Run: `make unit-tests`
Expected: FAIL due to missing classes/expectations.

**Step 3: Write minimal implementation**

```php
public function __invoke(UserRegisteredEvent $event): void
{
    $user = $event->user;
    $this->cache->invalidateTags([
        'user.' . $user->getId(),
        'user.email.' . $this->cacheKeyBuilder->hashEmail($user->getEmail()),
        'user.collection',
    ]);
}
```

```php
final readonly class UserUpdatedCacheInvalidationSubscriber implements UserCacheInvalidationSubscriberInterface
{
    public function __invoke(UserUpdatedEvent $event): void
    {
        $tags = [
            'user.' . $event->userId(),
            'user.email.' . $this->cacheKeyBuilder->hashEmail($event->currentEmail()),
            'user.collection',
        ];

        if ($event->emailChanged() && $event->previousEmail() !== null) {
            $tags[] = 'user.email.' . $this->cacheKeyBuilder->hashEmail($event->previousEmail());
        }

        $this->cache->invalidateTags($tags);
    }
}
```

```php
final readonly class UserDeletedCacheInvalidationSubscriber implements UserCacheInvalidationSubscriberInterface
{
    public function __invoke(UserDeletedEvent $event): void
    {
        $this->cache->invalidateTags([
            'user.' . $event->userId(),
            'user.email.' . $this->cacheKeyBuilder->hashEmail($event->userEmail()),
            'user.collection',
        ]);
    }
}
```

**Step 4: Run test to verify it passes**

Run: `make unit-tests`
Expected: PASS.

**Step 5: Commit**

```bash
git add src/User/Application/EventSubscriber/UserRegisteredCacheInvalidationSubscriber.php src/User/Application/EventSubscriber/UserUpdatedCacheInvalidationSubscriber.php src/User/Application/EventSubscriber/UserDeletedCacheInvalidationSubscriber.php tests/Unit/User/Application/EventSubscriber/UserRegisteredCacheInvalidationSubscriberTest.php tests/Unit/User/Application/EventSubscriber/UserUpdatedCacheInvalidationSubscriberTest.php tests/Unit/User/Application/EventSubscriber/UserDeletedCacheInvalidationSubscriberTest.php
git rm src/User/Application/EventSubscriber/EmailChangedCacheInvalidationSubscriber.php src/User/Application/EventSubscriber/PasswordChangedCacheInvalidationSubscriber.php src/User/Application/EventSubscriber/UserConfirmedCacheInvalidationSubscriber.php tests/Unit/User/Application/EventSubscriber/EmailChangedCacheInvalidationSubscriberTest.php tests/Unit/User/Application/EventSubscriber/PasswordChangedCacheInvalidationSubscriberTest.php tests/Unit/User/Application/EventSubscriber/UserConfirmedCacheInvalidationSubscriberTest.php
git commit -m "feat: align user cache invalidation subscribers"
```

### Task 4: Update/Delete Commands and Schemathesis Cleanup

**Files:**
- Modify: `src/User/Application/CommandHandler/UpdateUserCommandHandler.php`
- Modify: `src/User/Application/CommandHandler/ConfirmUserCommandHandler.php`
- Modify: `src/User/Application/CommandHandler/ConfirmPasswordResetCommandHandler.php`
- Create: `src/User/Application/Command/DeleteUserCommand.php`
- Create: `src/User/Application/CommandHandler/DeleteUserCommandHandler.php`
- Create: `src/User/Application/Processor/UserDeleteProcessor.php`
- Modify: `src/User/Infrastructure/EventListener/SchemathesisCleanupListener.php`
- Modify: `tests/Unit/User/Infrastructure/EventListener/SchemathesisCleanupListenerTest.php`
- Create: `tests/Unit/User/Application/CommandHandler/DeleteUserCommandHandlerTest.php`
- Create: `tests/Unit/User/Application/Processor/UserDeleteProcessorTest.php`

**Step 1: Write the failing tests**

```php
public function testDeleteUserHandlerDeletesAndPublishesEvent(): void
{
    // expect repository->delete and eventBus->publish(UserDeletedEvent)
}
```

```php
public function testUserDeleteProcessorDispatchesDeleteCommand(): void
{
    // expect commandBus->dispatch(new DeleteUserCommand($user))
}
```

```php
public function testSchemathesisCleanupDispatchesDeleteCommand(): void
{
    // expect commandBus->dispatch for each user instead of repository->delete
}
```

**Step 2: Run test to verify it fails**

Run: `make unit-tests`
Expected: FAIL due to missing classes/behavior.

**Step 3: Write minimal implementation**

```php
public function __invoke(UpdateUserCommand $command): void
{
    $user = $command->user;
    $previousEmail = $user->getEmail();

    // existing update logic

    $currentEmail = $user->getEmail();
    $this->eventBus->publish(
        ...$events,
        new UserUpdatedEvent(
            userId: $user->getId(),
            currentEmail: $currentEmail,
            previousEmail: $previousEmail !== $currentEmail ? $previousEmail : null,
            eventId: (string) $this->uuidFactory->create()
        )
    );
}
```

```php
public function __invoke(ConfirmUserCommand $command): void
{
    $userConfirmedEvent = $user->confirm(...);
    $this->eventBus->publish(
        $userConfirmedEvent,
        new UserUpdatedEvent($user->getId(), $user->getEmail(), null, (string) $this->uuidFactory->create())
    );
}
```

```php
public function __invoke(ConfirmPasswordResetCommand $command): void
{
    // after saving user
    $this->eventBus->publish(
        $passwordResetConfirmedEvent,
        new UserUpdatedEvent($user->getId(), $user->getEmail(), null, (string) $this->uuidFactory->create())
    );
}
```

```php
final readonly class DeleteUserCommand implements CommandInterface
{
    public function __construct(public UserInterface $user) {}
}
```

```php
final readonly class DeleteUserCommandHandler implements CommandHandlerInterface
{
    public function __invoke(DeleteUserCommand $command): void
    {
        $user = $command->user;
        $this->repository->delete($user);
        $this->eventBus->publish(
            new UserDeletedEvent($user->getId(), $user->getEmail(), (string) $this->uuidFactory->create())
        );
    }
}
```

```php
final readonly class UserDeleteProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        if (!$data instanceof User) {
            throw new InvalidArgumentException('Expected instance of User');
        }

        $this->commandBus->dispatch(new DeleteUserCommand($data));

        return null;
    }
}
```

```php
private function deleteUsers(array $emails): void
{
    $users = array_filter(/* existing lookup */);

    foreach ($users as $user) {
        $this->commandBus->dispatch(new DeleteUserCommand($user));
    }
}
```

**Step 4: Run test to verify it passes**

Run: `make unit-tests`
Expected: PASS.

**Step 5: Commit**

```bash
git add src/User/Application/CommandHandler/UpdateUserCommandHandler.php src/User/Application/CommandHandler/ConfirmUserCommandHandler.php src/User/Application/CommandHandler/ConfirmPasswordResetCommandHandler.php src/User/Application/Command/DeleteUserCommand.php src/User/Application/CommandHandler/DeleteUserCommandHandler.php src/User/Application/Processor/UserDeleteProcessor.php src/User/Infrastructure/EventListener/SchemathesisCleanupListener.php tests/Unit/User/Infrastructure/EventListener/SchemathesisCleanupListenerTest.php tests/Unit/User/Application/CommandHandler/DeleteUserCommandHandlerTest.php tests/Unit/User/Application/Processor/UserDeleteProcessorTest.php
git commit -m "feat: publish user update/delete events and delete command"
```

### Task 5: HTTP Cache Integration Test and Cache Headers

**Files:**
- Create: `tests/Integration/UserHttpCacheTest.php`
- Modify: `config/api_platform/resources/User.yaml`

**Step 1: Write the failing tests**

```php
public function testGetUserReturnsCacheControlAndEtag(): void
{
    // create user, GET /api/users/{id}, assert Cache-Control and ETag
}

public function testGetUserCollectionReturnsCacheControl(): void
{
    // create user, GET /api/users, assert Cache-Control
}

public function testEtagChangesAfterPatch(): void
{
    // GET ETag, PATCH initials (with oldPassword), GET again and compare
}
```

**Step 2: Run test to verify it fails**

Run: `make integration-tests`
Expected: FAIL due to missing test file or headers.

**Step 3: Write minimal implementation**

```yaml
ApiPlatform\Metadata\GetCollection:
  cacheHeaders:
    max_age: 300
    shared_max_age: 600
    vary: ['Accept', 'Authorization', 'Accept-Language']
ApiPlatform\Metadata\Get:
  cacheHeaders:
    max_age: 600
    shared_max_age: 600
    vary: ['Accept', 'Authorization', 'Accept-Language']
    etag: true
ApiPlatform\Metadata\Delete:
  processor: 'App\\User\\Application\\Processor\\UserDeleteProcessor'
ApiPlatform\Metadata\GraphQl\DeleteMutation:
  processor: 'App\\User\\Application\\Processor\\UserDeleteProcessor'
```

```php
final class UserHttpCacheTest extends ApiTestCase
{
    private Generator $faker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create();
    }

    public function testGetUserReturnsCacheControlAndEtag(): void
    {
        $client = self::createClient();
        $user = $this->createTestUser();

        $client->request('GET', "/api/users/{$user->getId()}");

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Cache-Control', 'max-age=600, public, s-maxage=600');
        self::assertResponseHasHeader('ETag');
    }

    public function testGetUserCollectionReturnsCacheControl(): void
    {
        $client = self::createClient();
        $this->createTestUser();

        $client->request('GET', '/api/users');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Cache-Control', 'max-age=300, public, s-maxage=600');
    }

    public function testEtagChangesAfterPatch(): void
    {
        $client = self::createClient();
        $userData = $this->createTestUser();
        $user = $userData['user'];
        $plainPassword = $userData['plainPassword'];

        $response1 = $client->request('GET', "/api/users/{$user->getId()}");
        self::assertResponseIsSuccessful();
        $etag1 = $response1->getHeaders()['etag'][0] ?? null;
        self::assertNotNull($etag1);

        $client->request('PATCH', "/api/users/{$user->getId()}", [
            'json' => ['initials' => 'ZZ', 'oldPassword' => $plainPassword],
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
        ]);
        self::assertResponseIsSuccessful();

        $response2 = $client->request('GET', "/api/users/{$user->getId()}");
        self::assertResponseIsSuccessful();
        $etag2 = $response2->getHeaders()['etag'][0] ?? null;
        self::assertNotNull($etag2);
        self::assertNotEquals($etag1, $etag2);
    }

    private function createTestUser(): array
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $hasherFactory = self::getContainer()->get(PasswordHasherFactoryInterface::class);
        $hasher = $hasherFactory->getPasswordHasher(User::class);

        $plainPassword = $this->faker->password(12);
        $user = new User(
            $this->faker->unique()->email(),
            strtoupper(substr($this->faker->firstName(), 0, 1) . substr($this->faker->lastName(), 0, 1)),
            $hasher->hash($plainPassword),
            new Uuid($this->faker->uuid())
        );

        $em->persist($user);
        $em->flush();

        return ['user' => $user, 'plainPassword' => $plainPassword];
    }
}
```

**Step 4: Run test to verify it passes**

Run: `make integration-tests`
Expected: PASS.

**Step 5: Commit**

```bash
git add tests/Integration/UserHttpCacheTest.php config/api_platform/resources/User.yaml
git commit -m "feat: align user http cache headers and etag"
```

### Task 6: Test Cache Configuration

**Files:**
- Modify: `config/packages/test/cache.yaml`

**Step 1: Write the failing test**

```php
public function testTestCacheUsesArrayAdapters(): void
{
    // use container to assert cache.app and cache.user are array adapters
}
```

**Step 2: Run test to verify it fails**

Run: `make integration-tests`
Expected: FAIL while app cache is redis.

**Step 3: Write minimal implementation**

```yaml
framework:
  cache:
    app: cache.adapter.array
    default_redis_provider: null
    pools:
      app:
        adapter: cache.adapter.array
        provider: null
      cache.user:
        adapter: cache.adapter.array
        provider: null
        tags: true
```

**Step 4: Run test to verify it passes**

Run: `make integration-tests`
Expected: PASS.

**Step 5: Commit**

```bash
git add config/packages/test/cache.yaml
git commit -m "chore: use array cache pools in tests"
```

### Task 7: Update CachedUserRepository deleteAll Tags

**Files:**
- Modify: `src/User/Infrastructure/Repository/CachedUserRepository.php`
- Modify: `tests/Unit/User/Infrastructure/Repository/CachedUserRepositoryTest.php`

**Step 1: Write the failing test**

```php
public function testDeleteAllInvalidatesCollectionTag(): void
{
    $this->cache->expects($this->once())
        ->method('invalidateTags')
        ->with(['user', 'user.collection']);

    $this->repository->deleteAll();
}
```

**Step 2: Run test to verify it fails**

Run: `make unit-tests`
Expected: FAIL due to missing tag.

**Step 3: Write minimal implementation**

```php
$this->cache->invalidateTags(['user', 'user.collection']);
```

**Step 4: Run test to verify it passes**

Run: `make unit-tests`
Expected: PASS.

**Step 5: Commit**

```bash
git add src/User/Infrastructure/Repository/CachedUserRepository.php tests/Unit/User/Infrastructure/Repository/CachedUserRepositoryTest.php
git commit -m "feat: invalidate collection tag on user deleteAll"
```

### Task 8: Full Verification

**Files:**
- N/A

**Step 1: Run targeted tests**

Run: `make unit-tests`
Expected: PASS.

**Step 2: Run integration tests**

Run: `make integration-tests`
Expected: PASS.

**Step 3: Run CI**

Run: `make ci`
Expected: "✅ CI checks successfully passed!"
