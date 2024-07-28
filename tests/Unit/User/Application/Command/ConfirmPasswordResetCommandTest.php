<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Tests\Builders\ConfirmationTokenBuilder;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\ConfirmPasswordResetCommand;

final class ConfirmPasswordResetCommandTest extends UnitTestCase
{
    public function testConstructor(): void
    {
        $confirmationToken = (new ConfirmationTokenBuilder())->build();
        $newPassword = $this->faker->password();
        $command = new ConfirmPasswordResetCommand($confirmationToken, $newPassword);

        $this->assertInstanceOf(ConfirmPasswordResetCommand::class, $command);
        $this->assertEquals($confirmationToken, $command->token);
        $this->assertEquals($newPassword, $command->newPassword);
    }
}
