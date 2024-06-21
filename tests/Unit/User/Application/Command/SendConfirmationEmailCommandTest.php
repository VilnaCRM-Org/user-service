<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SendConfirmationEmailCommand;
use App\User\Domain\Aggregate\ConfirmationEmailInterface;

final class SendConfirmationEmailCommandTest extends UnitTestCase
{
    public function testConstructor(): void
    {
        $confirmationEmail =
            $this->createMock(ConfirmationEmailInterface::class);

        $command = new SendConfirmationEmailCommand($confirmationEmail);

        $this->assertInstanceOf(
            SendConfirmationEmailCommand::class,
            $command
        );
        $this->assertSame($confirmationEmail, $command->confirmationEmail);
    }
}
