<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\EventListener;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Converter\SchemathesisPayloadConverter;
use App\User\Infrastructure\EventListener\SchemathesisCleanupListener;
use App\User\Infrastructure\Resolver\SchemathesisBatchUsersEmailResolver;
use App\User\Infrastructure\Resolver\SchemathesisCleanupResolver;
use App\User\Infrastructure\Resolver\SchemathesisEmailResolver;
use App\User\Infrastructure\Resolver\SchemathesisSingleUserEmailResolver;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

abstract class SchemathesisCleanupListenerTestCase extends UnitTestCase
{
    protected SchemathesisCleanupResolver $schemathesisCleanupMatcher;
    protected SchemathesisEmailResolver $emailExtractor;
    protected SchemathesisCleanupListener $listener;
    protected UserRepositoryInterface $repository;
    protected SchemathesisCleanupListenerTestExpectations $expectations;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->schemathesisCleanupMatcher = new SchemathesisCleanupResolver();
        $this->emailExtractor = $this->createEmailExtractor();
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->listener = $this->createListener();
        $this->expectations = $this->createExpectations();
    }

    protected function createEmailExtractor(): SchemathesisEmailResolver
    {
        $serializer = new Serializer([], [new JsonEncoder()]);
        $payloadConverter = new SchemathesisPayloadConverter($serializer);
        $singleExtractor = new SchemathesisSingleUserEmailResolver();
        $batchExtractor = new SchemathesisBatchUsersEmailResolver();

        return new SchemathesisEmailResolver(
            $this->schemathesisCleanupMatcher,
            $payloadConverter,
            $singleExtractor,
            $batchExtractor
        );
    }

    protected function createExpectations(): SchemathesisCleanupListenerTestExpectations
    {
        return new SchemathesisCleanupListenerTestExpectations(
            $this,
            $this->repository,
            /**
             * @psalm-return \Closure(...mixed):(array|null|object|scalar)
             */
            function (array $expectedCalls, $returnValue = null): \Closure {
                return $this->expectSequential($expectedCalls, $returnValue);
            }
        );
    }

    protected function createListener(
        string $appEnv = SchemathesisCleanupListener::ENVIRONMENT
    ): SchemathesisCleanupListener {
        return new SchemathesisCleanupListener(
            $appEnv,
            $this->repository,
            $this->schemathesisCleanupMatcher,
            $this->emailExtractor
        );
    }

    protected function userWithEmail(
        string $email
    ): MockObject&UserInterface {
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
