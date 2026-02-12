<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SignOutAllCommand;
use App\User\Application\DTO\SignOutAllDto;
use App\User\Application\Processor\SignOutAllProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class SignOutAllProcessorTest extends UnitTestCase
{
    private CommandBusInterface&MockObject $commandBus;
    private TokenStorageInterface&MockObject $tokenStorage;
    private SignOutAllProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->processor = new SignOutAllProcessor(
            $this->commandBus,
            $this->tokenStorage
        );
    }

    public function testProcessDispatchesSignOutAllCommand(): void
    {
        $userId = $this->faker->uuid();
        $dto = new SignOutAllDto();
        $operation = $this->createMock(Operation::class);

        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $token->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn($userId);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (SignOutAllCommand $command) use ($userId) {
                return $command->userId === $userId;
            }));

        $this->processor->process($dto, $operation);
    }

    public function testProcessThrowsExceptionWhenNoToken(): void
    {
        $dto = new SignOutAllDto();
        $operation = $this->createMock(Operation::class);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(null);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Authentication required');

        $this->processor->process($dto, $operation);
    }
}
