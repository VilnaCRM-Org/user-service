<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\EventListener;

use Symfony\Component\HttpFoundation\Response;

final class SchemathesisCleanupListenerCleanupTest extends SchemathesisCleanupListenerTestCase
{
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
}
