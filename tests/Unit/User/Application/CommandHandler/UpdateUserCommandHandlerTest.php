<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as UuidFactoryInterface;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\UpdateUserCommand;
use App\User\Application\CommandHandler\PasswordChangeSessionRevoker;
use App\User\Application\CommandHandler\UpdateUserCommandHandler;
use App\User\Application\CommandHandler\UserUpdateApplier;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\AllSessionsRevokedEvent;
use App\User\Domain\Event\UserUpdatedEvent;
use App\User\Domain\Exception\InvalidPasswordException;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\ValueObject\UserUpdate;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Uid\Uuid as SymfonyUuid;

final class UpdateUserCommandHandlerTest extends UnitTestCase
{
    private EventBusInterface $eventBus;
    private PasswordHasherFactoryInterface $hasherFactory;
    private UserUpdateApplier $userUpdateApplier;
    private PasswordChangeSessionRevoker $passwordChangeSessionRevoker;
    private UuidFactory $uuidFactory;
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

        $this->expectPasswordHasher(false);
        $this->userUpdateApplier->expects($this->never())->method('apply');
        $this->passwordChangeSessionRevoker
            ->expects($this->never())
            ->method('revokeOtherSessions');
        $this->eventBus->expects($this->never())->method('publish');

        $this->expectException(InvalidPasswordException::class);

        $this->createHandler()->__invoke($command);
    }

    public function testInvokeRevokesOtherSessionsAndPublishesAuditEventAfterPasswordChange(): void
    {
        $user = $this->createUser();
        $currentSessionId = (string) new SymfonyUuid($this->faker->uuid());
        $updateData = $this->createUpdateData(
            $this->faker->password(),
            $this->faker->password()
        );
        $command = new UpdateUserCommand($user, $updateData, $currentSessionId);

        $this->expectUuidFactory();
        $this->expectPasswordHasher(true);
        $this->expectUserUpdateApplied($user, $updateData);
        $this->passwordChangeSessionRevoker
            ->expects($this->once())
            ->method('revokeOtherSessions')
            ->with($user->getId(), $currentSessionId)
            ->willReturn(1);

        $publishedEvents = [];
        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->willReturnCallback(
                static function (DomainEvent ...$events) use (&$publishedEvents): void {
                    $publishedEvents = $events;
                }
            );

        $this->createHandler()->__invoke($command);

        $allSessionsRevokedEvent = $this->findAllSessionsRevokedEvent(
            $publishedEvents
        );
        $this->assertInstanceOf(AllSessionsRevokedEvent::class, $allSessionsRevokedEvent);
        $this->assertSame($user->getId(), $allSessionsRevokedEvent->userId);
        $this->assertSame('password_change', $allSessionsRevokedEvent->reason);
        $this->assertSame(1, $allSessionsRevokedEvent->revokedCount);
    }

    public function testInvokeDoesNotRevokeSessionsWhenPasswordIsNotChanged(): void
    {
        $user = $this->createUser();
        $unchangedPassword = $this->faker->password();
        $updateData = new UserUpdate(
            $user->getEmail(),
            $this->faker->firstName(),
            $unchangedPassword,
            $unchangedPassword,
        );
        $command = new UpdateUserCommand(
            $user,
            $updateData,
            $this->faker->uuid()
        );

        $this->expectUuidFactory();
        $this->expectPasswordHasher(true);
        $this->expectUserUpdateApplied($user, $updateData);
        $this->passwordChangeSessionRevoker
            ->expects($this->never())
            ->method('revokeOtherSessions');

        $publishedEvents = [];
        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->willReturnCallback(
                static function (DomainEvent ...$events) use (&$publishedEvents): void {
                    $publishedEvents = $events;
                }
            );

        $this->createHandler()->__invoke($command);

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

    private function expectUuidFactory(): void
    {
        $this->uuidFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn(new SymfonyUuid($this->faker->uuid()));
    }

    private function expectPasswordHasher(bool $isValid): void
    {
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher
            ->expects($this->once())
            ->method('verify')
            ->willReturn($isValid);

        if ($isValid) {
            $hasher
                ->expects($this->once())
                ->method('hash')
                ->willReturn($this->faker->sha256());
        } else {
            $hasher
                ->expects($this->never())
                ->method('hash');
        }

        $this->hasherFactory
            ->expects($this->once())
            ->method('getPasswordHasher')
            ->willReturn($hasher);
    }

    private function expectUserUpdateApplied(
        UserInterface $user,
        UserUpdate $updateData
    ): void {
        $this->userUpdateApplier
            ->expects($this->once())
            ->method('apply')
            ->with(
                $user,
                $updateData,
                $this->isType('string'),
                $this->isType('string')
            )
            ->willReturn([
                new UserUpdatedEvent(
                    $user->getId(),
                    $user->getEmail(),
                    null,
                    $this->faker->uuid()
                ),
            ]);
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
        $this->hasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $this->userUpdateApplier = $this->createMock(UserUpdateApplier::class);
        $this->passwordChangeSessionRevoker = $this->createMock(
            PasswordChangeSessionRevoker::class
        );
        $this->uuidFactory = $this->createMock(UuidFactory::class);
    }

    private function initFactories(): void
    {
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(
            new UuidFactoryInterface()
        );
    }

    private function createHandler(): UpdateUserCommandHandler
    {
        return new UpdateUserCommandHandler(
            $this->eventBus,
            $this->hasherFactory,
            $this->userUpdateApplier,
            $this->passwordChangeSessionRevoker,
            $this->uuidFactory
        );
    }
}
