<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RequestPasswordResetCommand;

final class RequestPasswordResetCommandTest extends UnitTestCase
{
    public function testConstructor(): void
    {
        $email = $this->faker->email();

        $command = new RequestPasswordResetCommand($email);

        $this->assertInstanceOf(RequestPasswordResetCommand::class, $command);
        $this->assertSame($email, $command->email);
    }
}
