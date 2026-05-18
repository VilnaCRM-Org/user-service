<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Query;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Query\FindUserByEmailQueryHandler;
use App\User\Application\Service\EmailNormalizer;
use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\DuplicateEmailException;
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
        $repository->expects($this->once())
            ->method('findByEmailCaseInsensitive')
            ->with($normalizedEmail)
            ->willReturn(new UserCollection([$user]));

        $handler = new FindUserByEmailQueryHandler(
            $repository,
            new EmailNormalizer()
        );

        $this->assertSame($user, $handler->find($email));
    }

    public function testThrowsWhenExactUserHasAmbiguousCaseInsensitiveVariants(): void
    {
        [$email, $normalizedEmail] = $this->createEmailFixture();
        $exactUser = $this->createMock(UserInterface::class);
        $legacyUser = $this->createMock(UserInterface::class);
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findByEmail')
            ->with($normalizedEmail)
            ->willReturn($exactUser);
        $repository->expects($this->once())
            ->method('findByEmailCaseInsensitive')
            ->with($normalizedEmail)
            ->willReturn(new UserCollection([$exactUser, $legacyUser]));

        $handler = new FindUserByEmailQueryHandler(
            $repository,
            new EmailNormalizer()
        );

        $this->expectException(DuplicateEmailException::class);

        $handler->find($email);
    }

    public function testReturnsNullIfUserNotFound(): void
    {
        [$email, $normalizedEmail] = $this->createEmailFixture();
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findByEmail')
            ->with($normalizedEmail)
            ->willReturn(null);
        $repository->expects($this->once())
            ->method('findByEmailCaseInsensitive')
            ->with($normalizedEmail)
            ->willReturn(new UserCollection());

        $handler = new FindUserByEmailQueryHandler(
            $repository,
            new EmailNormalizer()
        );

        $this->assertNull($handler->find($email));
    }

    public function testReturnsCaseInsensitiveFallbackUser(): void
    {
        [$email, $normalizedEmail] = $this->createEmailFixture();
        $user = $this->createMock(UserInterface::class);
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findByEmail')
            ->with($normalizedEmail)
            ->willReturn(null);
        $repository->expects($this->once())
            ->method('findByEmailCaseInsensitive')
            ->with($normalizedEmail)
            ->willReturn(new UserCollection([$user]));

        $handler = new FindUserByEmailQueryHandler(
            $repository,
            new EmailNormalizer()
        );

        $this->assertSame($user, $handler->find($email));
    }

    public function testThrowsWhenCaseInsensitiveFallbackIsAmbiguous(): void
    {
        [$email, $normalizedEmail] = $this->createEmailFixture();
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findByEmail')
            ->with($normalizedEmail)
            ->willReturn(null);
        $repository->expects($this->once())
            ->method('findByEmailCaseInsensitive')
            ->with($normalizedEmail)
            ->willReturn(new UserCollection([
                $this->createMock(UserInterface::class),
                $this->createMock(UserInterface::class),
            ]));

        $handler = new FindUserByEmailQueryHandler(
            $repository,
            new EmailNormalizer()
        );

        $this->expectException(DuplicateEmailException::class);

        $handler->find($email);
    }

    /**
     * @return array{string,string}
     */
    private function createEmailFixture(): array
    {
        $email = ' ' . "\u{00C4}" . strtoupper($this->faker->safeEmail()) . ' ';

        return [$email, mb_strtolower(trim($email), 'UTF-8')];
    }
}
