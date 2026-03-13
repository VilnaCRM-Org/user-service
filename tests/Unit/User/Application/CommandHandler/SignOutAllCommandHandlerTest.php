<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SignOutAllCommand;
use App\User\Application\CommandHandler\SignOutAllCommandHandler;
use App\User\Application\Processor\Revoker\AllSessionsRevokerInterface;
use PHPUnit\Framework\MockObject\MockObject;

final class SignOutAllCommandHandlerTest extends UnitTestCase
{
    private AllSessionsRevokerInterface&MockObject $allSessionsRevoker;
    private SignOutAllCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->allSessionsRevoker = $this->createMock(
            AllSessionsRevokerInterface::class
        );
        $this->handler = new SignOutAllCommandHandler(
            $this->allSessionsRevoker
        );
    }

    public function testInvokeDelegatesToAllSessionsRevoker(): void
    {
        $userId = $this->faker->uuid();
        $this->allSessionsRevoker->expects($this->once())
            ->method('revokeAllSessions')
            ->with($userId, 'user_initiated')
            ->willReturn(2);

        $this->handler->__invoke(new SignOutAllCommand($userId));
    }
}
