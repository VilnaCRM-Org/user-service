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

abstract class SchemathesisCleanupListenerTestCase extends UnitTestCase
{
    protected SchemathesisCleanupEvaluator $evaluator;
    protected SchemathesisEmailExtractor $emailExtractor;
    protected SchemathesisCleanupListener $listener;
    protected UserRepositoryInterface $repository;
    protected EventBusInterface $eventBus;
    protected UuidFactory $uuidFactory;
    protected UserDeletedEventFactoryInterface $eventFactory;
    protected SchemathesisCleanupListenerTestExpectations $expectations;

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

    protected function createEmailExtractor(): SchemathesisEmailExtractor
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

    protected function createExpectations(): SchemathesisCleanupListenerTestExpectations
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

    protected function createListener(): SchemathesisCleanupListener
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

    protected function userWithEmail(string $email): UserInterface
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn($this->faker->uuid());
        $user->method('getEmail')->willReturn($email);

        return $user;
    }

    protected function terminateEvent(Request $request, int $status): TerminateEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $response = new Response(status: $status);

        return new TerminateEvent($kernel, $request, $response);
    }

    protected function schemathesisRequest(
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
