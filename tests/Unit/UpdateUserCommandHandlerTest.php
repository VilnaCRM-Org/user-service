<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Application\Command\UpdateUserCommand;
use App\User\Application\CommandHandler\UpdateUserCommandHandler;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\Event\EmailChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\PasswordChangedEventFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserUpdateData;
use App\User\Infrastructure\Exception\InvalidPasswordException;
use DG\BypassFinals;
use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Uid\Uuid as SymfonyUuid;

class UpdateUserCommandHandlerTest extends TestCase
{
    private UpdateUserCommandHandler $handler;
    private EventBusInterface $eventBus;
    private PasswordHasherFactoryInterface $hasherFactory;
    private UserRepositoryInterface $userRepository;
    private UuidFactory $uuidFactory;
    private EmailChangedEventFactoryInterface $emailChangedEventFactory;
    private PasswordChangedEventFactoryInterface $passwordChangedFactory;
    private Generator $faker;

    protected function setUp(): void
    {
        BypassFinals::enable(bypassReadOnly: false);
        parent::setUp();

        $this->faker = Factory::create();

        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->hasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->uuidFactory = $this->createMock(UuidFactory::class);
        $this->emailChangedEventFactory = $this->createMock(EmailChangedEventFactoryInterface::class);
        $this->passwordChangedFactory = $this->createMock(PasswordChangedEventFactoryInterface::class);

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
        $user = new User(
            $this->faker->email(),
            $this->faker->firstName() . ' ' . $this->faker->lastName(),
            $this->faker->password(),
            new Uuid($this->faker->uuid())
        );

        $oldPassword = $this->faker->password();
        $newPassword = $this->faker->password();
        $updateData = new UserUpdateData(
            $this->faker->email(),
            $this->faker->firstName(),
            $newPassword,
            $oldPassword,
        );
        $command = new UpdateUserCommand($user, $updateData);

        $this->uuidFactory->expects($this->once())
            ->method('create')
            ->willReturn(new SymfonyUuid($this->faker->uuid()));

        $hasher = $this->createMock(PasswordHasherInterface::class);
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
        $user = new User(
            $this->faker->email(),
            $this->faker->firstName() . ' ' . $this->faker->lastName(),
            $this->faker->password(),
            new Uuid($this->faker->uuid())
        );

        $oldPassword = $this->faker->password();
        $newPassword = $this->faker->password();
        $updateData = new UserUpdateData(
            $this->faker->email(),
            $this->faker->firstName(),
            $newPassword,
            $oldPassword,
        );
        $command = new UpdateUserCommand($user, $updateData);

        $hasher = $this->createMock(PasswordHasherInterface::class);
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
