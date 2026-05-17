<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Query;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Query\FindUserByEmailQueryHandler;
use App\User\Application\Service\EmailNormalizer;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

final class FindUserByEmailQueryHandlerTest extends UnitTestCase
{
    public function testReturnsUserIfFound(): void
    {
        [$email, $normalizedEmail] = $this->createEmailFixture();
        $user = $this->createMock(UserInterface::class);
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findByEmail')
            ->with($normalizedEmail)
            ->willReturn($user);

        $handler = new FindUserByEmailQueryHandler(
            $repository,
            new EmailNormalizer()
        );

        $this->assertSame($user, $handler->find($email));
    }

    public function testReturnsNullIfUserNotFound(): void
    {
        [$email, $normalizedEmail] = $this->createEmailFixture();
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findByEmail')
            ->with($normalizedEmail)
            ->willReturn(null);

        $handler = new FindUserByEmailQueryHandler(
            $repository,
            new EmailNormalizer()
        );

        $this->assertNull($handler->find($email));
    }

    /**
     * @return array{string,string}
     */
    private function createEmailFixture(): array
    {
        $email = ' ' . "\u{00C4}" . strtoupper($this->faker->safeEmail()) . ' ';

        return [$email, mb_strtolower(trim($email))];
    }
}
