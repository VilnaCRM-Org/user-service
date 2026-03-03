<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Applier\UserUpdateApplierInterface;
use App\User\Application\Command\UpdateUserCommand;
use App\User\Application\CommandHandler\UpdateUserCommandHandler;
use App\User\Application\Generator\EventIdGeneratorInterface;
use App\User\Application\Hasher\PasswordHasherInterface;
use App\User\Application\Revoker\PasswordChangeSessionRevoker;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\AllSessionsRevokedEvent;
use App\User\Domain\Event\EmailChangedEvent;
use App\User\Domain\Event\PasswordChangedEvent;
use App\User\Domain\Event\UserUpdatedEvent;
use App\User\Domain\Exception\InvalidPasswordException;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\ValueObject\UserUpdate;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Uuid as SymfonyUuid;

final class UpdateUserCommandHandlerTest extends UnitTestCase
{
    private EventBusInterface&MockObject $eventBus;
    private PasswordHasherInterface&MockObject $passwordHasher;
    private UserUpdateApplierInterface&MockObject $userUpdateApplier;
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

        $this->userUpdateApplier->expects($this->never())->method('apply');
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

        $otherSession = $this->createOtherSession('other-session-id', $user->getId());
        $this->authSessionRepository->method('findByUserId')
            ->willReturn([$otherSession]);
        $this->authSessionRepository->method('save');
        $this->authRefreshTokenRepository->method('findBySessionId')->willReturn([]);

        $this->userUpdateApplier->expects($this->once())
            ->method('apply')
            ->willReturn($this->buildDomainEvents($user));

        $publishedEvents = [];
        $this->expectEventPublish($publishedEvents);

        $this->createHandler()->__invoke($command);

        $this->assertSessionRevokedEvent($publishedEvents, $user->getId(), 'password_change', 1);
    }

    public function testInvokeDoesNotRevokeSessionsWhenPasswordIsNotChanged(): void
    {
        $user = $this->createUser();
        $unchangedPassword = $this->faker->password();
        $updateData = $this->createUnchangedPasswordUpdate($user, $unchangedPassword);
        $command = new UpdateUserCommand($user, $updateData, $this->faker->uuid());

        $this->expectEventIdGenerator();
        $this->expectPasswordHasher(true);

        $this->authSessionRepository->expects($this->never())->method('findByUserId');

        $this->userUpdateApplier->expects($this->once())
            ->method('apply')
            ->willReturn($this->buildDomainEvents($user));

        $publishedEvents = [];
        $this->expectEventPublish($publishedEvents);

        $this->createHandler()->__invoke($command);

        $this->assertNull($this->findAllSessionsRevokedEvent($publishedEvents));
        $this->assertCount(3, $publishedEvents);
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

    /**
     * @return array<int, DomainEvent>
     */
    private function buildDomainEvents(UserInterface $user): array
    {
        $eventId = $this->faker->uuid();

        return [
            new UserUpdatedEvent(
                $user->getId(),
                $user->getEmail(),
                null,
                $eventId
            ),
            new EmailChangedEvent(
                $user->getId(),
                $user->getEmail(),
                $user->getEmail(),
                $eventId
            ),
            new PasswordChangedEvent(
                $user->getEmail(),
                $eventId
            ),
        ];
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
        $this->userUpdateApplier = $this->createMock(UserUpdateApplierInterface::class);
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
        $passwordChangeSessionRevoker = new PasswordChangeSessionRevoker(
            $this->authSessionRepository,
            $this->authRefreshTokenRepository,
        );

        return new UpdateUserCommandHandler(
            $this->eventBus,
            $this->passwordHasher,
            $this->userUpdateApplier,
            $passwordChangeSessionRevoker,
            $this->eventIdGenerator
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

    private function createUnchangedPasswordUpdate(
        UserInterface $user,
        string $password
    ): UserUpdate {
        return new UserUpdate(
            $user->getEmail(),
            $this->faker->firstName(),
            $password,
            $password,
        );
    }
}
