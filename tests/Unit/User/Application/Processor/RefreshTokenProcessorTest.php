<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RefreshTokenCommand;
use App\User\Application\Command\RefreshTokenCommandResponse;
use App\User\Application\DTO\RefreshTokenDto;
use App\User\Application\Processor\RefreshTokenProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Cookie;
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
        $dto = new RefreshTokenDto('old-refresh-token');

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(/**
             * @return true
             */
            function (RefreshTokenCommand $command): bool {
                $this->assertSame('old-refresh-token', $command->refreshToken);

                $command->setResponse(
                    new RefreshTokenCommandResponse(
                        'new-access-token',
                        'new-refresh-token'
                    )
                );

                return true;
            }));

        $processor = new RefreshTokenProcessor($this->commandBus);
        $response = $processor->process($dto, $this->operation);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $body = json_decode((string) $response->getContent(), true);
        $this->assertSame('new-access-token', $body['access_token']);
        $this->assertSame('new-refresh-token', $body['refresh_token']);

        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);
        $this->assertSame('__Host-auth_token', $cookies[0]->getName());
        $this->assertSame('new-access-token', $cookies[0]->getValue());
        $this->assertTrue($cookies[0]->isSecure());
        $this->assertTrue($cookies[0]->isHttpOnly());
        $this->assertSame(
            Cookie::SAMESITE_LAX,
            $cookies[0]->getSameSite()
        );
    }

    public function testProcessDoesNotSetCookieWhenAccessTokenIsEmpty(): void
    {
        $dto = new RefreshTokenDto('old-refresh-token');

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(/**
             * @return true
             */
            static function (RefreshTokenCommand $command): bool {
                $command->setResponse(
                    new RefreshTokenCommandResponse(
                        '',
                        'new-refresh-token'
                    )
                );

                return true;
            }));

        $processor = new RefreshTokenProcessor($this->commandBus);
        $response = $processor->process($dto, $this->operation);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertCount(0, $response->headers->getCookies());
    }
}
