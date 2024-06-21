<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Message;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Message\UserRegisteredMessage;
use App\User\Domain\Entity\UserInterface;

final class UserRegisteredMessageTest extends UnitTestCase
{
    public function testConstruct(): void
    {
        $userMock = $this->createMock(UserInterface::class);

        $message = new UserRegisteredMessage($userMock);

        $this->assertSame($userMock, $message->user);
    }
}
