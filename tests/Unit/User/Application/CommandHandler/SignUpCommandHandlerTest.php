<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as IdFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\CommandHandler\RegisterUserCommandHandler;
use App\User\Application\Factory\SignUpCommandFactory;
use App\User\Application\Factory\SignUpCommandFactoryInterface;
use App\User\Application\Transformer\SignUpTransformer;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\Event\UserRegisteredEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final class SignUpCommandHandlerTest extends UnitTestCase
{
    private RegisterUserCommandHandler $handler;
    private PasswordHasherFactoryInterface $hasherFactory;
    private UserRepositoryInterface $userRepository;
    private SignUpTransformer $transformer;
    private EventBusInterface $eventBus;
    private UserRegisteredEventFactoryInterface $registeredEventFactory;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private SignUpCommandFactoryInterface $signUpCommandFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasherFactory =
            $this->createMock(PasswordHasherFactoryInterface::class);
        $this->userRepository =
            $this->createMock(UserRepositoryInterface::class);
        $this->transformer = $this->createMock(SignUpTransformer::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new IdFactory());
        $this->signUpCommandFactory = new SignUpCommandFactory();
        $this->registeredEventFactory =
            $this->createMock(UserRegisteredEventFactoryInterface::class);

        $this->handler = new RegisterUserCommandHandler(
            $this->hasherFactory,
            $this->userRepository,
            $this->transformer,
            $this->eventBus,
            new UuidFactory(),
            $this->registeredEventFactory
        );
    }

    public function testInvoke(): void
    {
        $email = $this->faker->email();
        $initials =
            $this->faker->firstName() . ' ' . $this->faker->lastName();
        $password = $this->faker->password();
        $userId =
            $this->uuidTransformer->transformFromString($this->faker->uuid());
        $user =
            $this->userFactory->create($email, $initials, $password, $userId);
        $command =
            $this->signUpCommandFactory->create($email, $initials, $password);

        $this->setExpectations($user);

        $this->handler->__invoke($command);
    }

    private function setExpectations(
        UserInterface $user
    ): void {
        $this->transformer->expects($this->once())
            ->method('transformToUser')
            ->willReturn($user);

        $hasher =
            $this->createMock(PasswordHasherInterface::class);
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
    }
}
