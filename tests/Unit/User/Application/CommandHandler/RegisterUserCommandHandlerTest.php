<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as IdFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RegisterUserCommand;
use App\User\Application\CommandHandler\RegisterUserCommandHandler;
use App\User\Application\DTO\RegisterUserCommandResponse;
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
    private SignUpTransformer&MockObject $transformer;
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
        $this->handler = $this->createHandler();
    }

    public function testInvokeCreatesUserWithNormalizedEmail(): void
    {
        [$email, $normalizedEmail] = $this->createEmailFixture();
        $initials =
            $this->faker->firstName() . ' ' . $this->faker->lastName();
        $password = $this->faker->password();
        $user = $this->createUser($normalizedEmail, $initials, $password);
        $command =
            $this->signUpCommandFactory->create($email, $initials, $password);

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($normalizedEmail)
            ->willReturn(null);
        $this->expectUserCreation($normalizedEmail, $initials, $password, $user);

        $response = $this->handler->__invoke($command);

        $this->assertInstanceOf(RegisterUserCommandResponse::class, $response);
        $this->assertSame($user, $response->createdUser);
    }

    public function testInvokeReturnsExistingUserWhenEmailAlreadyRegistered(): void
    {
        [$email, $normalizedEmail] = $this->createEmailFixture();
        $existingUser = $this->createMock(UserInterface::class);
        $command = $this->signUpCommandFactory->create(
            $email,
            $this->faker->name(),
            $this->faker->password()
        );

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($normalizedEmail)
            ->willReturn($existingUser);
        $this->expectNoUserCreation();

        $response = $this->handler->__invoke($command);

        $this->assertSame($existingUser, $response->createdUser);
    }

    public function testInvokeReturnsConcurrentWinnerWhenSaveHitsEmailRace(): void
    {
        [$command, $normalizedEmail, $initials, $password, $user] =
            $this->createRegistrationFixture();
        $raceWinner = $this->createMock(UserInterface::class);

        $this->expectTwoEmailLookups($normalizedEmail, null, $raceWinner);
        $this->expectUserTransformAndHash(
            $normalizedEmail,
            $initials,
            $password,
            $user
        );
        $this->expectSaveFailure($user, new RuntimeException('Duplicate email.'));
        $this->expectNoRegistrationEvent();

        $response = $this->handler->__invoke($command);

        $this->assertSame($raceWinner, $response->createdUser);
    }

    public function testInvokeRethrowsSaveErrorWhenNoConcurrentUserExists(): void
    {
        [$command, $normalizedEmail, $initials, $password, $user] =
            $this->createRegistrationFixture();
        $error = new RuntimeException('Persistence failed.');

        $this->expectTwoEmailLookups($normalizedEmail, null, null);
        $this->expectUserTransformAndHash(
            $normalizedEmail,
            $initials,
            $password,
            $user
        );
        $this->expectSaveFailure($user, $error);
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
        $this->transformer = $this->createMock(SignUpTransformer::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new IdFactory());
        $this->signUpCommandFactory = new SignUpCommandFactory();
        $this->registeredEventFactory =
            $this->createMock(UserRegisteredEventFactoryInterface::class);
    }

    private function createHandler(): RegisterUserCommandHandler
    {
        return new RegisterUserCommandHandler(
            $this->hasherFactory,
            $this->userRepository,
            $this->transformer,
            $this->eventBus,
            new UuidFactory(),
            $this->registeredEventFactory,
            new EmailNormalizer()
        );
    }

    private function expectUserCreation(
        string $email,
        string $initials,
        string $password,
        User $user,
    ): void {
        $this->expectUserTransformAndHash($email, $initials, $password, $user);
        $this->expectUserSave($user);
        $this->expectRegistrationEvent($email, $user);
    }

    private function expectRegistrationEvent(string $email, User $user): void
    {
        $event = new UserRegisteredEvent(
            (string) $user->getId(),
            $email,
            $this->faker->uuid()
        );
        $this->registeredEventFactory->expects($this->once())
            ->method('create')
            ->with($user, $this->isType('string'))
            ->willReturn($event);
        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($event);
    }

    private function expectUserSave(User $user): void
    {
        $this->userRepository->expects($this->once())
            ->method('save')
            ->with($user);
    }

    private function expectTwoEmailLookups(
        string $email,
        ?UserInterface $firstResult,
        ?UserInterface $secondResult,
    ): void {
        $this->userRepository->expects($this->exactly(2))
            ->method('findByEmail')
            ->with($email)
            ->willReturnOnConsecutiveCalls($firstResult, $secondResult);
    }

    private function expectSaveFailure(
        User $user,
        RuntimeException $error,
    ): void {
        $this->userRepository->expects($this->once())
            ->method('save')
            ->with($user)
            ->willThrowException($error);
    }

    private function expectNoRegistrationEvent(): void
    {
        $this->registeredEventFactory->expects($this->never())
            ->method('create');
        $this->eventBus->expects($this->never())
            ->method('publish');
    }

    private function expectUserTransformAndHash(
        string $email,
        string $initials,
        string $password,
        User $user,
    ): void {
        $this->expectUserTransform($email, $initials, $password, $user);
        $this->expectPasswordHash();
    }

    private function expectUserTransform(
        string $email,
        string $initials,
        string $password,
        User $user,
    ): void {
        $this->transformer->expects($this->once())
            ->method('transformToUser')
            ->with($this->callback(
                static fn (RegisterUserCommand $command): bool => $command->email === $email
                    && $command->initials === $initials
                    && $command->password === $password
            ))
            ->willReturn($user);
    }

    private function expectPasswordHash(): void
    {
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->expects($this->once())
            ->method('hash')
            ->willReturn($this->faker->password());
        $this->hasherFactory->expects($this->once())
            ->method('getPasswordHasher')
            ->with(User::class)
            ->willReturn($hasher);
    }

    private function expectNoUserCreation(): void
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

    private function createUser(
        string $email,
        string $initials,
        string $password,
    ): User {
        return $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
    }

    /**
     * @return array{RegisterUserCommand,string,string,string,User}
     */
    private function createRegistrationFixture(): array
    {
        [$email, $normalizedEmail] = $this->createEmailFixture();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $user = $this->createUser($normalizedEmail, $initials, $password);
        $command =
            $this->signUpCommandFactory->create($email, $initials, $password);

        return [$command, $normalizedEmail, $initials, $password, $user];
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
