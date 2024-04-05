<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\CommandHandler\UpdateUserCommandHandler;
use App\User\Application\Factory\UpdateUserCommandFactory;
use App\User\Application\Factory\UpdateUserCommandFactoryInterface;
use App\User\Domain\Exception\InvalidPasswordException;
use App\User\Domain\Factory\Event\EmailChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\PasswordChangedEventFactoryInterface;
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
    private UpdateUserCommandHandler $handler;
    private EventBusInterface $eventBus;
    private PasswordHasherFactoryInterface $hasherFactory;
    private UserRepositoryInterface $userRepository;
    private UuidFactory $uuidFactory;
    private EmailChangedEventFactoryInterface $emailChangedEventFactory;
    private PasswordChangedEventFactoryInterface $passwordChangedFactory;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private UpdateUserCommandFactoryInterface $updateUserCommandFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->hasherFactory =
            $this->createMock(PasswordHasherFactoryInterface::class);
        $this->userRepository =
            $this->createMock(UserRepositoryInterface::class);
        $this->uuidFactory = $this->createMock(UuidFactory::class);
        $this->emailChangedEventFactory = $this->createMock(
            EmailChangedEventFactoryInterface::class
        );
        $this->passwordChangedFactory = $this->createMock(
            PasswordChangedEventFactoryInterface::class
        );
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer();
        $this->updateUserCommandFactory = new UpdateUserCommandFactory();

        $this->handler = new UpdateUserCommandHandler(
            $this->eventBus,
            $this->hasherFactory,
            $this->userRepository,
            $this->uuidFactory,
            $this->emailChangedEventFactory,
            $this->passwordChangedFactory
        );
    }

    public function testInvoke(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->firstName() . ' ' . $this->faker->lastName();
        $password = $this->faker->password();
        $userId =
            $this->uuidTransformer->transformFromString($this->faker->uuid());

        $user =
            $this->userFactory->create($email, $initials, $password, $userId);

        $oldPassword = $this->faker->password();
        $newPassword = $this->faker->password();
        $updateData = new UserUpdate(
            $this->faker->email(),
            $this->faker->firstName(),
            $newPassword,
            $oldPassword,
        );

        $command = $this->updateUserCommandFactory->create($user, $updateData);

        $this->uuidFactory->expects($this->once())
            ->method('create')
            ->willReturn(new SymfonyUuid($this->faker->uuid()));

        $hasher =
            $this->createMock(PasswordHasherInterface::class);
        $hasher->expects($this->once())
            ->method('verify')
            ->willReturn(true);
        $this->hasherFactory->expects($this->once())
            ->method('getPasswordHasher')
            ->willReturn($hasher);

        $this->userRepository->expects($this->once())
            ->method('save')
            ->with($this->equalTo($user));

        $this->emailChangedEventFactory->expects($this->once())
            ->method('create');

        $this->passwordChangedFactory->expects($this->once())
            ->method('create');

        $this->eventBus->expects($this->once())
            ->method('publish');

        $this->handler->__invoke($command);
    }

    public function testInvokeInvalidPassword(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->firstName() . ' ' . $this->faker->lastName();
        $password = $this->faker->password();
        $userId =
            $this->uuidTransformer->transformFromString($this->faker->uuid());

        $user =
            $this->userFactory->create($email, $initials, $password, $userId);

        $oldPassword = $this->faker->password();
        $newPassword = $this->faker->password();
        $updateData = new UserUpdate(
            $this->faker->email(),
            $this->faker->firstName(),
            $newPassword,
            $oldPassword,
        );

        $command = $this->updateUserCommandFactory->create($user, $updateData);

        $hasher =
            $this->createMock(PasswordHasherInterface::class);
        $hasher->expects($this->once())
            ->method('verify')
            ->willReturn(false);
        $this->hasherFactory->expects($this->once())
            ->method('getPasswordHasher')
            ->willReturn($hasher);

        $this->expectException(InvalidPasswordException::class);

        $this->handler->__invoke($command);
    }
}
