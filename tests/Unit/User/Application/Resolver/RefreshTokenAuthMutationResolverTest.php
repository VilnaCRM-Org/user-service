<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Application\Bus\Guard\CommandResponseTypeGuard;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\RefreshTokenCommand;
use App\User\Application\DTO\AuthPayload;
use App\User\Application\DTO\RefreshTokenCommandResponse;
use App\User\Application\DTO\RefreshTokenDto;
use App\User\Application\Factory\RefreshTokenCommandFactoryInterface;
use App\User\Application\Resolver\RefreshTokenAuthMutationResolver;
use App\User\Application\Validator\MutationInputValidator;

final class RefreshTokenAuthMutationResolverTest extends AuthMutationResolverTestCase
{
    public function testInvokeDispatchesCommandAndBuildsPayload(): void
    {
        $refreshToken = $this->faker->sha256();
        $command = new RefreshTokenCommand($refreshToken);
        $commandResponse = $this->response();
        $validator = $this->createMock(MutationInputValidator::class);
        $commandBus = $this->createMock(CommandBusInterface::class);
        $commandFactory = $this->createMock(
            RefreshTokenCommandFactoryInterface::class
        );
        $resolver = new RefreshTokenAuthMutationResolver(
            $validator,
            $commandBus,
            new CommandResponseTypeGuard(),
            $this->authPayloadFactory(),
            $commandFactory
        );

        $this->expectResolver($validator, $commandBus, $commandFactory, $command, $commandResponse);
        $result = $resolver->__invoke(null, $this->context($refreshToken));

        $this->assertPayload($result, $commandResponse);
    }

    private function expectResolver(
        MutationInputValidator $validator,
        CommandBusInterface $commandBus,
        RefreshTokenCommandFactoryInterface $commandFactory,
        RefreshTokenCommand $command,
        RefreshTokenCommandResponse $response,
    ): void {
        $validator->expects($this->once())
            ->method('validate')
            ->with($this->callback(static fn (object $dto): bool => $dto instanceof RefreshTokenDto
                && $dto->refreshTokenValue() === $command->refreshToken));
        $commandFactory->expects($this->once())
            ->method('create')
            ->with($command->refreshToken)
            ->willReturn($command);
        $commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command)
            ->willReturn($response);
    }

    /**
     * @return array<string, array<string, array<string, string>>>
     */
    private function context(string $refreshToken): array
    {
        return ['args' => ['input' => ['refreshToken' => $refreshToken]]];
    }

    private function response(): RefreshTokenCommandResponse
    {
        return new RefreshTokenCommandResponse(
            $this->faker->sha256(),
            $this->faker->sha256()
        );
    }

    private function assertPayload(
        AuthPayload $payload,
        RefreshTokenCommandResponse $response,
    ): void {
        $this->assertSame('auth-refresh-token', $payload->getId());
        $this->assertSame($response->getAccessToken(), $payload->getAccessToken());
        $this->assertSame($response->getRefreshToken(), $payload->getRefreshToken());
    }
}
