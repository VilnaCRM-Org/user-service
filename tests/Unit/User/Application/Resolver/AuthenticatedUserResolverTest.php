<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Query\FindUserByEmailQueryHandlerInterface;
use App\User\Application\Resolver\AuthenticatedUserResolver;
use App\User\Domain\Exception\DuplicateEmailException;
use App\User\Domain\Factory\UserFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class AuthenticatedUserResolverTest extends UnitTestCase
{
    private FindUserByEmailQueryHandlerInterface&MockObject $findUserByEmailQueryHandler;
    private AuthenticatedUserResolver $resolver;
    private UserFactory $userFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->findUserByEmailQueryHandler =
            $this->createMock(FindUserByEmailQueryHandlerInterface::class);
        $this->resolver = new AuthenticatedUserResolver($this->findUserByEmailQueryHandler);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());
    }

    public function testReturnsResolvedUser(): void
    {
        $email = $this->faker->email();
        $user = $this->userFactory->create(
            $email,
            $this->faker->name(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
        $this->findUserByEmailQueryHandler
            ->expects($this->once())
            ->method('find')
            ->with($email)
            ->willReturn($user);

        $this->assertSame($user, $this->resolver->resolve($email));
    }

    public function testThrowsUnauthorizedWhenUserNotFound(): void
    {
        $email = $this->faker->email();
        $this->findUserByEmailQueryHandler
            ->expects($this->once())
            ->method('find')
            ->with($email)
            ->willReturn(null);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Authentication required.');
        $this->resolver->resolve($email);
    }

    public function testThrowsUnauthorizedWhenEmailIsAmbiguous(): void
    {
        $email = $this->faker->email();
        $this->findUserByEmailQueryHandler
            ->expects($this->once())
            ->method('find')
            ->with($email)
            ->willThrowException(new DuplicateEmailException($email));

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Authentication required.');
        $this->resolver->resolve($email);
    }
}
