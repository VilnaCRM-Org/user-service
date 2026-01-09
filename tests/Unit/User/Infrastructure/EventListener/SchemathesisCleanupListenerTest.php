<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\EventListener;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\UserInterface;
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

final class SchemathesisCleanupListenerTest extends UnitTestCase
{
    private SchemathesisCleanupEvaluator $evaluator;
    private SchemathesisEmailExtractor $emailExtractor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->evaluator = new SchemathesisCleanupEvaluator();
        $serializer = new Serializer([], [new JsonEncoder()]);
        $decoder = new SchemathesisPayloadDecoder($serializer);
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
        $email = $this->faker->email();
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = $this->createListener($repository);

        $request = $this->schemathesisRequest('/api/users', [
            'email' => $email,
            'initials' => $this->faker->lexify('????????'),
            'password' => $this->faker->password(12),
        ]);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $user = $this->createMock(UserInterface::class);
        $repository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);
        $repository->expects($this->once())
            ->method('delete')
            ->with($user);

        $listener($event);
    }

    public function testListenerSkipsWhenHeaderMissing(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = $this->createListener($repository);

        $request = Request::create(
            '/api/users',
            Request::METHOD_POST,
            content: $this->content(['email' => $this->faker->email()])
        );
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectNoRepositoryCalls($repository);

        $listener($event);
    }

    public function testListenerRemovesBatchUsers(): void
    {
        $emails = [$this->faker->email(), $this->faker->email()];
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = $this->createListener($repository);

        $request = $this->schemathesisRequest('/api/users/batch', [
            'users' => [
                ['email' => $emails[0]],
                ['email' => $emails[1]],
            ],
        ]);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $users = [$this->createMock(UserInterface::class), $this->createMock(UserInterface::class)];
        $this->expectBatchFindByEmail($repository, $emails, [$users[0], $users[1]]);
        $this->expectBatchDelete($repository, [$users[0], $users[1]]);

        $listener($event);
    }

    public function testListenerSkipsWhenResponseStatusIsNotSuccessful(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = $this->createListener($repository);

        $request = $this->schemathesisRequest('/api/users', ['email' => $this->faker->email()]);
        $event = $this->terminateEvent($request, Response::HTTP_BAD_REQUEST);

        $this->expectNoRepositoryCalls($repository);

        $listener($event);
    }

    public function testListenerSkipsWhenBodyIsEmpty(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = $this->createListener($repository);

        $request = $this->schemathesisRequest('/api/users', null);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectNoRepositoryCalls($repository);

        $listener($event);
    }

    public function testListenerSkipsWhenUserNotFound(): void
    {
        $email = $this->faker->email();
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = $this->createListener($repository);

        $request = $this->schemathesisRequest('/api/users', ['email' => $email]);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $repository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);
        $repository->expects($this->never())->method('delete');

        $listener($event);
    }

    public function testListenerContinuesWhenUserMissing(): void
    {
        $emails = [$this->faker->email(), $this->faker->email()];
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = $this->createListener($repository);

        $request = $this->schemathesisRequest('/api/users/batch', [
            'users' => [
                ['email' => $emails[0]],
                ['email' => $emails[1]],
            ],
        ]);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $existingUser = $this->createMock(UserInterface::class);
        $this->expectBatchFindByEmail($repository, $emails, [null, $existingUser]);

        $repository->expects($this->once())
            ->method('delete')
            ->with($existingUser);

        $listener($event);
    }

    public function testListenerSkipsWhenJsonIsMalformed(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = $this->createListener($repository);

        $request = $this->schemathesisRequest('/api/users', '{invalid');
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectNoRepositoryCalls($repository);

        $listener($event);
    }

    public function testListenerSkipsWhenPayloadIsNotArray(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = $this->createListener($repository);

        $payload = json_encode('string', JSON_THROW_ON_ERROR);
        $request = $this->schemathesisRequest('/api/users', $payload);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectNoRepositoryCalls($repository);

        $listener($event);
    }

    public function testListenerSkipsWhenBatchUsersNotArray(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = $this->createListener($repository);

        $request = $this->schemathesisRequest('/api/users/batch', ['users' => 'string']);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectNoRepositoryCalls($repository);

        $listener($event);
    }

    public function testListenerSkipsWhenBatchEntriesKeyMissing(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = $this->createListener($repository);

        $request = $this->schemathesisRequest('/api/users/batch', ['something' => 'else']);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectNoRepositoryCalls($repository);

        $listener($event);
    }

    public function testListenerSkipsWhenBatchEntriesNotArray(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = $this->createListener($repository);

        $request = $this->schemathesisRequest('/api/users/batch', ['users' => ['string']]);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectNoRepositoryCalls($repository);

        $listener($event);
    }

    public function testListenerSkipsWhenHeaderValueIsNotCleanupUsers(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = $this->createListener($repository);

        $request = $this->schemathesisRequest(
            '/api/users',
            ['email' => $this->faker->email()],
            $this->faker->word()
        );
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectNoRepositoryCalls($repository);

        $listener($event);
    }

    public function testListenerSkipsWhenEmailIsMissing(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = $this->createListener($repository);

        $payload = ['initials' => $this->faker->lexify('????????')];
        $request = $this->schemathesisRequest('/api/users', $payload);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectNoRepositoryCalls($repository);

        $listener($event);
    }

    public function testListenerSkipsWhenEmailIsNotString(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = $this->createListener($repository);

        $request = $this->schemathesisRequest('/api/users', ['email' => ['value']]);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectNoRepositoryCalls($repository);

        $listener($event);
    }

    public function testListenerSkipsInvalidEntriesInsideBatch(): void
    {
        $emails = [$this->faker->email(), $this->faker->email()];
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = $this->createListener($repository);

        $request = $this->schemathesisRequest('/api/users/batch', [
            'users' => [
                ['email' => $emails[0]],
                $this->faker->word(),
                ['email' => ['not-string']],
                ['email' => $emails[1]],
            ],
        ]);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $users = [$this->createMock(UserInterface::class), $this->createMock(UserInterface::class)];
        $this->expectBatchFindByEmail($repository, $emails, [$users[0], $users[1]]);
        $this->expectBatchDelete($repository, [$users[0], $users[1]]);

        $listener($event);
    }

    public function testListenerSkipsWhenPathIsNotHandled(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = $this->createListener($repository);

        $request = $this->schemathesisRequest('/api/health', ['email' => $this->faker->email()]);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectNoRepositoryCalls($repository);

        $listener($event);
    }

    public function testListenerDeletesEachEmailOnlyOnce(): void
    {
        $emails = [$this->faker->email(), $this->faker->email()];
        $repository = $this->createMock(UserRepositoryInterface::class);
        $listener = $this->createListener($repository);

        $request = $this->schemathesisRequest('/api/users/batch', [
            'users' => [
                ['email' => $emails[0]],
                ['email' => $emails[0]],
                ['email' => $emails[1]],
            ],
        ]);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $users = [$this->createMock(UserInterface::class), $this->createMock(UserInterface::class)];
        $this->expectBatchFindByEmail(
            $repository,
            [$emails[0], $emails[1]],
            [$users[0], $users[1]]
        );
        $this->expectBatchDelete($repository, [$users[0], $users[1]]);

        $listener($event);
    }

    private function createListener(
        UserRepositoryInterface $repository
    ): SchemathesisCleanupListener {
        return new SchemathesisCleanupListener(
            $repository,
            $this->evaluator,
            $this->emailExtractor
        );
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
        return Request::create(
            $path,
            Request::METHOD_POST,
            server: ['HTTP_X_SCHEMATHESIS_TEST' => $headerValue],
            content: $this->content($payload)
        );
    }

    private function content(string|array|null $payload): ?string
    {
        if ($payload === null) {
            return null;
        }

        if (is_string($payload)) {
            return $payload;
        }

        return json_encode($payload, JSON_THROW_ON_ERROR);
    }

    private function expectNoRepositoryCalls(UserRepositoryInterface $repository): void
    {
        $repository->expects($this->never())->method('findByEmail');
        $repository->expects($this->never())->method('delete');
    }

    /**
     * @param array<int, string> $emails
     * @param array<int, UserInterface|null> $users
     */
    private function expectBatchFindByEmail(
        UserRepositoryInterface $repository,
        array $emails,
        array $users
    ): void {
        $repository->expects($this->exactly(count($emails)))
            ->method('findByEmail')
            ->willReturnCallback(
                $this->expectSequential(
                    array_map(static fn (string $email): array => [$email], $emails),
                    $users
                )
            );
    }

    /**
     * @param array<int, UserInterface> $users
     */
    private function expectBatchDelete(UserRepositoryInterface $repository, array $users): void
    {
        $repository->expects($this->exactly(count($users)))
            ->method('delete')
            ->willReturnCallback(
                $this->expectSequential(
                    array_map(static fn (UserInterface $user): array => [$user], $users)
                )
            );
    }
}
