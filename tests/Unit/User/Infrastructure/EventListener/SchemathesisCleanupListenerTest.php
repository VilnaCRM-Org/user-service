<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\EventListener;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\Event\UserDeletedEventFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Decoder\SchemathesisPayloadDecoder;
use App\User\Infrastructure\Evaluator\SchemathesisCleanupEvaluator;
use App\User\Infrastructure\EventListener\SchemathesisCleanupListener;
use App\User\Infrastructure\Extractor\SchemathesisBatchUsersEmailExtractor;
use App\User\Infrastructure\Extractor\SchemathesisEmailExtractor;
use App\User\Infrastructure\Extractor\SchemathesisSingleUserEmailExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class SchemathesisCleanupListenerTest extends UnitTestCase
{
    private SchemathesisCleanupEvaluator $evaluator;
    private SchemathesisEmailExtractor $emailExtractor;
    private SchemathesisCleanupListener $listener;
    private UserRepositoryInterface $repository;
    private EventBusInterface $eventBus;
    private UuidFactory $uuidFactory;
    private UserDeletedEventFactoryInterface $eventFactory;
    private SchemathesisCleanupListenerTestExpectations $expectations;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = new SchemathesisCleanupEvaluator();
        $this->emailExtractor = $this->createEmailExtractor();
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->uuidFactory = $this->createMock(UuidFactory::class);
        $this->eventFactory = $this->createMock(UserDeletedEventFactoryInterface::class);
        $this->listener = $this->createListener();
        $this->expectations = $this->createExpectations();
    }

    public function testListenerRemovesCreatedUserAfterSchemathesisRequest(): void
    {
        $email = $this->faker->email();

        $request = $this->schemathesisRequest('/api/users', [
            'email' => $email,
            'initials' => $this->faker->lexify('????????'),
            'password' => $this->faker->password(12),
        ]);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $user = $this->userWithEmail($email);

        $this->repository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->expectations->expectBatchDeleteAndEvents([$user]);

        ($this->listener)($event);
    }

    public function testListenerSkipsWhenHeaderMissing(): void
    {
        $payload = ['email' => $this->faker->email()];
        $request = Request::create(
            '/api/users',
            Request::METHOD_POST,
            content: json_encode($payload, JSON_THROW_ON_ERROR)
        );
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectations->expectNoRepositoryCalls();

        ($this->listener)($event);
    }

    public function testListenerRemovesBatchUsers(): void
    {
        $emails = [$this->faker->email(), $this->faker->email()];

        $request = $this->schemathesisRequest('/api/users/batch', [
            'users' => [
                ['email' => $emails[0]],
                ['email' => $emails[1]],
            ],
        ]);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $users = [
            $this->userWithEmail($emails[0]),
            $this->userWithEmail($emails[1]),
        ];
        $this->expectations->expectBatchFindByEmail($emails, $users);
        $this->expectations->expectBatchDeleteAndEvents($users);

        ($this->listener)($event);
    }

    public function testListenerSkipsWhenResponseStatusIsNotSuccessful(): void
    {
        $request = $this->schemathesisRequest('/api/users', ['email' => $this->faker->email()]);
        $event = $this->terminateEvent($request, Response::HTTP_BAD_REQUEST);

        $this->expectations->expectNoRepositoryCalls();

        ($this->listener)($event);
    }

    public function testListenerSkipsWhenBodyIsEmpty(): void
    {
        $request = $this->schemathesisRequest('/api/users', null);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectations->expectNoRepositoryCalls();

        ($this->listener)($event);
    }

    public function testListenerSkipsWhenUserNotFound(): void
    {
        $email = $this->faker->email();

        $request = $this->schemathesisRequest('/api/users', ['email' => $email]);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->repository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);
        $this->repository->expects($this->never())->method('deleteBatch');
        $this->eventFactory->expects($this->never())->method('create');
        $this->eventBus->expects($this->never())->method('publish');

        ($this->listener)($event);
    }

    public function testListenerContinuesWhenUserMissing(): void
    {
        $emails = [$this->faker->email(), $this->faker->email()];

        $request = $this->schemathesisRequest('/api/users/batch', [
            'users' => [
                ['email' => $emails[0]],
                ['email' => $emails[1]],
            ],
        ]);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $existingUser = $this->userWithEmail($emails[1]);

        $this->expectations->expectBatchFindByEmail($emails, [null, $existingUser]);
        $this->expectations->expectBatchDeleteAndEvents([$existingUser]);

        ($this->listener)($event);
    }

    public function testListenerSkipsWhenJsonIsMalformed(): void
    {
        $request = $this->schemathesisRequest('/api/users', '{invalid');
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectations->expectNoRepositoryCalls();

        ($this->listener)($event);
    }

    public function testListenerSkipsWhenPayloadIsNotArray(): void
    {
        $payload = json_encode('string', JSON_THROW_ON_ERROR);
        $request = $this->schemathesisRequest('/api/users', $payload);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectations->expectNoRepositoryCalls();

        ($this->listener)($event);
    }

    public function testListenerSkipsWhenBatchUsersNotArray(): void
    {
        $request = $this->schemathesisRequest('/api/users/batch', ['users' => 'string']);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectations->expectNoRepositoryCalls();

        ($this->listener)($event);
    }

    public function testListenerSkipsWhenBatchEntriesKeyMissing(): void
    {
        $request = $this->schemathesisRequest('/api/users/batch', ['something' => 'else']);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectations->expectNoRepositoryCalls();

        ($this->listener)($event);
    }

    public function testListenerSkipsWhenBatchEntriesNotArray(): void
    {
        $request = $this->schemathesisRequest('/api/users/batch', ['users' => ['string']]);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectations->expectNoRepositoryCalls();

        ($this->listener)($event);
    }

    public function testListenerSkipsWhenHeaderValueIsNotCleanupUsers(): void
    {
        $request = $this->schemathesisRequest(
            '/api/users',
            ['email' => $this->faker->email()],
            $this->faker->word()
        );
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectations->expectNoRepositoryCalls();

        ($this->listener)($event);
    }

    public function testListenerSkipsWhenEmailIsMissing(): void
    {
        $payload = ['initials' => $this->faker->lexify('????????')];
        $request = $this->schemathesisRequest('/api/users', $payload);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectations->expectNoRepositoryCalls();

        ($this->listener)($event);
    }

    public function testListenerSkipsWhenEmailIsNotString(): void
    {
        $request = $this->schemathesisRequest('/api/users', ['email' => ['value']]);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectations->expectNoRepositoryCalls();

        ($this->listener)($event);
    }

    public function testListenerSkipsInvalidEntriesInsideBatch(): void
    {
        $emails = [$this->faker->email(), $this->faker->email()];

        $request = $this->schemathesisRequest('/api/users/batch', [
            'users' => [
                ['email' => $emails[0]],
                $this->faker->word(),
                ['email' => ['not-string']],
                ['email' => $emails[1]],
            ],
        ]);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $users = [
            $this->userWithEmail($emails[0]),
            $this->userWithEmail($emails[1]),
        ];
        $this->expectations->expectBatchFindByEmail($emails, $users);
        $this->expectations->expectBatchDeleteAndEvents($users);

        ($this->listener)($event);
    }

    public function testListenerSkipsWhenPathIsNotHandled(): void
    {
        $request = $this->schemathesisRequest('/api/health', ['email' => $this->faker->email()]);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectations->expectNoRepositoryCalls();

        ($this->listener)($event);
    }

    public function testListenerDeletesEachEmailOnlyOnce(): void
    {
        $emails = [$this->faker->email(), $this->faker->email()];

        $request = $this->schemathesisRequest('/api/users/batch', [
            'users' => [
                ['email' => $emails[0]],
                ['email' => $emails[0]],
                ['email' => $emails[1]],
            ],
        ]);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $users = [
            $this->userWithEmail($emails[0]),
            $this->userWithEmail($emails[1]),
        ];
        $this->expectations->expectBatchFindByEmail([$emails[0], $emails[1]], $users);
        $this->expectations->expectBatchDeleteAndEvents($users);

        ($this->listener)($event);
    }

    private function createEmailExtractor(): SchemathesisEmailExtractor
    {
        $serializer = new Serializer([], [new JsonEncoder()]);
        $decoder = new SchemathesisPayloadDecoder($serializer);
        $singleExtractor = new SchemathesisSingleUserEmailExtractor();
        $batchExtractor = new SchemathesisBatchUsersEmailExtractor();

        return new SchemathesisEmailExtractor(
            $this->evaluator,
            $decoder,
            $singleExtractor,
            $batchExtractor
        );
    }

    private function createExpectations(): SchemathesisCleanupListenerTestExpectations
    {
        $eventId = $this->faker->uuid();

        return new SchemathesisCleanupListenerTestExpectations(
            $this,
            $this->repository,
            $this->eventBus,
            $this->uuidFactory,
            $this->eventFactory,
            $eventId,
            function (array $expectedCalls, $returnValue = null): callable {
                return $this->expectSequential($expectedCalls, $returnValue);
            }
        );
    }

    private function createListener(): SchemathesisCleanupListener
    {
        $cache = $this->createMock(TagAwareCacheInterface::class);
        $cache->method('invalidateTags')->willReturn(true);

        $cacheKeyBuilder = $this->createMock(CacheKeyBuilder::class);
        $cacheKeyBuilder
            ->method('hashEmail')
            ->willReturnCallback(
                static fn (string $email): string => md5($email)
            );

        return new SchemathesisCleanupListener(
            $this->repository,
            $this->eventBus,
            $this->uuidFactory,
            $this->eventFactory,
            $this->evaluator,
            $this->emailExtractor,
            $cache,
            $cacheKeyBuilder
        );
    }

    private function userWithEmail(string $email): UserInterface
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn($this->faker->uuid());
        $user->method('getEmail')->willReturn($email);

        return $user;
    }

    private function terminateEvent(Request $request, int $status): TerminateEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $response = new Response(status: $status);

        return new TerminateEvent($kernel, $request, $response);
    }

    private function schemathesisRequest(
        string $path,
        string|array|null $payload,
        string $headerValue = 'cleanup-users'
    ): Request {
        $content = null;
        if ($payload !== null) {
            $content = is_string($payload)
                ? $payload
                : json_encode($payload, JSON_THROW_ON_ERROR);
        }

        return Request::create(
            $path,
            Request::METHOD_POST,
            server: ['HTTP_X_SCHEMATHESIS_TEST' => $headerValue],
            content: $content
        );
    }
}
