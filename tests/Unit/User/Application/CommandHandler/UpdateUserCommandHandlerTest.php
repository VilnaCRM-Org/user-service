<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\UpdateUserCommand;
use App\User\Application\CommandHandler\UpdateUserCommandHandler;
use App\User\Application\Factory\Generator\EventIdGeneratorInterface;
use App\User\Application\Transformer\PasswordHasherInterface;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\AllSessionsRevokedEvent;
use App\User\Domain\Event\EmailChangedEvent;
use App\User\Domain\Event\PasswordChangedEvent;
use App\User\Domain\Event\UserUpdatedEvent;
use App\User\Domain\Exception\InvalidPasswordException;
use App\User\Domain\Factory\Event\EmailChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\PasswordChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\UserUpdatedEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserUpdate;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Uuid as SymfonyUuid;

final class UpdateUserCommandHandlerTest extends UnitTestCase
{
    private EventBusInterface&MockObject $eventBus;
    private PasswordHasherInterface&MockObject $passwordHasher;
    private UserRepositoryInterface&MockObject $userRepository;
    private EmailChangedEventFactoryInterface&MockObject $emailChangedEventFactory;
    private PasswordChangedEventFactoryInterface&MockObject $passwordChangedFactory;
    private UserUpdatedEventFactoryInterface&MockObject $userUpdatedEventFactory;
    private AuthSessionRepositoryInterface&MockObject $authSessionRepository;
    private AuthRefreshTokenRepositoryInterface&MockObject $authRefreshTokenRepository;
    private EventIdGeneratorInterface&MockObject $eventIdGenerator;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->initMocks();
        $this->initFactories();
    }

    public function testInvokeInvalidPassword(): void
    {
        $user = $this->createUser();
        $updateData = $this->createUpdateData(
            $this->faker->password(),
            $this->faker->password()
        );
        $command = new UpdateUserCommand($user, $updateData, $this->faker->uuid());

        $this->passwordHasher->expects($this->once())
            ->method('verify')
            ->willReturn(false);

        $this->userRepository->expects($this->never())->method('save');
        $this->eventBus->expects($this->never())->method('publish');

        $this->expectException(InvalidPasswordException::class);

        $this->createHandler()->__invoke($command);
    }

    public function testInvokeRevokesOtherSessionsAndPublishesAuditEventAfterPasswordChange(): void
    {
        $user = $this->createUser();
        $currentSessionId = (string) new SymfonyUuid($this->faker->uuid());
        $updateData = $this->createUpdateData($this->faker->password(), $this->faker->password());
        $command = new UpdateUserCommand($user, $updateData, $currentSessionId);

        $this->expectEventIdGenerator();
        $this->expectPasswordHasher(true);
        $this->setupUpdateMocks($user);

        $otherSession = $this->createOtherSession('other-session-id', $user->getId());
        $this->authSessionRepository->method('findByUserId')
            ->willReturn([$otherSession]);
        $this->authSessionRepository->method('save');
        $this->authRefreshTokenRepository->method('findBySessionId')->willReturn([]);

        $this->userRepository->expects($this->once())->method('save')->with($user);

        $publishedEvents = [];
        $this->expectEventPublish($publishedEvents);

        $this->createHandler()->__invoke($command);

        $this->assertSessionRevokedEvent($publishedEvents, $user->getId(), 'password_change', 1);
    }

    public function testInvokeSkipsCurrentAndAlreadyRevokedSessionsWhileRevokingActiveRefreshTokens(): void
    {
        $user = $this->createUser();
        $currentSessionId = (string) new SymfonyUuid($this->faker->uuid());
        $updateData = $this->createUpdateData($this->faker->password(), $this->faker->password());
        $command = new UpdateUserCommand($user, $updateData, $currentSessionId);

        $this->expectEventIdGenerator();
        $this->expectPasswordHasher(true);
        $this->setupUpdateMocks($user);

        $currentSession = $this->createOtherSession($currentSessionId, $user->getId());
        $revokedSession = $this->createOtherSession($this->faker->uuid(), $user->getId());
        $revokedSession->revoke();
        $activeSession = $this->createOtherSession($this->faker->uuid(), $user->getId());

        $activeRefreshToken = new AuthRefreshToken(
            $this->faker->uuid(),
            $activeSession->getId(),
            $this->faker->sha256(),
            new DateTimeImmutable('+1 month')
        );
        $revokedRefreshToken = new AuthRefreshToken(
            $this->faker->uuid(),
            $activeSession->getId(),
            $this->faker->sha256(),
            new DateTimeImmutable('+1 month')
        );
        $revokedRefreshToken->revoke();

        $this->authSessionRepository->expects($this->once())
            ->method('findByUserId')
            ->with($user->getId())
            ->willReturn([$currentSession, $revokedSession, $activeSession]);
        $this->authSessionRepository->expects($this->once())
            ->method('save')
            ->with($activeSession);
        $this->authRefreshTokenRepository->expects($this->once())
            ->method('findBySessionId')
            ->with($activeSession->getId())
            ->willReturn([$revokedRefreshToken, $activeRefreshToken]);
        $this->authRefreshTokenRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (AuthRefreshToken $refreshToken): bool => $refreshToken->getId() === $activeRefreshToken->getId()
                    && $refreshToken->isRevoked()
            ));
        $this->userRepository->expects($this->once())->method('save')->with($user);

        $publishedEvents = [];
        $this->expectEventPublish($publishedEvents);

        $this->createHandler()->__invoke($command);

        $this->assertSessionRevokedEvent($publishedEvents, $user->getId(), 'password_change', 1);
    }

    public function testInvokePassesPreviousEmailToUserUpdatedEventFactoryWhenEmailChanges(): void
    {
        $user = $this->createUser();
        $previousEmail = $user->getEmail();
        $updateData = $this->createUpdateData($this->faker->password(), $this->faker->password());
        $command = new UpdateUserCommand($user, $updateData, $this->faker->uuid());

        $this->preparePasswordChangeScenario($user);
        $this->setupUpdateMocksForEmailChange($user, $previousEmail);

        $publishedEvents = [];
        $this->expectEventPublish($publishedEvents);
        $this->createHandler()->__invoke($command);

        $userUpdatedEvent = $this->findEventOfType($publishedEvents, UserUpdatedEvent::class);
        $this->assertNotNull($userUpdatedEvent);
        $this->assertSame($previousEmail, $userUpdatedEvent->previousEmail);
    }

    public function testInvokePublishesAllEventsIncludingUserUpdatedEvent(): void
    {
        $user = $this->createUser();
        $updateData = $this->createUpdateData($this->faker->password(), $this->faker->password());
        $command = new UpdateUserCommand($user, $updateData, $this->faker->uuid());

        $this->preparePasswordChangeScenario($user);
        $this->setupUpdateMocks($user);

        $publishedEvents = [];
        $this->expectEventPublish($publishedEvents);
        $this->createHandler()->__invoke($command);

        $this->assertNotNull($this->findEventOfType($publishedEvents, UserUpdatedEvent::class));
        $this->assertGreaterThanOrEqual(2, count($publishedEvents));
    }

    public function testInvokePublishesUserUpdatedEventWhenPasswordUnchanged(): void
    {
        $user = $this->createUser();
        $unchangedPassword = $this->faker->password();
        $updateData = $this->createUnchangedPasswordUpdate($user, $unchangedPassword);
        $command = new UpdateUserCommand($user, $updateData, $this->faker->uuid());

        $this->expectEventIdGenerator();
        $this->expectPasswordHasher(true);
        $this->setupUpdateMocks($user);
        $this->authSessionRepository->expects($this->never())->method('findByUserId');
        $this->userRepository->expects($this->once())->method('save')->with($user);

        $publishedEvents = [];
        $this->expectEventPublish($publishedEvents);
        $this->createHandler()->__invoke($command);

        $this->assertNotNull($this->findEventOfType($publishedEvents, UserUpdatedEvent::class));
    }

    public function testInvokeDoesNotRevokeSessionsWhenPasswordIsNotChanged(): void
    {
        $user = $this->createUser();
        $unchangedPassword = $this->faker->password();
        $updateData = $this->createUnchangedPasswordUpdate($user, $unchangedPassword);
        $command = new UpdateUserCommand($user, $updateData, $this->faker->uuid());

        $this->expectEventIdGenerator();
        $this->expectPasswordHasher(true);
        $this->setupUpdateMocks($user);

        $this->authSessionRepository->expects($this->never())->method('findByUserId');
        $this->userRepository->expects($this->once())->method('save')->with($user);

        $publishedEvents = [];
        $this->expectEventPublish($publishedEvents);

        $this->createHandler()->__invoke($command);

        $this->assertNull($this->findAllSessionsRevokedEvent($publishedEvents));
    }

    public function testInvokePublishesUserUpdatedEventWhenEmailChangesWithoutPasswordChange(): void
    {
        $user = $this->createUser();
        $previousEmail = $user->getEmail();
        $password = $this->faker->password();
        $updateData = $this->createUnchangedPasswordUpdate($user, $password, $this->faker->email());
        $command = new UpdateUserCommand($user, $updateData, $this->faker->uuid());

        $publishedEvents = [];
        $this->expectEventIdGenerator();
        $this->expectPasswordHasher(true);
        $this->setupUpdateMocksForEmailChange($user, $previousEmail);
        $this->authSessionRepository->expects($this->never())->method('findByUserId');
        $this->userRepository->expects($this->once())->method('save')->with($user);
        $this->expectEventPublish($publishedEvents);
        $this->createHandler()->__invoke($command);
        $userUpdatedEvent = $this->findEventOfType($publishedEvents, UserUpdatedEvent::class);
        $this->assertNotNull($userUpdatedEvent);
        $this->assertSame($previousEmail, $userUpdatedEvent->previousEmail);
        $this->assertNull($this->findAllSessionsRevokedEvent($publishedEvents));
    }

    private function createUser(): UserInterface
    {
        return $this->userFactory->create(
            $this->faker->email(),
            $this->faker->firstName() . ' ' . $this->faker->lastName(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
    }

    private function createUpdateData(
        string $oldPassword,
        string $newPassword
    ): UserUpdate {
        return new UserUpdate(
            $this->faker->email(),
            $this->faker->firstName(),
            $newPassword,
            $oldPassword,
        );
    }

    private function expectEventIdGenerator(): void
    {
        $this->eventIdGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn($this->faker->uuid());
    }

    private function expectPasswordHasher(bool $isValid): void
    {
        $this->passwordHasher->expects($this->once())
            ->method('verify')
            ->willReturn($isValid);

        if ($isValid) {
            $this->passwordHasher->expects($this->once())
                ->method('hash')
                ->willReturn($this->faker->sha256());
        } else {
            $this->passwordHasher->expects($this->never())->method('hash');
        }
    }

    private function setupUpdateMocks(UserInterface $user): void
    {
        $eventId = $this->faker->uuid();

        $this->emailChangedEventFactory->method('create')
            ->willReturn(new EmailChangedEvent(
                $user->getId(),
                $user->getEmail(),
                $user->getEmail(),
                $eventId
            ));

        $this->passwordChangedFactory->method('create')
            ->willReturn(new PasswordChangedEvent(
                $user->getEmail(),
                $eventId
            ));

        $this->userUpdatedEventFactory->method('create')
            ->willReturn(new UserUpdatedEvent(
                $user->getId(),
                $user->getEmail(),
                null,
                $eventId
            ));
    }

    /**
     * @param array<int, DomainEvent> $events
     */
    private function findAllSessionsRevokedEvent(array $events): ?AllSessionsRevokedEvent
    {
        foreach ($events as $event) {
            if ($event instanceof AllSessionsRevokedEvent) {
                return $event;
            }
        }

        return null;
    }

    private function initMocks(): void
    {
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->emailChangedEventFactory = $this->createMock(
            EmailChangedEventFactoryInterface::class
        );
        $this->passwordChangedFactory = $this->createMock(
            PasswordChangedEventFactoryInterface::class
        );
        $this->userUpdatedEventFactory = $this->createMock(
            UserUpdatedEventFactoryInterface::class
        );
        $this->authSessionRepository = $this->createMock(AuthSessionRepositoryInterface::class);
        $this->authRefreshTokenRepository = $this->createMock(
            AuthRefreshTokenRepositoryInterface::class
        );
        $this->eventIdGenerator = $this->createMock(EventIdGeneratorInterface::class);
    }

    private function initFactories(): void
    {
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(
            new SharedUuidFactory()
        );
    }

    private function createHandler(): UpdateUserCommandHandler
    {
        return new UpdateUserCommandHandler(
            $this->eventBus,
            $this->passwordHasher,
            $this->authSessionRepository,
            $this->authRefreshTokenRepository,
            $this->eventIdGenerator,
            $this->userRepository,
            $this->emailChangedEventFactory,
            $this->passwordChangedFactory,
            $this->userUpdatedEventFactory,
        );
    }

    private function createOtherSession(string $sessionId, string $userId): AuthSession
    {
        $createdAt = new DateTimeImmutable('-5 minutes');
        return new AuthSession(
            $sessionId,
            $userId,
            '127.0.0.1',
            'Test Agent',
            $createdAt,
            $createdAt->modify('+15 minutes'),
            false
        );
    }

    /**
     * @param array<int, DomainEvent> $publishedEvents
     */
    private function expectEventPublish(array &$publishedEvents): void
    {
        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->willReturnCallback(
                static function (DomainEvent ...$events) use (&$publishedEvents): void {
                    $publishedEvents = $events;
                }
            );
    }

    /**
     * @param array<int, DomainEvent> $events
     */
    private function assertSessionRevokedEvent(
        array $events,
        string $userId,
        string $reason,
        int $revokedCount
    ): void {
        $event = $this->findAllSessionsRevokedEvent($events);
        $this->assertInstanceOf(AllSessionsRevokedEvent::class, $event);
        $this->assertSame($userId, $event->userId);
        $this->assertSame($reason, $event->reason);
        $this->assertSame($revokedCount, $event->revokedCount);
    }

    private function preparePasswordChangeScenario(UserInterface $user): void
    {
        $this->expectEventIdGenerator();
        $this->expectPasswordHasher(true);

        $otherSession = $this->createOtherSession('other-session-id', $user->getId());
        $this->authSessionRepository->method('findByUserId')
            ->willReturn([$otherSession]);
        $this->authSessionRepository->method('save');
        $this->authRefreshTokenRepository->method('findBySessionId')->willReturn([]);
        $this->userRepository->expects($this->once())->method('save');
    }

    private function setupUpdateMocksForEmailChange(
        UserInterface $user,
        string $previousEmail
    ): void {
        $eventId = $this->faker->uuid();

        $this->emailChangedEventFactory->method('create')
            ->willReturn(new EmailChangedEvent(
                $user->getId(),
                $user->getEmail(),
                $previousEmail,
                $eventId
            ));
        $this->passwordChangedFactory->method('create')
            ->willReturn(new PasswordChangedEvent($user->getEmail(), $eventId));
        $event = new UserUpdatedEvent($user->getId(), $user->getEmail(), $previousEmail, $eventId);
        $this->userUpdatedEventFactory->expects($this->once())
            ->method('create')
            ->with($user, $previousEmail, $this->anything())
            ->willReturn($event);
    }

    /**
     * @template T of DomainEvent
     *
     * @param array<int, DomainEvent> $events
     * @param class-string<T> $type
     *
     * @return T|null
     */
    private function findEventOfType(array $events, string $type): ?DomainEvent
    {
        foreach ($events as $event) {
            if ($event instanceof $type) {
                return $event;
            }
        }

        return null;
    }

    private function createUnchangedPasswordUpdate(
        UserInterface $user,
        string $password,
        ?string $email = null
    ): UserUpdate {
        return new UserUpdate(
            $email ?? $user->getEmail(),
            $this->faker->firstName(),
            $password,
            $password,
        );
    }
}
