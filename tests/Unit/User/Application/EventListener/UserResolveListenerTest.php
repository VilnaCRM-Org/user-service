<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventListener;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\AuthorizationUserDto;
use App\User\Application\EventListener\UserResolveListener;
use App\User\Application\Transformer\UserTransformer;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use League\Bundle\OAuth2ServerBundle\Event\UserResolveEvent;
use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

final class UserResolveListenerTest extends UnitTestCase
{
    private MockObject $hasherFactory;
    private MockObject $userRepository;
    private MockObject $mockUserTransformer;

    private UserTransformer $userTransformer;

    private UserFactoryInterface $userFactory;
    private UuidTransformer $transformer;
    private Grant $mockGrant;
    private AbstractClient $mockAbstractClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasherFactory =
            $this->createMock(PasswordHasherFactoryInterface::class);
        $this->userRepository =
            $this->createMock(UserRepositoryInterface::class);
        $this->mockUserTransformer = $this->createMock(UserTransformer::class);
        $this->userTransformer = new UserTransformer(new UuidTransformer());
        $this->userFactory = new UserFactory();
        $this->transformer = new UuidTransformer();
        $this->mockGrant = $this->createMock(Grant::class);
        $this->mockAbstractClient = $this->createMock(AbstractClient::class);
    }

    public function testSuccessfulUserResolution(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $uuid = $this->transformer->transformFromString($this->faker->uuid());
        $user = $this->userFactory->create($email, $initials, $password, $uuid);
        $authUser = $this->userTransformer->transformToAuthorizationUser($user);

        $this->testSuccessfulUserResolutionSetExpectations($user, $authUser);

        $event = new UserResolveEvent(
            $email,
            $password,
            $this->mockGrant,
            $this->mockAbstractClient
        );

        $listener = new UserResolveListener(
            $this->hasherFactory,
            $this->userRepository,
            $this->mockUserTransformer
        );
        $listener->onUserResolve($event);

        $this->assertSame($authUser, $event->getUser());
    }

    public function testInvalidPassword(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $uuid = $this->transformer->transformFromString($this->faker->uuid());
        $user = $this->userFactory->create($email, $initials, $password, $uuid);
        $authUser = $this->userTransformer->transformToAuthorizationUser($user);

        $this->testInvalidPasswordSetExpectations($user, $authUser);

        $event = new UserResolveEvent(
            $email,
            $password,
            $this->mockGrant,
            $this->mockAbstractClient
        );

        $listener = new UserResolveListener(
            $this->hasherFactory,
            $this->userRepository,
            $this->mockUserTransformer
        );

        $listener->onUserResolve($event);

        $this->assertNull($event->getUser());
    }

    private function testInvalidPasswordSetExpectations(
        UserInterface $user,
        AuthorizationUserDto $authUser,
    ): void {
        $email = $user->getEmail();
        $password = $user->getPassword();

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->mockUserTransformer->expects($this->once())
            ->method('transformToAuthorizationUser')
            ->with($user)
            ->willReturn($authUser);

        $hasher =
            $this->createMock(PasswordHasherInterface::class);
        $hasher->expects($this->once())
            ->method('verify')
            ->with($password, $password)
            ->willReturn(false);

        $this->hasherFactory->expects($this->once())
            ->method('getPasswordHasher')
            ->with(User::class)
            ->willReturn($hasher);
    }

    private function testSuccessfulUserResolutionSetExpectations(
        UserInterface $user,
        AuthorizationUserDto $authUser,
    ): void {
        $email = $user->getEmail();
        $password = $user->getPassword();

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->mockUserTransformer->expects($this->once())
            ->method('transformToAuthorizationUser')
            ->with($user)
            ->willReturn($authUser);

        $hasher =
            $this->createMock(PasswordHasherInterface::class);
        $hasher->expects($this->once())
            ->method('verify')
            ->with($password, $password)
            ->willReturn(true);

        $this->hasherFactory->expects($this->once())
            ->method('getPasswordHasher')
            ->with(User::class)
            ->willReturn($hasher);
    }
}
