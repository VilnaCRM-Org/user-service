<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as IdFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RegisterUserCommand;
use App\User\Application\CommandHandler\RegisterUserCommandHandler;
use App\User\Application\Factory\SignUpCommandFactory;
use App\User\Application\Factory\SignUpCommandFactoryInterface;
use App\User\Application\Service\EmailNormalizer;
use App\User\Application\Transformer\SignUpTransformer;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\UserRegisteredEvent;
use App\User\Domain\Factory\Event\UserRegisteredEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final class RegisterUserCommandHandlerTest extends UnitTestCase
{
    private RegisterUserCommandHandler $handler;
    private PasswordHasherFactoryInterface&MockObject $hasherFactory;
    private UserRepositoryInterface&MockObject $userRepository;
    private SignUpTransformer $transformer;
    private EventBusInterface&MockObject $eventBus;
    private UserRegisteredEventFactoryInterface&MockObject $registeredEventFactory;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private SignUpCommandFactoryInterface $signUpCommandFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->createCollaborators();
        $this->handler = $this->createHandler(
            $this->hasherFactory,
            $this->userRepository,
            $this->transformer,
            $this->eventBus,
            $this->registeredEventFactory
        );
    }

    public function testInvokeCreatesUserWithNormalizedEmail(): void
    {
        [$command, $normalizedEmail, $initials] =
            $this->createRegistrationFixture();

        $this->expectNoEmailLookup();
        $this->expectUserCreation(
            $normalizedEmail,
            $initials
        );

        $this->handler->__invoke($command);
    }

    public function testInvokeRethrowsSaveErrorWithoutPublishingRegistrationEvent(): void
    {
        [$command, $normalizedEmail, $initials] =
            $this->createRegistrationFixture();
        $error = new RuntimeException('Persistence failed.');

        $this->expectNoEmailLookup();
        $hashedPassword = $this->expectPasswordHash();
        $this->expectSaveFailure(
            $normalizedEmail,
            $initials,
            $hashedPassword,
            $error
        );
        $this->expectNoRegistrationEvent();

        $this->expectExceptionObject($error);

        $this->handler->__invoke($command);
    }

    private function createCollaborators(): void
    {
        $this->hasherFactory =
            $this->createMock(PasswordHasherFactoryInterface::class);
        $this->userRepository =
            $this->createMock(UserRepositoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new IdFactory());
        $this->transformer = new SignUpTransformer(
            $this->userFactory,
            $this->uuidTransformer,
            new UuidFactory()
        );
        $this->signUpCommandFactory = new SignUpCommandFactory();
        $this->registeredEventFactory =
            $this->createMock(UserRegisteredEventFactoryInterface::class);
    }

    private function createHandler(
        PasswordHasherFactoryInterface $hasherFactory,
        UserRepositoryInterface $userRepository,
        SignUpTransformer $transformer,
        EventBusInterface $eventBus,
        UserRegisteredEventFactoryInterface $registeredEventFactory,
    ): RegisterUserCommandHandler {
        return new RegisterUserCommandHandler(
            $hasherFactory,
            $userRepository,
            $transformer,
            $eventBus,
            new UuidFactory(),
            $registeredEventFactory,
            new EmailNormalizer()
        );
    }

    private function expectUserCreation(
        string $email,
        string $initials,
    ): void {
        $hashedPassword = $this->expectPasswordHash();
        $this->expectUserSave($email, $initials, $hashedPassword);
        $this->expectRegistrationEvent($email, $initials, $hashedPassword);
    }

    private function expectRegistrationEvent(
        string $email,
        string $initials,
        string $password,
    ): void {
        $event = new UserRegisteredEvent(
            $this->faker->uuid(),
            $email,
            $this->faker->uuid()
        );
        $this->registeredEventFactory->expects($this->once())
            ->method('create')
            ->with(
                $this->callback(
                    $this->userMatchesCallback($email, $initials, $password)
                ),
                $this->isType('string')
            )
            ->willReturn($event);
        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($event);
    }

    private function expectUserSave(
        string $email,
        string $initials,
        string $password,
    ): void {
        $this->userRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(
                $this->userMatchesCallback($email, $initials, $password)
            ));
    }

    private function expectSaveFailure(
        string $email,
        string $initials,
        string $password,
        RuntimeException $error,
    ): void {
        $this->userRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(
                $this->userMatchesCallback($email, $initials, $password)
            ))
            ->willThrowException($error);
    }

    private function expectNoRegistrationEvent(): void
    {
        $this->registeredEventFactory->expects($this->never())
            ->method('create');
        $this->eventBus->expects($this->never())
            ->method('publish');
    }

    private function expectNoEmailLookup(): void
    {
        $this->userRepository->expects($this->never())
            ->method('findByEmail');
    }

    private function userMatches(
        UserInterface $user,
        string $email,
        string $initials,
        string $password,
    ): bool {
        return $user->getEmail() === $email
            && $user->getInitials() === $initials
            && $user->getPassword() === $password;
    }

    private function userMatchesCallback(
        string $email,
        string $initials,
        string $password,
    ): callable {
        return fn (UserInterface $user): bool => $this->userMatches(
            $user,
            $email,
            $initials,
            $password
        );
    }

    private function expectPasswordHash(): string
    {
        $hashedPassword = $this->faker->password();
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->expects($this->once())
            ->method('hash')
            ->willReturn($hashedPassword);
        $this->hasherFactory->expects($this->once())
            ->method('getPasswordHasher')
            ->with(User::class)
            ->willReturn($hasher);

        return $hashedPassword;
    }

    /**
     * @return array{RegisterUserCommand,string,string}
     */
    private function createRegistrationFixture(): array
    {
        [$email, $normalizedEmail] = $this->createEmailFixture();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $command =
            $this->signUpCommandFactory->create($email, $initials, $password);

        return [$command, $normalizedEmail, $initials];
    }

    /**
     * @return array{string,string}
     */
    private function createEmailFixture(): array
    {
        $email = ' ' . "\u{00C4}" . strtoupper($this->faker->safeEmail()) . ' ';

        return [$email, mb_strtolower(trim($email))];
    }
}
