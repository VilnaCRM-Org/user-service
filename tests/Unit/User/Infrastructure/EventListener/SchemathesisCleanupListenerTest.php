<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\EventListener;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\EventListener\SchemathesisCleanupListener;
use App\User\Infrastructure\Schemathesis\SchemathesisBatchUsersEmailExtractor;
use App\User\Infrastructure\Schemathesis\SchemathesisCleanupEvaluator;
use App\User\Infrastructure\Schemathesis\SchemathesisEmailExtractor;
use App\User\Infrastructure\Schemathesis\SchemathesisPayloadDecoder;
use App\User\Infrastructure\Schemathesis\SchemathesisSingleUserEmailExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class SchemathesisCleanupListenerTest extends UnitTestCase
{
    private SchemathesisCleanupEvaluator $evaluator;
    private SchemathesisEmailExtractor $emailExtractor;

    #[\Override]
    protected function setUp(): void
    {
        $this->evaluator = new SchemathesisCleanupEvaluator();
        $decoder = new SchemathesisPayloadDecoder();
        $singleExtractor = new SchemathesisSingleUserEmailExtractor();
        $batchExtractor = new SchemathesisBatchUsersEmailExtractor();
        $this->emailExtractor = new SchemathesisEmailExtractor(
            $this->evaluator,
            $decoder,
            $singleExtractor,
            $batchExtractor
        );
    }

    public function testListenerRemovesCreatedUserAfterSchemathesisRequest(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = new SchemathesisCleanupListener($repository, $this->evaluator, $this->emailExtractor);

        $request = Request::create(
            '/api/users',
            Request::METHOD_POST,
            server: ['HTTP_X_SCHEMATHESIS_TEST' => 'cleanup-users'],
            content: json_encode([
                'email' => 'create-user@example.com',
                'initials' => 'CreateUser',
                'password' => 'Password1!',
            ], JSON_THROW_ON_ERROR)
        );

        $response = new Response(status: Response::HTTP_CREATED);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new TerminateEvent($kernel, $request, $response);

        $user = $this->createMock(UserInterface::class);

        $repository->expects($this->once())
            ->method('findByEmail')
            ->with('create-user@example.com')
            ->willReturn($user);

        $repository->expects($this->once())
            ->method('delete')
            ->with($user);

        $listener($event);
    }

    public function testListenerSkipsWhenHeaderMissing(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = new SchemathesisCleanupListener($repository, $this->evaluator, $this->emailExtractor);

        $request = Request::create(
            '/api/users',
            Request::METHOD_POST,
            content: json_encode(['email' => 'create-user@example.com'], JSON_THROW_ON_ERROR)
        );

        $response = new Response(status: Response::HTTP_CREATED);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new TerminateEvent($kernel, $request, $response);

        $repository->expects($this->never())->method('findByEmail');
        $repository->expects($this->never())->method('delete');

        $listener($event);
    }

    public function testListenerRemovesBatchUsers(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = new SchemathesisCleanupListener($repository, $this->evaluator, $this->emailExtractor);

        $request = Request::create(
            '/api/users/batch',
            Request::METHOD_POST,
            server: ['HTTP_X_SCHEMATHESIS_TEST' => 'cleanup-users'],
            content: json_encode([
                'users' => [
                    ['email' => 'batch-user-one@example.com'],
                    ['email' => 'batch-user-two@example.com'],
                ],
            ], JSON_THROW_ON_ERROR)
        );

        $response = new Response(status: Response::HTTP_CREATED);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new TerminateEvent($kernel, $request, $response);

        $firstUser = $this->createMock(UserInterface::class);
        $secondUser = $this->createMock(UserInterface::class);

        $repository->expects($this->exactly(2))
            ->method('findByEmail')
            ->willReturnCallback(
                $this->expectSequential(
                    [['batch-user-one@example.com'], ['batch-user-two@example.com']],
                    [$firstUser, $secondUser]
                )
            );

        $repository->expects($this->exactly(2))
            ->method('delete')
            ->willReturnCallback(
                $this->expectSequential(
                    [[$firstUser], [$secondUser]]
                )
            );

        $listener($event);
    }

    public function testListenerSkipsWhenResponseStatusIsNotSuccessful(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = new SchemathesisCleanupListener($repository, $this->evaluator, $this->emailExtractor);

        $request = Request::create(
            '/api/users',
            Request::METHOD_POST,
            server: ['HTTP_X_SCHEMATHESIS_TEST' => 'cleanup-users'],
            content: json_encode(['email' => 'create-user@example.com'], JSON_THROW_ON_ERROR)
        );

        $response = new Response(status: Response::HTTP_BAD_REQUEST);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new TerminateEvent($kernel, $request, $response);

        $repository->expects($this->never())->method('findByEmail');
        $repository->expects($this->never())->method('delete');

        $listener($event);
    }

    public function testListenerSkipsWhenBodyIsEmpty(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = new SchemathesisCleanupListener($repository, $this->evaluator, $this->emailExtractor);

        $request = Request::create(
            '/api/users',
            Request::METHOD_POST,
            server: ['HTTP_X_SCHEMATHESIS_TEST' => 'cleanup-users']
        );

        $response = new Response(status: Response::HTTP_CREATED);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new TerminateEvent($kernel, $request, $response);

        $repository->expects($this->never())->method('findByEmail');
        $repository->expects($this->never())->method('delete');

        $listener($event);
    }

    public function testListenerSkipsWhenUserNotFound(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = new SchemathesisCleanupListener($repository, $this->evaluator, $this->emailExtractor);

        $request = Request::create(
            '/api/users',
            Request::METHOD_POST,
            server: ['HTTP_X_SCHEMATHESIS_TEST' => 'cleanup-users'],
            content: json_encode(['email' => 'create-user@example.com'], JSON_THROW_ON_ERROR)
        );

        $response = new Response(status: Response::HTTP_CREATED);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new TerminateEvent($kernel, $request, $response);

        $repository->expects($this->once())
            ->method('findByEmail')
            ->with('create-user@example.com')
            ->willReturn(null);

        $repository->expects($this->never())->method('delete');

        $listener($event);
    }

    public function testListenerContinuesWhenUserMissing(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = new SchemathesisCleanupListener($repository, $this->evaluator, $this->emailExtractor);

        $request = Request::create(
            '/api/users/batch',
            Request::METHOD_POST,
            server: ['HTTP_X_SCHEMATHESIS_TEST' => 'cleanup-users'],
            content: json_encode([
                'users' => [
                    ['email' => 'missing@example.com'],
                    ['email' => 'present@example.com'],
                ],
            ], JSON_THROW_ON_ERROR)
        );

        $response = new Response(status: Response::HTTP_CREATED);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new TerminateEvent($kernel, $request, $response);

        $existingUser = $this->createMock(UserInterface::class);

        $repository->expects($this->exactly(2))
            ->method('findByEmail')
            ->willReturnCallback(
                $this->expectSequential(
                    [['missing@example.com'], ['present@example.com']],
                    [null, $existingUser]
                )
            );

        $repository->expects($this->once())
            ->method('delete')
            ->with($existingUser);

        $listener($event);
    }

    public function testListenerSkipsWhenJsonIsMalformed(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = new SchemathesisCleanupListener($repository, $this->evaluator, $this->emailExtractor);

        $request = Request::create(
            '/api/users',
            Request::METHOD_POST,
            server: ['HTTP_X_SCHEMATHESIS_TEST' => 'cleanup-users'],
            content: '{invalid'
        );

        $response = new Response(status: Response::HTTP_CREATED);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new TerminateEvent($kernel, $request, $response);

        $repository->expects($this->never())->method('findByEmail');
        $repository->expects($this->never())->method('delete');

        $listener($event);
    }

    public function testListenerSkipsWhenPayloadIsNotArray(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = new SchemathesisCleanupListener($repository, $this->evaluator, $this->emailExtractor);

        $request = Request::create(
            '/api/users',
            Request::METHOD_POST,
            server: ['HTTP_X_SCHEMATHESIS_TEST' => 'cleanup-users'],
            content: json_encode('string', JSON_THROW_ON_ERROR)
        );

        $response = new Response(status: Response::HTTP_CREATED);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new TerminateEvent($kernel, $request, $response);

        $repository->expects($this->never())->method('findByEmail');
        $repository->expects($this->never())->method('delete');

        $listener($event);
    }

    public function testListenerSkipsWhenBatchUsersNotArray(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = new SchemathesisCleanupListener($repository, $this->evaluator, $this->emailExtractor);

        $request = Request::create(
            '/api/users/batch',
            Request::METHOD_POST,
            server: ['HTTP_X_SCHEMATHESIS_TEST' => 'cleanup-users'],
            content: json_encode(['users' => 'string'], JSON_THROW_ON_ERROR)
        );

        $response = new Response(status: Response::HTTP_CREATED);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new TerminateEvent($kernel, $request, $response);

        $repository->expects($this->never())->method('findByEmail');
        $repository->expects($this->never())->method('delete');

        $listener($event);
    }

    public function testListenerSkipsWhenEmailIsNotString(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = new SchemathesisCleanupListener($repository, $this->evaluator, $this->emailExtractor);

        $request = Request::create(
            '/api/users',
            Request::METHOD_POST,
            server: ['HTTP_X_SCHEMATHESIS_TEST' => 'cleanup-users'],
            content: json_encode(['email' => ['value']], JSON_THROW_ON_ERROR)
        );

        $response = new Response(status: Response::HTTP_CREATED);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new TerminateEvent($kernel, $request, $response);

        $repository->expects($this->never())->method('findByEmail');
        $repository->expects($this->never())->method('delete');

        $listener($event);
    }

    public function testListenerSkipsInvalidEntriesInsideBatch(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = new SchemathesisCleanupListener($repository, $this->evaluator, $this->emailExtractor);

        $request = Request::create(
            '/api/users/batch',
            Request::METHOD_POST,
            server: ['HTTP_X_SCHEMATHESIS_TEST' => 'cleanup-users'],
            content: json_encode([
                'users' => [
                    ['email' => 'batch-user-one@example.com'],
                    'invalid',
                    ['email' => ['not-string']],
                    ['email' => 'batch-user-two@example.com'],
                ],
            ], JSON_THROW_ON_ERROR)
        );

        $response = new Response(status: Response::HTTP_CREATED);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new TerminateEvent($kernel, $request, $response);

        $firstUser = $this->createMock(UserInterface::class);
        $secondUser = $this->createMock(UserInterface::class);

        $repository->expects($this->exactly(2))
            ->method('findByEmail')
            ->willReturnCallback(
                $this->expectSequential(
                    [['batch-user-one@example.com'], ['batch-user-two@example.com']],
                    [$firstUser, $secondUser]
                )
            );

        $repository->expects($this->exactly(2))
            ->method('delete')
            ->willReturnCallback(
                $this->expectSequential(
                    [[$firstUser], [$secondUser]]
                )
            );

        $listener($event);
    }

    public function testListenerSkipsWhenPathIsNotHandled(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = new SchemathesisCleanupListener($repository, $this->evaluator, $this->emailExtractor);

        $request = Request::create(
            '/api/health',
            Request::METHOD_POST,
            server: ['HTTP_X_SCHEMATHESIS_TEST' => 'cleanup-users'],
            content: json_encode(['email' => 'create-user@example.com'], JSON_THROW_ON_ERROR)
        );

        $response = new Response(status: Response::HTTP_CREATED);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new TerminateEvent($kernel, $request, $response);

        $repository->expects($this->never())->method('findByEmail');
        $repository->expects($this->never())->method('delete');

        $listener($event);
    }

    public function testListenerDeletesEachEmailOnlyOnce(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = new SchemathesisCleanupListener($repository, $this->evaluator, $this->emailExtractor);

        $request = Request::create(
            '/api/users/batch',
            Request::METHOD_POST,
            server: ['HTTP_X_SCHEMATHESIS_TEST' => 'cleanup-users'],
            content: json_encode([
                'users' => [
                    ['email' => 'duplicate@example.com'],
                    ['email' => 'duplicate@example.com'],
                    ['email' => 'unique@example.com'],
                ],
            ], JSON_THROW_ON_ERROR)
        );

        $response = new Response(status: Response::HTTP_CREATED);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new TerminateEvent($kernel, $request, $response);

        $duplicateUser = $this->createMock(UserInterface::class);
        $uniqueUser = $this->createMock(UserInterface::class);

        $repository->expects($this->exactly(2))
            ->method('findByEmail')
            ->willReturnCallback(
                $this->expectSequential(
                    [['duplicate@example.com'], ['unique@example.com']],
                    [$duplicateUser, $uniqueUser]
                )
            );

        $repository->expects($this->exactly(2))
            ->method('delete')
            ->willReturnCallback(
                $this->expectSequential(
                    [[$duplicateUser], [$uniqueUser]]
                )
            );

        $listener($event);
    }
}
