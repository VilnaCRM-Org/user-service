<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\SignOutAllCommand;
use App\User\Application\Command\SignOutCommand;
use App\User\Application\Factory\SignOutAllCommandFactoryInterface;
use App\User\Application\Factory\SignOutCommandFactoryInterface;
use App\User\Application\Resolver\SignOutAllAuthMutationResolver;
use App\User\Application\Resolver\SignOutAuthMutationResolver;

final class SignOutAuthMutationResolverTest extends AuthMutationResolverTestCase
{
    public function testSignOutResolverDispatchesCommandAndReturnsSuccessPayload(): void
    {
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $command = new SignOutCommand($sessionId, $userId);
        $commandBus = $this->createMock(CommandBusInterface::class);
        $commandFactory = $this->createMock(SignOutCommandFactoryInterface::class);
        $resolver = new SignOutAuthMutationResolver(
            $commandBus,
            $this->authPayloadFactory(),
            $this->currentUserIdentityResolver($this->faker->email(), $sessionId, $userId),
            $commandFactory
        );

        $this->expectSignOutResolver($commandBus, $commandFactory, $command, $sessionId, $userId);
        $payload = $resolver->__invoke(null, []);

        $this->assertSuccessPayload($payload);
    }

    public function testSignOutAllResolverDispatchesCommandAndReturnsSuccessPayload(): void
    {
        $userId = $this->faker->uuid();
        $command = new SignOutAllCommand($userId);
        $commandBus = $this->createMock(CommandBusInterface::class);
        $commandFactory = $this->createMock(
            SignOutAllCommandFactoryInterface::class
        );
        $resolver = new SignOutAllAuthMutationResolver(
            $commandBus,
            $this->authPayloadFactory(),
            $this->currentUserIdentityResolver($this->faker->email(), '', $userId),
            $commandFactory
        );

        $this->expectSignOutAllResolver($commandBus, $commandFactory, $command, $userId);
        $payload = $resolver->__invoke(null, []);

        $this->assertSuccessPayload($payload);
    }

    private function expectSignOutResolver(
        CommandBusInterface $commandBus,
        SignOutCommandFactoryInterface $commandFactory,
        SignOutCommand $command,
        string $sessionId,
        string $userId,
    ): void {
        $commandFactory->expects($this->once())
            ->method('create')
            ->with($sessionId, $userId)
            ->willReturn($command);
        $commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);
    }

    private function expectSignOutAllResolver(
        CommandBusInterface $commandBus,
        SignOutAllCommandFactoryInterface $commandFactory,
        SignOutAllCommand $command,
        string $userId,
    ): void {
        $commandFactory->expects($this->once())
            ->method('create')
            ->with($userId)
            ->willReturn($command);
        $commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);
    }

    private function assertSuccessPayload(object $payload): void
    {
        self::assertSame('auth-success', $payload->getId());
        self::assertTrue($payload->isSuccess());
    }
}
