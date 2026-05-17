<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\RegisterUserCommandResponse;
use App\User\Domain\Entity\UserInterface;

final class RegisterUserCommandResponseTest extends UnitTestCase
{
    public function testConstructor(): void
    {
        $user = $this->createMock(UserInterface::class);

        $response = new RegisterUserCommandResponse($user);

        $this->assertSame($user, $response->createdUser);
    }
}
