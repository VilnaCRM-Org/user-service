<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\Command\ConfirmPasswordResetCommandResponse;

final class ConfirmPasswordResetCommandTest extends UnitTestCase
{
    public function testConstructor(): void
    {
        $token = $this->faker->sha256();
        $newPassword = $this->faker->password();

        $command = new ConfirmPasswordResetCommand($token, $newPassword);

        $this->assertInstanceOf(ConfirmPasswordResetCommand::class, $command);
        $this->assertSame($token, $command->token);
        $this->assertSame($newPassword, $command->newPassword);
    }

    public function testSetAndGetResponse(): void
    {
        $token = $this->faker->sha256();
        $newPassword = $this->faker->password();

        $command = new ConfirmPasswordResetCommand($token, $newPassword);
        $response = new ConfirmPasswordResetCommandResponse();

        $command->setResponse($response);

        $this->assertSame($response, $command->getResponse());
    }
}
