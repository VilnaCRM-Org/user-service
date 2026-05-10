<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Application\Bus\Guard\CommandResponseTypeGuard;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RefreshTokenCommand;
use App\User\Application\DTO\RefreshTokenCommandResponse;
use App\User\Application\DTO\RefreshTokenDto;
use App\User\Application\Factory\RefreshTokenCommandFactory;
use App\User\Application\Processor\RefreshTokenProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;

final class RefreshTokenProcessorTest extends UnitTestCase
{
    private CommandBusInterface&MockObject $commandBus;
    private Operation $operation;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->operation = $this->createMock(Operation::class);
    }

    public function testProcessReturnsNewTokensAndSetsCookie(): void
    {
        $refreshToken = $this->faker->sha256();
        $dto = new RefreshTokenDto($refreshToken);
        $this->expectRefreshDispatch($refreshToken);

        $processor = new RefreshTokenProcessor(
            $this->commandBus,
            new CommandResponseTypeGuard(),
            new RefreshTokenCommandFactory()
        );
        $response = $processor->process($dto, $this->operation);

        $this->assertRefreshResponse($response);
    }

    public function testProcessDoesNotSetCookieWhenAccessTokenIsEmpty(): void
    {
        $refreshToken = $this->faker->sha256();
        $dto = new RefreshTokenDto($refreshToken);
        $this->expectEmptyAccessTokenDispatch($refreshToken);

        $processor = new RefreshTokenProcessor(
            $this->commandBus,
            new CommandResponseTypeGuard(),
            new RefreshTokenCommandFactory()
        );
        $response = $processor->process($dto, $this->operation);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertCount(0, $response->headers->getCookies());
    }

    private function expectRefreshDispatch(string $refreshToken): void
    {
        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(/**
             * @return true
             */
                function (RefreshTokenCommand $command) use ($refreshToken): bool {
                    $this->assertSame($refreshToken, $command->refreshToken);

                    return true;
                }
            ))
            ->willReturn(new RefreshTokenCommandResponse(
                'new-access-token',
                'new-refresh-token'
            ));
    }

    private function expectEmptyAccessTokenDispatch(string $refreshToken): void
    {
        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(/**
             * @return true
             */
                function (RefreshTokenCommand $command) use ($refreshToken): bool {
                    $this->assertSame($refreshToken, $command->refreshToken);

                    return true;
                }
            ))
            ->willReturn(new RefreshTokenCommandResponse(
                '',
                'new-refresh-token'
            ));
    }

    private function assertRefreshResponse(Response $response): void
    {
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        $this->assertSame('new-access-token', $body['access_token']);
        $this->assertSame('new-refresh-token', $body['refresh_token']);
    }
}
