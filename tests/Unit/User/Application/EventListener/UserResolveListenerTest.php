<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventListener;

use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\AuthorizationUserDto;
use App\User\Application\EventListener\UserResolveListener;
use App\User\Application\Query\FindUserByEmailQueryHandlerInterface;
use App\User\Application\Transformer\UserTransformer;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\DuplicateEmailException;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use League\Bundle\OAuth2ServerBundle\Event\UserResolveEvent;
use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use function mb_strtoupper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

final class UserResolveListenerTest extends UnitTestCase
{
    private MockObject $hasherFactory;
    private FindUserByEmailQueryHandlerInterface&MockObject $findUserByEmailQueryHandler;
    private MockObject $mockUserTransformer;

    private UserTransformer $userTransformer;

    private UserFactoryInterface $userFactory;
    private UuidTransformer $transformer;
    private Grant $mockGrant;
    private AbstractClient $mockAbstractClient;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->hasherFactory =
            $this->createMock(PasswordHasherFactoryInterface::class);
        $this->findUserByEmailQueryHandler =
            $this->createMock(FindUserByEmailQueryHandlerInterface::class);
        $this->mockUserTransformer = $this->createMock(UserTransformer::class);
        $this->userTransformer = new UserTransformer(
            new UuidTransformer(new UuidFactory())
        );
        $this->userFactory = new UserFactory();
        $this->transformer = new UuidTransformer(new UuidFactory());
        $this->mockGrant = $this->createMock(Grant::class);
        $this->mockAbstractClient = $this->createMock(AbstractClient::class);
    }

    public function testSuccessfulUserResolution(): void
    {
        $email = $this->faker->email();
        $password = $this->faker->password();
        $user = $this->createUser($email, $password);
        $authUser = $this->userTransformer->transformToAuthorizationUser($user);

        $this->testSuccessfulUserResolutionSetExpectations($user, $authUser);

        $event = $this->createEvent($email, $password);

        $this->createListener()->onUserResolve($event);

        $this->assertSame($authUser, $event->getUser());
    }

    public function testInvalidPassword(): void
    {
        $email = $this->faker->email();
        $password = $this->faker->password();
        $user = $this->createUser($email, $password);
        $this->testInvalidPasswordSetExpectations($user);

        $event = $this->createEvent($email, $password);

        $this->createListener()->onUserResolve($event);

        $this->assertNull($event->getUser());
    }

    public function testUserNotFoundDoesNothing(): void
    {
        $email = $this->faker->email();

        $this->findUserByEmailQueryHandler->expects($this->once())
            ->method('find')
            ->with($email)
            ->willReturn(null);

        $this->hasherFactory->expects($this->never())
            ->method('getPasswordHasher');

        $event = $this->createEvent($email, $this->faker->password());

        $this->createListener()->onUserResolve($event);

        $this->assertNull($event->getUser());
    }

    public function testDuplicateEmailAmbiguityLeavesUserUnset(): void
    {
        $email = $this->faker->email();

        $this->findUserByEmailQueryHandler->expects($this->once())
            ->method('find')
            ->with($email)
            ->willThrowException(new DuplicateEmailException($email));

        $this->hasherFactory->expects($this->never())
            ->method('getPasswordHasher');

        $this->mockUserTransformer->expects($this->never())
            ->method('transformToAuthorizationUser');

        $event = $this->createEvent($email, $this->faker->password());

        $this->createListener()->onUserResolve($event);

        $this->assertNull($event->getUser());
    }

    public function testSuccessfulUserResolutionUsesNormalizedEmailLookup(): void
    {
        $email = $this->faker->email();
        $submittedEmail = mb_strtoupper($email, 'UTF-8');
        $password = $this->faker->password();
        $user = $this->createUser($email, $password);
        $authUser = $this->userTransformer->transformToAuthorizationUser($user);

        $this->testSuccessfulUserResolutionSetExpectations(
            $user,
            $authUser,
            $submittedEmail
        );

        $event = $this->createEvent($submittedEmail, $password);

        $this->createListener()->onUserResolve($event);

        $this->assertSame($authUser, $event->getUser());
    }

    public function testSuccessfulUserResolutionUsesSubmittedEmailFallback(): void
    {
        $email = $this->createMixedCaseEmail();
        $password = $this->faker->password();
        $user = $this->createUser($email, $password);
        $authUser = $this->userTransformer->transformToAuthorizationUser($user);

        $this->testSuccessfulUserResolutionFallbackSetExpectations($user, $authUser);

        $event = $this->createEvent($email, $password);

        $this->createListener()->onUserResolve($event);

        $this->assertSame($authUser, $event->getUser());
    }

    private function testInvalidPasswordSetExpectations(UserInterface $user): void
    {
        $email = $user->getEmail();
        $password = $user->getPassword();

        $this->findUserByEmailQueryHandler->expects($this->once())
            ->method('find')
            ->with($email)
            ->willReturn($user);

        $this->mockUserTransformer->expects($this->never())
            ->method('transformToAuthorizationUser');

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
        ?string $submittedEmail = null,
    ): void {
        $email = $user->getEmail();
        $password = $user->getPassword();

        $this->findUserByEmailQueryHandler->expects($this->once())
            ->method('find')
            ->with($submittedEmail ?? $email)
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

    private function testSuccessfulUserResolutionFallbackSetExpectations(
        UserInterface $user,
        AuthorizationUserDto $authUser,
    ): void {
        $email = $user->getEmail();

        $this->findUserByEmailQueryHandler->expects($this->once())
            ->method('find')
            ->with($email)
            ->willReturn($user);

        $this->mockUserTransformer->expects($this->once())
            ->method('transformToAuthorizationUser')
            ->with($user)
            ->willReturn($authUser);

        $this->hasherFactory->expects($this->once())
            ->method('getPasswordHasher')
            ->with(User::class)
            ->willReturn($this->successfulPasswordHasher($user->getPassword()));
    }

    private function successfulPasswordHasher(
        string $password
    ): PasswordHasherInterface {
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->expects($this->once())
            ->method('verify')
            ->with($password, $password)
            ->willReturn(true);

        return $hasher;
    }

    private function createEvent(
        string $email,
        string $password
    ): UserResolveEvent {
        return new UserResolveEvent(
            $email,
            $password,
            $this->mockGrant,
            $this->mockAbstractClient
        );
    }

    private function createListener(): UserResolveListener
    {
        return new UserResolveListener(
            $this->hasherFactory,
            $this->findUserByEmailQueryHandler,
            $this->mockUserTransformer
        );
    }

    private function createUser(
        string $email,
        string $password
    ): UserInterface {
        return $this->userFactory->create(
            $email,
            $this->faker->name(),
            $password,
            $this->transformer->transformFromString($this->faker->uuid())
        );
    }

    private function createMixedCaseEmail(): string
    {
        return sprintf(
            '%s@%s',
            ucfirst(strtolower($this->faker->lexify('????????'))),
            $this->faker->domainName(),
        );
    }
}
