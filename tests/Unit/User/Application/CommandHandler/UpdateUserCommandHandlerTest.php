<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as UuidFactoryInterface;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\CommandHandler\UpdateUserCommandHandler;
use App\User\Application\Factory\UpdateUserCommandFactory;
use App\User\Application\Factory\UpdateUserCommandFactoryInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\InvalidPasswordException;
use App\User\Domain\Factory\Event\EmailChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\PasswordChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\UserUpdatedEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserUpdate;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Uid\Uuid as SymfonyUuid;

final class UpdateUserCommandHandlerTest extends UnitTestCase
{
    private EventBusInterface $eventBus;
    private PasswordHasherFactoryInterface $hasherFactory;
    private UserRepositoryInterface $userRepository;
    private UuidFactory $uuidFactory;
    private EmailChangedEventFactoryInterface $emailChangedEventFactory;
    private PasswordChangedEventFactoryInterface $passwordChangedFactory;
    private UserUpdatedEventFactoryInterface $userUpdatedEventFactory;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private UpdateUserCommandFactoryInterface $updateUserCommandFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initMocks();
        $this->initFactories();
    }

    public function testInvoke(): void
    {
        $user = $this->createUser();
        $updateData = $this->createUpdateData();
        $command = $this->updateUserCommandFactory->create($user, $updateData);

        $this->testInvokeSetExpectations($user);

        $this->getHandler()->__invoke($command);
    }

    public function testInvokeInvalidPassword(): void
    {
        $user = $this->createUser();
        $updateData = $this->createUpdateData();
        $command = $this->updateUserCommandFactory->create($user, $updateData);

        $this->testInvokeInvalidPasswordSetExpectations();

        $this->expectException(InvalidPasswordException::class);

        $this->getHandler()->__invoke($command);
    }

    private function testInvokeInvalidPasswordSetExpectations(): void
    {
        $this->expectPasswordVerification(false);
    }

    private function testInvokeSetExpectations(
        UserInterface $user
    ): void {
        $this->expectUuidFactory();
        $this->expectPasswordVerification(true);
        $this->expectUserSave($user);
        $this->expectEventFactories($user);
        $this->expectEventBusPublish();
    }

    private function createUser(): UserInterface
    {
        $email = $this->faker->email();
        $initials = $this->faker->firstName() . ' ' . $this->faker->lastName();
        $password = $this->faker->password();
        $userId = $this->uuidTransformer->transformFromString($this->faker->uuid());

        return $this->userFactory->create($email, $initials, $password, $userId);
    }

    private function createUpdateData(): UserUpdate
    {
        $oldPassword = $this->faker->password();
        $newPassword = $this->faker->password();

        return new UserUpdate(
            $this->faker->email(),
            $this->faker->firstName(),
            $newPassword,
            $oldPassword,
        );
    }

    private function expectUuidFactory(): void
    {
        $this->uuidFactory->expects($this->once())
            ->method('create')
            ->willReturn(new SymfonyUuid($this->faker->uuid()));
    }

    private function expectPasswordVerification(bool $isValid): void
    {
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->expects($this->once())
            ->method('verify')
            ->willReturn($isValid);
        $this->hasherFactory->expects($this->once())
            ->method('getPasswordHasher')
            ->willReturn($hasher);
    }

    private function expectUserSave(UserInterface $user): void
    {
        $this->userRepository->expects($this->once())
            ->method('save')
            ->with($this->equalTo($user));
    }

    private function expectEventFactories(UserInterface $user): void
    {
        $this->emailChangedEventFactory->expects($this->once())
            ->method('create');
        $this->passwordChangedFactory->expects($this->once())
            ->method('create');
        $this->expectUserUpdatedEvent($user);
    }

    private function expectUserUpdatedEvent(UserInterface $user): void
    {
        $this->userUpdatedEventFactory->expects($this->once())
            ->method('create')
            ->with($user, $user->getEmail(), $this->anything())
            ->willReturn(new \App\User\Domain\Event\UserUpdatedEvent(
                $user->getId(),
                $user->getEmail(),
                $user->getEmail(),
                $this->faker->uuid()
            ));
    }

    private function expectEventBusPublish(): void
    {
        $this->eventBus->expects($this->once())
            ->method('publish');
    }

    private function initMocks(): void
    {
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->hasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->uuidFactory = $this->createMock(UuidFactory::class);
        $this->emailChangedEventFactory = $this->createMock(
            EmailChangedEventFactoryInterface::class
        );
        $this->passwordChangedFactory = $this->createMock(
            PasswordChangedEventFactoryInterface::class
        );
        $this->userUpdatedEventFactory = $this->createMock(
            UserUpdatedEventFactoryInterface::class
        );
    }

    private function initFactories(): void
    {
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(
            new UuidFactoryInterface()
        );
        $this->updateUserCommandFactory = new UpdateUserCommandFactory();
    }

    private function getHandler(): UpdateUserCommandHandler
    {
        return new UpdateUserCommandHandler(
            $this->eventBus,
            $this->hasherFactory,
            $this->userRepository,
            $this->uuidFactory,
            $this->emailChangedEventFactory,
            $this->passwordChangedFactory,
            $this->userUpdatedEventFactory
        );
    }
}
