<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Post;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\DisableTwoFactorCommand;
use App\User\Application\DTO\DisableTwoFactorDto;
use App\User\Application\Processor\DisableTwoFactorProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

final class DisableTwoFactorProcessorTest extends UnitTestCase
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

    public function testProcessReturns204(): void
    {
        $email = $this->faker->email();
        $code = '123456';

        $this->mockAuthenticatedUser($email);

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(
                function (DisableTwoFactorCommand $cmd) use (
                    $email,
                    $code
                ): void {
                    $this->assertSame($email, $cmd->userEmail);
                    $this->assertSame($code, $cmd->twoFactorCode);
                }
            );

        $processor = $this->createProcessor();
        $response = $processor->process(
            new DisableTwoFactorDto($code),
            new Post(),
            [],
            []
        );

        $this->assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
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
            new DisableTwoFactorDto('123456'),
            new Post(),
            [],
            []
        );
    }

    private function createProcessor(): DisableTwoFactorProcessor
    {
        return new DisableTwoFactorProcessor(
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
    }
}
