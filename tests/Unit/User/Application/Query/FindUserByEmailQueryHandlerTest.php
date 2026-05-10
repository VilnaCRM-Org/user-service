<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Query;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Query\FindUserByEmailQueryHandler;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;

final class FindUserByEmailQueryHandlerTest extends UnitTestCase
{
    public function testReturnsUserIfFound(): void
    {
        $email = $this->faker->email();
        $user = $this->createMock(User::class);
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $handler = new FindUserByEmailQueryHandler($repository);

        $this->assertSame($user, $handler->find($email));
    }

    public function testReturnsNullIfUserNotFound(): void
    {
        $email = $this->faker->email();
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $handler = new FindUserByEmailQueryHandler($repository);

        $this->assertNull($handler->find($email));
    }
}
