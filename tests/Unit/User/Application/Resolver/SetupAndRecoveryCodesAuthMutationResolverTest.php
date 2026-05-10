<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\RegenerateRecoveryCodesCommand;
use App\User\Application\Command\SetupTwoFactorCommand;
use App\User\Application\DTO\AuthPayload;
use App\User\Application\DTO\RegenerateRecoveryCodesCommandResponse;
use App\User\Application\DTO\SetupTwoFactorCommandResponse;
use App\User\Application\Factory\RegenerateRecoveryCodesCommandFactoryInterface;
use App\User\Application\Factory\SetupTwoFactorCommandFactoryInterface;
use App\User\Application\Resolver\RegenerateRecoveryCodesAuthMutationResolver;
use App\User\Application\Resolver\SetupTwoFactorAuthMutationResolver;

final class SetupAndRecoveryCodesAuthMutationResolverTest extends AuthMutationResolverTestCase
{
    public function testSetupResolverDispatchesCommandAndBuildsPayload(): void
    {
        $email = $this->faker->email();
        $command = new SetupTwoFactorCommand($email);
        $commandResponse = $this->setupResponse();
        $commandBus = $this->createMock(CommandBusInterface::class);
        $commandFactory = $this->createMock(
            SetupTwoFactorCommandFactoryInterface::class
        );
        $resolver = new SetupTwoFactorAuthMutationResolver(
            $commandBus,
            $this->authPayloadFactory(),
            $this->currentUserIdentityResolver($email, '', $this->faker->uuid()),
            $commandFactory
        );

        $this->expectSetupResolver($commandBus, $commandFactory, $command, $email, $commandResponse);
        $result = $resolver->__invoke(null, []);

        $this->assertSetupPayload($result, $commandResponse);
    }

    public function testRecoveryCodesResolverDispatchesCommandAndBuildsPayload(): void
    {
        $email = $this->faker->email();
        $sessionId = $this->faker->uuid();
        $command = new RegenerateRecoveryCodesCommand($email, $sessionId);
        $commandResponse = $this->recoveryCodesResponse();
        $commandBus = $this->createMock(CommandBusInterface::class);
        $commandFactory = $this->createMock(
            RegenerateRecoveryCodesCommandFactoryInterface::class
        );
        $resolver = new RegenerateRecoveryCodesAuthMutationResolver(
            $commandBus,
            $this->authPayloadFactory(),
            $this->currentUserIdentityResolver($email, $sessionId, $this->faker->uuid()),
            $commandFactory
        );

        $this->expectRecoveryResolver($commandBus, $commandFactory, $command, $commandResponse);
        $result = $resolver->__invoke(null, []);

        $this->assertRecoveryCodesPayload($result, $commandResponse);
    }

    private function expectSetupResolver(
        CommandBusInterface $commandBus,
        SetupTwoFactorCommandFactoryInterface $commandFactory,
        SetupTwoFactorCommand $command,
        string $email,
        SetupTwoFactorCommandResponse $response,
    ): void {
        $commandFactory->expects($this->once())
            ->method('create')
            ->with($email)
            ->willReturn($command);
        $commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command)
            ->willReturn($response);
    }

    private function expectRecoveryResolver(
        CommandBusInterface $commandBus,
        RegenerateRecoveryCodesCommandFactoryInterface $commandFactory,
        RegenerateRecoveryCodesCommand $command,
        RegenerateRecoveryCodesCommandResponse $response,
    ): void {
        $commandFactory->expects($this->once())
            ->method('create')
            ->with($command->userEmail, $command->currentSessionId)
            ->willReturn($command);
        $commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command)
            ->willReturn($response);
    }

    private function setupResponse(): SetupTwoFactorCommandResponse
    {
        return new SetupTwoFactorCommandResponse(
            'otpauth://totp/test',
            strtoupper($this->faker->lexify('????????????????'))
        );
    }

    private function recoveryCodesResponse(): RegenerateRecoveryCodesCommandResponse
    {
        return new RegenerateRecoveryCodesCommandResponse(
            [$this->faker->sha1(), $this->faker->sha1()]
        );
    }

    private function assertSetupPayload(
        AuthPayload $payload,
        SetupTwoFactorCommandResponse $response,
    ): void {
        $this->assertSame('auth-setup-two-factor', $payload->getId());
        $this->assertSame($response->getOtpauthUri(), $payload->getOtpauthUri());
        $this->assertSame($response->getSecret(), $payload->getSecret());
    }

    private function assertRecoveryCodesPayload(
        AuthPayload $payload,
        RegenerateRecoveryCodesCommandResponse $response,
    ): void {
        $this->assertSame('auth-regenerate-recovery-codes', $payload->getId());
        $this->assertSame($response->getRecoveryCodes(), $payload->getRecoveryCodes());
    }
}
