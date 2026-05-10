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
use DateTimeImmutable;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class PasskeyUserCreatorTest extends UnitTestCase
{
    private string $challenge;
    private string $challengeId;
    private string $displayName;
    private string $email;
    private string $eventId;
    private string $hashedPassword;
    private string $initials;
    private string $optionsJson;
    private string $userId;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->challenge = $this->faker->sha256();
        $this->challengeId = $this->faker->uuid();
        $this->displayName = $this->faker->name();
        $this->email = $this->faker->safeEmail();
        $this->eventId = $this->faker->uuid();
        $this->hashedPassword = $this->faker->password();
        $this->initials = strtoupper($this->faker->lexify('??'));
        $this->optionsJson = \json_encode(['challenge' => $this->challenge], \JSON_THROW_ON_ERROR);
        $this->userId = $this->faker->uuid();
    }

    public function testCreateFromSignupChallengeUsesSecureRandomPasswordAndPublishesEvent(): void
    {
        $challenge = $this->createSignupChallenge();
        $uuidTransformer = new UuidTransformer(new UuidFactory());
        $user = $this->createExpectedUser($uuidTransformer);
        $event = new UserRegisteredEvent($user->getId(), $user->getEmail(), $this->eventId);

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
            $this->email,
            $this->initials,
            $this->hashedPassword,
            $uuidTransformer->transformFromString($this->userId)
        );
    }

    private function createPasswordHasherExpectingRandomPassword(): PasswordHasherInterface
    {
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->expects($this->once())
            ->method('hash')
            ->with(self::callback(static fn (string $password): bool => \strlen($password) === 64
                && \ctype_xdigit($password)))
            ->willReturn($this->hashedPassword);
        return $hasher;
    }

    private function createUserFactoryExpectingSignup(User $user): UserFactoryInterface
    {
        $userFactory = $this->createMock(UserFactoryInterface::class);
        $userFactory->expects($this->once())
            ->method('create')
            ->with(
                $this->email,
                $this->initials,
                $this->hashedPassword,
                self::callback(fn (UuidInterface $uuid): bool => (string) $uuid === $this->userId)
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
        $factory->expects($this->once())->method('generate')->willReturn($this->eventId);

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
            ->with($user, $this->eventId)
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
        $passwordHasher->method('hash')->willReturn($this->hashedPassword);

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
            $this->challengeId,
            PasskeyChallenge::PURPOSE_SIGNUP,
            $this->challenge,
            $this->optionsJson,
            $createdAt,
            $createdAt->modify('+5 minutes'),
            new PasskeyChallengeContext(
                $this->email,
                $this->initials,
                $this->displayName,
                $this->userId
            )
        );
    }
}
