<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Post;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\ConfirmTwoFactorCommand;
use App\User\Application\Command\ConfirmTwoFactorCommandResponse;
use App\User\Application\DTO\ConfirmTwoFactorDto;
use App\User\Application\Processor\ConfirmTwoFactorProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class ConfirmTwoFactorProcessorTest extends UnitTestCase
{
    private CommandBusInterface&MockObject $commandBus;
    private Security&MockObject $security;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(
            CommandBusInterface::class
        );
        $this->security = $this->createMock(Security::class);
    }

    public function testProcessReturnsRecoveryCodes(): void
    {
        $email = $this->faker->email();
        $code = '123456';
        $recoveryCodes = [
            'ABCD-1234',
            'EFGH-5678',
            'IJKL-9012',
            'MNOP-3456',
            'QRST-7890',
            'UVWX-1234',
            'YZab-5678',
            'cdef-9012',
        ];

        $this->mockAuthenticatedUser($email);

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(
                function (ConfirmTwoFactorCommand $cmd) use (
                    $email,
                    $code,
                    $recoveryCodes
                ): void {
                    $this->assertSame($email, $cmd->userEmail);
                    $this->assertSame($code, $cmd->twoFactorCode);
                    $cmd->setResponse(
                        new ConfirmTwoFactorCommandResponse(
                            $recoveryCodes
                        )
                    );
                }
            );

        $processor = $this->createProcessor();
        $dto = new ConfirmTwoFactorDto($code);

        $response = $processor->process(
            $dto,
            new Post(),
            [],
            []
        );

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        $body = json_decode(
            (string) $response->getContent(),
            true
        );
        $this->assertSame($recoveryCodes, $body['recovery_codes']);
    }

    public function testProcessThrowsWhenNotAuthenticated(): void
    {
        $this->security
            ->method('getUser')
            ->willReturn(null);

        $this->commandBus
            ->expects($this->never())
            ->method('dispatch');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Authentication required.');

        $processor = $this->createProcessor();
        $processor->process(
            new ConfirmTwoFactorDto('123456'),
            new Post(),
            [],
            []
        );
    }

    public function testProcessPassesCurrentSessionId(): void
    {
        $email = $this->faker->email();
        $sessionId = $this->faker->uuid();

        $this->mockAuthenticatedUserWithSession($email, $sessionId);

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(
                function (ConfirmTwoFactorCommand $cmd) use (
                    $sessionId
                ): void {
                    $this->assertSame(
                        $sessionId,
                        $cmd->currentSessionId
                    );
                    $cmd->setResponse(
                        new ConfirmTwoFactorCommandResponse([])
                    );
                }
            );

        $processor = $this->createProcessor();
        $processor->process(
            new ConfirmTwoFactorDto('123456'),
            new Post(),
            [],
            []
        );
    }

    public function testProcessFallsBackToEmptySessionId(): void
    {
        $email = $this->faker->email();
        $this->mockAuthenticatedUser($email);

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(
                function (ConfirmTwoFactorCommand $cmd): void {
                    $this->assertSame('', $cmd->currentSessionId);
                    $cmd->setResponse(
                        new ConfirmTwoFactorCommandResponse([])
                    );
                }
            );

        $processor = $this->createProcessor();
        $processor->process(
            new ConfirmTwoFactorDto('123456'),
            new Post(),
            [],
            []
        );
    }

    private function createProcessor(): ConfirmTwoFactorProcessor
    {
        return new ConfirmTwoFactorProcessor(
            $this->commandBus,
            $this->security,
        );
    }

    private function mockAuthenticatedUser(string $email): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn($email);

        $this->security
            ->method('getUser')
            ->willReturn($user);

        $this->security
            ->method('getToken')
            ->willReturn(null);
    }

    private function mockAuthenticatedUserWithSession(
        string $email,
        string $sessionId
    ): void {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn($email);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getAttribute')
            ->with('sid')
            ->willReturn($sessionId);

        $this->security
            ->method('getUser')
            ->willReturn($user);

        $this->security
            ->method('getToken')
            ->willReturn($token);
    }
}
