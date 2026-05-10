<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SignOutAllCommand;

final class SignOutAllCommandTest extends UnitTestCase
{
    public function testConstruct(): void
    {
        $userId = $this->faker->uuid();

        $command = new SignOutAllCommand($userId);

        $this->assertSame($userId, $command->userId);
    }
}
