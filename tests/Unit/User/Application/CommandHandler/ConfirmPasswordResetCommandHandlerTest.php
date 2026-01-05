<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\Command\ConfirmPasswordResetCommandResponse;
use App\User\Application\CommandHandler\ConfirmPasswordResetCommandHandler;
use App\User\Domain\Contract\PasswordHasherInterface;
use App\User\Domain\Contract\PasswordResetTokenValidatorInterface;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\PasswordResetConfirmedEvent;
use App\User\Domain\Exception\PasswordResetTokenAlreadyUsedException;
use App\User\Domain\Exception\PasswordResetTokenExpiredException;
use App\User\Domain\Exception\PasswordResetTokenNotFoundException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Factory\Event\PasswordResetConfirmedEventFactoryInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Uid\Uuid;

final class ConfirmPasswordResetCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private PasswordResetTokenRepositoryInterface&MockObject $tokenRepository;
    private PasswordHasherInterface&MockObject $passwordHasher;
    private EventBusInterface&MockObject $eventBus;
    private UuidFactory&MockObject $uuidFactory;
    private PasswordResetTokenValidatorInterface&MockObject $tokenValidator;
    private PasswordResetConfirmedEventFactoryInterface&MockObject $eventFactory;
    private ConfirmPasswordResetCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->tokenRepository = $this->createMock(PasswordResetTokenRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->uuidFactory = $this->createMock(UuidFactory::class);
        $this->tokenValidator = $this->createMock(PasswordResetTokenValidatorInterface::class);
        $this->eventFactory = $this->createMock(PasswordResetConfirmedEventFactoryInterface::class);

        $this->handler = new ConfirmPasswordResetCommandHandler(
            $this->tokenRepository,
            $this->userRepository,
            $this->passwordHasher,
            $this->eventBus,
            $this->uuidFactory,
            $this->tokenValidator,
            $this->eventFactory,
        );
    }

    public function testInvokeSuccessfully(): void
    {
        $testData = $this->createConfirmPasswordResetTestData();
        $command = new ConfirmPasswordResetCommand($testData['token'], $testData['newPassword']);
        $mocks = $this->createConfirmPasswordResetMocks($testData);

        $this->setupConfirmPasswordResetExpectations($testData, $mocks);

        $this->handler->__invoke($command);

        $response = $command->getResponse();
        $this->assertInstanceOf(ConfirmPasswordResetCommandResponse::class, $response);
    }

    public function testInvokePublishesEventWithCorrectUserId(): void
    {
        $testData = $this->createConfirmPasswordResetTestData();
        $command = new ConfirmPasswordResetCommand($testData['token'], $testData['newPassword']);
        $mocks = $this->createConfirmPasswordResetMocks($testData);

        $this->setupConfirmPasswordResetExpectations($testData, $mocks);

        $this->handler->__invoke($command);
    }

    public function testInvokeThrowsExceptionWhenTokenNotFound(): void
    {
        $token = $this->faker->lexify('??????????');
        $newPassword = $this->faker->password(12);

        $command = new ConfirmPasswordResetCommand($token, $newPassword);

        $this->tokenRepository->expects($this->once())
            ->method('findByToken')
            ->with($token)
            ->willReturn(null);

        $this->tokenValidator->expects($this->once())
            ->method('validate')
            ->with(null)
            ->willThrowException(new PasswordResetTokenNotFoundException());

        $this->expectException(PasswordResetTokenNotFoundException::class);

        $this->handler->__invoke($command);
    }

    public function testInvokeThrowsExceptionWhenTokenExpired(): void
    {
        $token = $this->faker->lexify('??????????');
        $newPassword = $this->faker->password(12);

        $command = new ConfirmPasswordResetCommand($token, $newPassword);

        $passwordResetToken = $this->createMock(PasswordResetTokenInterface::class);

        $this->tokenRepository->expects($this->once())
            ->method('findByToken')
            ->with($token)
            ->willReturn($passwordResetToken);

        $this->tokenValidator->expects($this->once())
            ->method('validate')
            ->with($passwordResetToken)
            ->willThrowException(new PasswordResetTokenExpiredException());

        $this->expectException(PasswordResetTokenExpiredException::class);

        $this->handler->__invoke($command);
    }

    public function testInvokeThrowsExceptionWhenTokenAlreadyUsed(): void
    {
        $token = $this->faker->lexify('??????????');
        $newPassword = $this->faker->password(12);

        $command = new ConfirmPasswordResetCommand($token, $newPassword);

        $passwordResetToken = $this->createMock(PasswordResetTokenInterface::class);

        $this->tokenRepository->expects($this->once())
            ->method('findByToken')
            ->with($token)
            ->willReturn($passwordResetToken);

        $this->tokenValidator->expects($this->once())
            ->method('validate')
            ->with($passwordResetToken)
            ->willThrowException(new PasswordResetTokenAlreadyUsedException());

        $this->expectException(PasswordResetTokenAlreadyUsedException::class);

        $this->handler->__invoke($command);
    }

    public function testInvokeThrowsExceptionWhenUserNotFound(): void
    {
        $testData = $this->createCommandWithTestData();
        $command = $testData['command'];
        $token = $testData['token'];

        $userId = $this->faker->uuid();
        $passwordResetToken = $this->createMockPasswordResetTokenWithUserId($userId);

        $this->setupUserNotFoundExpectations($token, $passwordResetToken, $userId);

        $this->expectException(UserNotFoundException::class);

        $this->handler->__invoke($command);
    }

    /**
     * @return array<string, string|ConfirmPasswordResetCommand>
     */
    private function createCommandWithTestData(): array
    {
        $token = $this->faker->lexify('??????????');
        $newPassword = $this->faker->password(12);
        $command = new ConfirmPasswordResetCommand($token, $newPassword);

        return [
            'token' => $token,
            'newPassword' => $newPassword,
            'command' => $command,
        ];
    }

    private function createMockPasswordResetTokenWithUserId(
        string $userId
    ): PasswordResetTokenInterface {
        $passwordResetToken = $this->createMock(PasswordResetTokenInterface::class);

        $passwordResetToken->expects($this->once())
            ->method('getUserID')
            ->willReturn($userId);

        return $passwordResetToken;
    }

    private function setupUserNotFoundExpectations(
        string $token,
        PasswordResetTokenInterface $passwordResetToken,
        string $userId
    ): void {
        $this->tokenRepository->expects($this->once())
            ->method('findByToken')
            ->with($token)
            ->willReturn($passwordResetToken);

        $this->tokenValidator->expects($this->once())
            ->method('validate')
            ->with($passwordResetToken);

        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn(null);
    }

    /**
     * @return array<string, string|Uuid>
     */
    private function createConfirmPasswordResetTestData(): array
    {
        return [
            'token' => $this->faker->lexify('??????????'),
            'newPassword' => $this->faker->password(12),
            'userId' => $this->faker->uuid(),
            'eventId' => Uuid::v4(),
            'hashedPassword' => $this->faker->sha256(),
        ];
    }

    /**
     * @param array<string, string|Uuid> $testData
     *
     * @return array<string, PasswordResetTokenInterface|User|PasswordResetConfirmedEvent>
     */
    private function createConfirmPasswordResetMocks(array $testData): array
    {
        $passwordResetToken = $this->createMock(PasswordResetTokenInterface::class);
        $passwordResetToken->expects($this->once())->method('markAsUsed');
        $passwordResetToken->expects($this->once())
            ->method('getUserID')
            ->willReturn($testData['userId']);

        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getId')->willReturn($testData['userId']);

        $event = $this->createMock(PasswordResetConfirmedEvent::class);

        return [
            'passwordResetToken' => $passwordResetToken,
            'user' => $user,
            'event' => $event,
        ];
    }

    /**
     * @param array<string, string|Uuid> $testData
     * @param array<string, PasswordResetTokenInterface|User|PasswordResetConfirmedEvent> $mocks
     */
    private function setupConfirmPasswordResetExpectations(array $testData, array $mocks): void
    {
        $this->setupTokenValidationExpectations($testData['token'], $mocks['passwordResetToken']);
        $this->setupUserUpdateExpectations($testData, $mocks['user']);
        $this->setupEventPublishingExpectations($testData, $mocks);
    }

    private function setupTokenValidationExpectations(
        string $token,
        PasswordResetTokenInterface $passwordResetToken
    ): void {
        $this->tokenRepository->expects($this->once())
            ->method('findByToken')
            ->with($token)
            ->willReturn($passwordResetToken);

        $this->tokenValidator->expects($this->once())
            ->method('validate')
            ->with($passwordResetToken);
    }

    /**
     * @param array<string, string|Uuid> $testData
     */
    private function setupUserUpdateExpectations(array $testData, User $user): void
    {
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($testData['userId'])
            ->willReturn($user);

        $this->passwordHasher->expects($this->once())
            ->method('hash')
            ->with($testData['newPassword'])
            ->willReturn($testData['hashedPassword']);

        $user->expects($this->once())->method('setPassword')->with($testData['hashedPassword']);
        $this->userRepository->expects($this->once())->method('save')->with($user);
    }

    /**
     * @param array<string, string|Uuid> $testData
     * @param array<string, PasswordResetTokenInterface|User|PasswordResetConfirmedEvent> $mocks
     */
    private function setupEventPublishingExpectations(array $testData, array $mocks): void
    {
        $this->tokenRepository->expects($this->once())
            ->method('save')
            ->with($mocks['passwordResetToken']);

        $this->uuidFactory->expects($this->once())
            ->method('create')
            ->willReturn($testData['eventId']);

        $this->eventFactory->expects($this->once())
            ->method('create')
            ->with($testData['userId'], (string) $testData['eventId'])
            ->willReturn($mocks['event']);

        $this->eventBus->expects($this->once())->method('publish')->with($mocks['event']);
    }
}
