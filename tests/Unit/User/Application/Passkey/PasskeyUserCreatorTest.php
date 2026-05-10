<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Passkey;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\UuidInterface;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\EventIdFactoryInterface;
use App\User\Application\Passkey\PasskeyUserCreator;
use App\User\Domain\Contract\PasswordHasherInterface;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\UserRegisteredEvent;
use App\User\Domain\Factory\Event\UserRegisteredEventFactoryInterface;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\PasskeyChallengeContext;
use function ctype_xdigit;
use DateTimeImmutable;

use function strlen;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class PasskeyUserCreatorTest extends UnitTestCase
{
    public function testCreateFromSignupChallengeUsesSecureRandomPasswordAndPublishesEvent(): void
    {
        $challenge = $this->createSignupChallenge();
        $uuidTransformer = new UuidTransformer(new UuidFactory());
        $user = $this->createExpectedUser($uuidTransformer);
        $event = new UserRegisteredEvent($user->getId(), $user->getEmail(), 'event-id');

        self::assertSame(
            $user,
            $this->createSuccessfulUserCreator($user, $uuidTransformer, $event)
                ->createFromSignupChallenge($challenge)
        );
    }

    public function testCreateFromSignupChallengeRejectsUnexpectedFactoryResult(): void
    {
        $userFactory = $this->createMock(UserFactoryInterface::class);
        $userFactory->method('create')->willReturn($this->createMock(UserInterface::class));

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired passkey challenge.');

        $this->createUserCreator($userFactory)->createFromSignupChallenge(
            $this->createSignupChallenge()
        );
    }

    private function createSuccessfulUserCreator(
        User $user,
        UuidTransformer $uuidTransformer,
        UserRegisteredEvent $event
    ): PasskeyUserCreator {
        return new PasskeyUserCreator(
            $this->createUserRepositoryExpectingSave($user),
            $this->createPasswordHasherExpectingRandomPassword(),
            $this->createUserFactoryExpectingSignup($user),
            $uuidTransformer,
            $this->createEventBusExpectingPublish($event),
            $this->createEventIdFactory(),
            $this->createRegisteredEventFactory($user, $event)
        );
    }

    private function createExpectedUser(UuidTransformer $uuidTransformer): User
    {
        return new User(
            'new@example.com',
            'NE',
            'hashed-password',
            $uuidTransformer->transformFromString('018f33bb-1111-7222-8333-111111111111')
        );
    }

    private function createPasswordHasherExpectingRandomPassword(): PasswordHasherInterface
    {
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->expects($this->once())
            ->method('hash')
            ->with(self::callback(static fn (string $password): bool => strlen($password) === 64
                && ctype_xdigit($password)))
            ->willReturn('hashed-password');
        return $hasher;
    }

    private function createUserFactoryExpectingSignup(User $user): UserFactoryInterface
    {
        $userFactory = $this->createMock(UserFactoryInterface::class);
        $userFactory->expects($this->once())
            ->method('create')
            ->with(
                'new@example.com',
                'NE',
                'hashed-password',
                self::callback(static fn (UuidInterface $uuid): bool => (string) $uuid
                    === '018f33bb-1111-7222-8333-111111111111')
            )
            ->willReturn($user);

        return $userFactory;
    }

    private function createUserRepositoryExpectingSave(User $user): UserRepositoryInterface
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects($this->once())->method('save')->with($user);

        return $repository;
    }

    private function createEventIdFactory(): EventIdFactoryInterface
    {
        $factory = $this->createMock(EventIdFactoryInterface::class);
        $factory->expects($this->once())->method('generate')->willReturn('event-id');

        return $factory;
    }

    private function createRegisteredEventFactory(
        User $user,
        UserRegisteredEvent $event
    ): UserRegisteredEventFactoryInterface {
        $registeredEventFactory = $this->createMock(
            UserRegisteredEventFactoryInterface::class
        );
        $registeredEventFactory->expects($this->once())
            ->method('create')
            ->with($user, 'event-id')
            ->willReturn($event);

        return $registeredEventFactory;
    }

    private function createEventBusExpectingPublish(UserRegisteredEvent $event): EventBusInterface
    {
        $eventBus = $this->createMock(EventBusInterface::class);
        $eventBus->expects($this->once())->method('publish')->with($event);

        return $eventBus;
    }

    private function createUserCreator(UserFactoryInterface $userFactory): PasskeyUserCreator
    {
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $passwordHasher->method('hash')->willReturn('hashed-password');

        return new PasskeyUserCreator(
            $this->createMock(UserRepositoryInterface::class),
            $passwordHasher,
            $userFactory,
            new UuidTransformer(new UuidFactory()),
            $this->createMock(EventBusInterface::class),
            $this->createMock(EventIdFactoryInterface::class),
            $this->createMock(UserRegisteredEventFactoryInterface::class)
        );
    }

    private function createSignupChallenge(): PasskeyChallenge
    {
        $createdAt = new DateTimeImmutable();

        return new PasskeyChallenge(
            'challenge-id',
            PasskeyChallenge::PURPOSE_SIGNUP,
            'challenge',
            '{}',
            $createdAt,
            $createdAt->modify('+5 minutes'),
            new PasskeyChallengeContext(
                'new@example.com',
                'NE',
                'New Example',
                '018f33bb-1111-7222-8333-111111111111'
            )
        );
    }
}
