<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Application\Command\SignUpCommand;
use App\User\Application\CommandHandler\SignUpCommandHandler;
use App\User\Application\Transformer\SignUpTransformer;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\Event\UserRegisteredEventFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DG\BypassFinals;
use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

class SignUpCommandHandlerTest extends TestCase
{
    private SignUpCommandHandler $handler;
    private PasswordHasherFactoryInterface $hasherFactory;
    private UserRepositoryInterface $userRepository;
    private SignUpTransformer $transformer;
    private EventBusInterface $eventBus;
    private UuidFactory $uuidFactory;
    private UserRegisteredEventFactoryInterface $registeredEventFactory;
    private Generator $faker;

    protected function setUp(): void
    {
        BypassFinals::enable(bypassReadOnly: false);
        parent::setUp();

        $this->faker = Factory::create();

        // Mock dependencies
        $this->hasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->transformer = $this->createMock(SignUpTransformer::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->uuidFactory = new UuidFactory();
        $this->registeredEventFactory = $this->createMock(UserRegisteredEventFactoryInterface::class);

        // Create the handler instance
        $this->handler = new SignUpCommandHandler(
            $this->hasherFactory,
            $this->userRepository,
            $this->transformer,
            $this->eventBus,
            $this->uuidFactory,
            $this->registeredEventFactory
        );
    }

    public function testInvoke(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->firstName() . ' ' . $this->faker->lastName();
        $password = $this->faker->password();
        $userId = $this->faker->uuid();

        $user = new User($email, $initials, $password, new Uuid($userId));

        $command = new SignUpCommand($email, $initials, $password);

        $this->transformer->expects($this->once())
            ->method('transformToUser')
            ->willReturn($user);

        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->expects($this->once())
            ->method('hash')
            ->willReturn($this->faker->password());
        $this->hasherFactory->expects($this->once())
            ->method('getPasswordHasher')
            ->willReturn($hasher);

        $this->userRepository->expects($this->once())
            ->method('save')
            ->with($this->equalTo($user));

        $this->registeredEventFactory->expects($this->once())
            ->method('create');

        $this->eventBus->expects($this->once())
            ->method('publish');

        // Invoke the handler
        $this->handler->__invoke($command);
    }
}
