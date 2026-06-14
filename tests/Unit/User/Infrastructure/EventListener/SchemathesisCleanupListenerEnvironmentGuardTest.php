<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\EventListener;

use App\User\Infrastructure\EventListener\SchemathesisCleanupListener;
use Symfony\Component\HttpFoundation\Response;

final class SchemathesisCleanupListenerEnvironmentGuardTest extends
    SchemathesisCleanupListenerTestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function nonSchemathesisEnvironments(): iterable
    {
        yield 'prod' => ['prod'];
        yield 'dev' => ['dev'];
        yield 'test' => ['test'];
        yield 'load_test' => ['load_test'];
    }

    /**
     * @dataProvider nonSchemathesisEnvironments
     */
    public function testListenerNeverDeletesOutsideSchemathesisEnvironment(
        string $appEnv
    ): void {
        $listener = $this->createListener($appEnv);

        $request = $this->schemathesisRequest('/api/users/batch', [
            'users' => [
                ['email' => $this->faker->email()],
                ['email' => $this->faker->email()],
            ],
        ]);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectations->expectNoRepositoryCalls();

        $listener($event);
    }

    public function testListenerDeletesWhenEnvironmentIsSchemathesis(): void
    {
        $email = $this->faker->email();
        $listener = $this->createListener(
            SchemathesisCleanupListener::ENVIRONMENT
        );

        $request = $this->schemathesisRequest('/api/users', ['email' => $email]);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $user = $this->userWithEmail($email);
        $this->repository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);
        $this->expectations->expectBatchDelete([$user]);

        $listener($event);
    }
}
