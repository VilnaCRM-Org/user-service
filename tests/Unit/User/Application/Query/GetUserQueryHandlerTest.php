<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Query;

use PHPUnit\Framework\TestCase;
use App\User\Application\Query\GetUserQueryHandler;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Entity\User;

class GetUserQueryHandlerTest extends TestCase
{
    public function testReturnsUserIfFound(): void
    {
        $user = $this->createMock(User::class);
        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('find')->with('123')->willReturn($user);

        $handler = new GetUserQueryHandler($repo);

        $result = $handler->handle('123');

        $this->assertSame($user, $result);
    }

    public function testThrowsExceptionIfUserNotFound(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('find')->with('not-exist')->willReturn(null);

        $handler = new GetUserQueryHandler($repo);

        $this->expectException(UserNotFoundException::class);
        $handler->handle('not-exist');
    }
}
