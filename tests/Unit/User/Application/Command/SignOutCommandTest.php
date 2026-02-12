<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SignOutCommand;

final class SignOutCommandTest extends UnitTestCase
{
    public function testConstruct(): void
    {
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();

        $command = new SignOutCommand($sessionId, $userId);

        $this->assertSame($sessionId, $command->sessionId);
        $this->assertSame($userId, $command->userId);
    }
}
