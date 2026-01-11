<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SendPasswordResetEmailCommand;
use App\User\Domain\Aggregate\PasswordResetEmailInterface;

final class SendPasswordResetEmailCommandTest extends UnitTestCase
{
    public function testConstructor(): void
    {
        $passwordResetEmail = $this->createMock(PasswordResetEmailInterface::class);

        $command = new SendPasswordResetEmailCommand($passwordResetEmail);

        $this->assertInstanceOf(SendPasswordResetEmailCommand::class, $command);
        $this->assertSame($passwordResetEmail, $command->passwordResetEmail);
    }
}
