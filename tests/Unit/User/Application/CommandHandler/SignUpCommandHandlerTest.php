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

    #[\Override]
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

    public function testInvokeReturnsExistingUserWhenEmailAlreadyRegistered(): void
    {
        $testData = $this->createTestDataForExistingUser();
        $existingUser = $testData['existingUser'];
        $command = $testData['command'];
        $email = $testData['email'];

        $this->setupExistingUserExpectations($email, $existingUser);
        $this->setupNeverCalledForSignup();

        $this->handler->__invoke($command);

        $this->assertSame($existingUser, $command->getResponse()->createdUser);
    }

    private function setExpectations(
        UserInterface $user
    ): void {
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->willReturn(null);

        $this->transformer->expects($this->once())
            ->method('transformToUser')
            ->willReturn($user);

        $this->setupPasswordHasherExpectations();
        $this->setupSaveAndEventExpectations($user);
    }

    /**
     * @return array<string, string|UserInterface|SignUpCommand>
     */
    private function createTestDataForExistingUser(): array
    {
        $email = $this->faker->email();
        $password = $this->faker->password();
        $initials = $this->faker->firstName();
        $userId = $this->uuidTransformer->transformFromString($this->faker->uuid());
        $existingUser = $this->userFactory->create($email, $initials, $password, $userId);
        $command = $this->signUpCommandFactory->create($email, $initials, $password);

        return [
            'email' => $email,
            'existingUser' => $existingUser,
            'command' => $command,
        ];
    }

    private function setupExistingUserExpectations(
        string $email,
        UserInterface $existingUser
    ): void {
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($existingUser);
    }

    private function setupNeverCalledForSignup(): void
    {
        $this->transformer->expects($this->never())
            ->method('transformToUser');
        $this->hasherFactory->expects($this->never())
            ->method('getPasswordHasher');
        $this->userRepository->expects($this->never())
            ->method('save');
        $this->registeredEventFactory->expects($this->never())
            ->method('create');
        $this->eventBus->expects($this->never())
            ->method('publish');
    }

    private function setupPasswordHasherExpectations(): void
    {
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->expects($this->once())
            ->method('hash')
            ->willReturn($this->faker->password());
        $this->hasherFactory->expects($this->once())
            ->method('getPasswordHasher')
            ->willReturn($hasher);
    }

    private function setupSaveAndEventExpectations(UserInterface $user): void
    {
        $this->userRepository->expects($this->once())
            ->method('save')
            ->with($this->equalTo($user));

        $this->registeredEventFactory->expects($this->once())
            ->method('create');

        $this->eventBus->expects($this->once())
            ->method('publish');
    }
}
