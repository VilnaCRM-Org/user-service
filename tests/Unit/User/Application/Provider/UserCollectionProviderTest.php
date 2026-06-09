<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Provider;

use ApiPlatform\Metadata\Operation;
use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\AuthorizationUserDto;
use App\User\Application\Provider\UserCollectionProvider;
use App\User\Application\Query\GetUserQueryHandlerInterface;
use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserCollectionProviderTest extends UnitTestCase
{
    private Security&MockObject $security;
    private GetUserQueryHandlerInterface&MockObject $getUserQueryHandler;
    private UserCollectionProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->security = $this->createMock(Security::class);
        $this->getUserQueryHandler = $this->createMock(
            GetUserQueryHandlerInterface::class
        );
        $this->provider = new UserCollectionProvider(
            $this->security,
            $this->getUserQueryHandler
        );
    }

    public function testProvideReturnsOnlyCurrentAuthenticatedUser(): void
    {
        $userId = $this->faker->uuid();
        $authUser = $this->createAuthorizationUserDto($userId);
        $ownRecord = $this->createMock(User::class);

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($authUser);

        $this->getUserQueryHandler->expects($this->once())
            ->method('handle')
            ->with($userId)
            ->willReturn($ownRecord);

        $result = $this->provider->provide($this->createMock(Operation::class));

        $this->assertInstanceOf(UserCollection::class, $result);
        $this->assertSame([$ownRecord], $result->users);
    }

    public function testProvideReturnsEmptyWhenNotAuthorizationUser(): void
    {
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createMock(UserInterface::class));

        $this->getUserQueryHandler->expects($this->never())
            ->method('handle');

        $result = $this->provider->provide($this->createMock(Operation::class));

        $this->assertInstanceOf(UserCollection::class, $result);
        $this->assertSame([], $result->users);
    }

    public function testProvideReturnsEmptyWhenNoAuthenticatedUser(): void
    {
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->getUserQueryHandler->expects($this->never())
            ->method('handle');

        $result = $this->provider->provide($this->createMock(Operation::class));

        $this->assertInstanceOf(UserCollection::class, $result);
        $this->assertSame([], $result->users);
    }

    public function testProvideReturnsEmptyWhenOwnRecordMissing(): void
    {
        $userId = $this->faker->uuid();
        $authUser = $this->createAuthorizationUserDto($userId);

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($authUser);

        $this->getUserQueryHandler->expects($this->once())
            ->method('handle')
            ->with($userId)
            ->willThrowException(new UserNotFoundException());

        $result = $this->provider->provide($this->createMock(Operation::class));

        $this->assertInstanceOf(UserCollection::class, $result);
        $this->assertSame([], $result->users);
    }

    private function createAuthorizationUserDto(string $userId): AuthorizationUserDto
    {
        $uuidTransformer = new UuidTransformer(new SharedUuidFactory());

        return new AuthorizationUserDto(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
            $uuidTransformer->transformFromString($userId),
            true
        );
    }
}
