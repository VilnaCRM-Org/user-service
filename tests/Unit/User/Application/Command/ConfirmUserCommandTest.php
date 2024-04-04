<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\ConfirmUserCommand;
use App\User\Domain\Entity\ConfirmationToken;

final class ConfirmUserCommandTest extends UnitTestCase
{
    public function testConstructor(): void
    {
        $token = new ConfirmationToken($this->faker->uuid(), $this->faker->uuid());

        $command = new ConfirmUserCommand($token);

        $this->assertInstanceOf(ConfirmUserCommand::class, $command);
        $this->assertSame($token, $command->token);
    }
}
