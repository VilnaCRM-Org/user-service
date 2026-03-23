<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\Command\SignOutAllCommand;
use App\User\Application\CommandHandler\ConfirmPasswordResetCommandHandler;
use App\User\Application\Provider\AccountLockoutProviderInterface;
use App\User\Application\Validator\PasswordResetTokenValidatorInterface;
use App\User\Domain\Contract\PasswordHasherInterface;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\PasswordResetTokenAlreadyUsedException;
use App\User\Domain\Exception\PasswordResetTokenExpiredException;
use App\User\Domain\Exception\PasswordResetTokenNotFoundException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Publisher\PasswordResetConfirmationPublisherInterface;
use PHPUnit\Framework\MockObject\MockObject;

final class ConfirmPasswordResetCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private PasswordResetTokenRepositoryInterface&MockObject $tokenRepository;
    private PasswordHasherInterface&MockObject $passwordHasher;
    private PasswordResetTokenValidatorInterface&MockObject $tokenValidator;
    private AccountLockoutProviderInterface&MockObject $accountLockoutGuard;
    private CommandBusInterface&MockObject $commandBus;
    private PasswordResetConfirmationPublisherInterface&MockObject $publisher;
    private ConfirmPasswordResetCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->tokenRepository = $this->createMock(PasswordResetTokenRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $this->tokenValidator = $this->createMock(PasswordResetTokenValidatorInterface::class);
        $this->accountLockoutGuard = $this->createMock(AccountLockoutProviderInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->publisher = $this->createMock(PasswordResetConfirmationPublisherInterface::class);

        $this->handler = new ConfirmPasswordResetCommandHandler(
            $this->tokenRepository,
            $this->userRepository,
            $this->passwordHasher,
            $this->tokenValidator,
            $this->accountLockoutGuard,
            $this->commandBus,
            $this->publisher,
        );
    }

    public function testInvokeSuccessfully(): void
    {
        $testData = $this->createConfirmPasswordResetTestData();
        $command = new ConfirmPasswordResetCommand($testData['token'], $testData['newPassword']);
        $mocks = $this->createConfirmPasswordResetMocks($testData);

        $this->setupConfirmPasswordResetExpectations($testData, $mocks);

        $this->handler->__invoke($command);

        $this->assertInstanceOf(
            \App\User\Application\DTO\ConfirmPasswordResetCommandResponse::class,
            $command->getResponse()
        );
    }

    public function testInvokePublishesEventWithCorrectUserId(): void
    {
        $testData = $this->createConfirmPasswordResetTestData();
        $command = new ConfirmPasswordResetCommand($testData['token'], $testData['newPassword']);
        $mocks = $this->createConfirmPasswordResetMocks($testData);

        $this->setupConfirmPasswordResetExpectations($testData, $mocks);

        $this->handler->__invoke($command);
    }

    public function testInvokeClearsFailuresWithLowercasedEmail(): void
    {
        $testData = $this->createConfirmPasswordResetTestData();
        $testData['userEmail'] = '  Test.User@Example.COM  ';
        $command = new ConfirmPasswordResetCommand(
            $testData['token'],
            $testData['newPassword']
        );
        $mocks = $this->createConfirmPasswordResetMocks($testData);

        $this->setupTokenValidationExpectations(
            $testData['token'],
            $mocks['passwordResetToken']
        );
        $this->setupUserUpdateExpectations($testData, $mocks['user']);
        $this->expectLockoutClearAndRevoke(
            $testData,
            $mocks,
            'test.user@example.com'
        );

        $this->handler->__invoke($command);
    }

    public function testInvokeDispatchesSignOutAllCommandWithPasswordResetReason(): void
    {
        $testData = $this->createConfirmPasswordResetTestData();
        $command = new ConfirmPasswordResetCommand($testData['token'], $testData['newPassword']);
        $mocks = $this->createConfirmPasswordResetMocks($testData);

        $this->setupTokenValidationExpectations($testData['token'], $mocks['passwordResetToken']);
        $this->setupUserUpdateExpectations($testData, $mocks['user']);
        $this->expectLockoutClearAndRevoke(
            $testData,
            $mocks,
            strtolower(trim($testData['userEmail']))
        );

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
     * @return array<ConfirmPasswordResetCommand|string>
     *
     * @psalm-return array{token: string, newPassword: string, command: ConfirmPasswordResetCommand}
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
    ): MockObject&PasswordResetTokenInterface {
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
     * @return array<string>
     *
     * @psalm-return array{
     *     token: string,
     *     newPassword: string,
     *     userId: string,
     *     userEmail: string,
     *     hashedPassword: string
     * }
     */
    private function createConfirmPasswordResetTestData(): array
    {
        return [
            'token' => $this->faker->lexify('??????????'),
            'newPassword' => $this->faker->password(12),
            'userId' => $this->faker->uuid(),
            'userEmail' => '  ' . $this->faker->email() . '  ',
            'hashedPassword' => $this->faker->sha256(),
        ];
    }

    /**
     * @param array<string, string> $testData
     *
     * @return (MockObject&PasswordResetTokenInterface|MockObject&User)[]
     *
     * @psalm-return array{passwordResetToken: MockObject&PasswordResetTokenInterface, user: MockObject&User}
     */
    private function createConfirmPasswordResetMocks(array $testData): array
    {
        $passwordResetToken = $this->createMock(PasswordResetTokenInterface::class);
        $passwordResetToken->expects($this->once())->method('markAsUsed');
        $passwordResetToken->expects($this->once())
            ->method('getUserID')
            ->willReturn($testData['userId']);

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($testData['userId']);
        $user->method('getEmail')->willReturn($testData['userEmail']);

        return [
            'passwordResetToken' => $passwordResetToken,
            'user' => $user,
        ];
    }

    /**
     * @param array<string, string> $testData
     * @param array<string, PasswordResetTokenInterface|User> $mocks
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
     * @param array<string, string> $testData
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
     * @param array<string, string> $testData
     * @param array<string, PasswordResetTokenInterface|User> $mocks
     */
    private function expectLockoutClearAndRevoke(
        array $testData,
        array $mocks,
        string $expectedEmail
    ): void {
        $this->tokenRepository->expects($this->once())
            ->method('save')
            ->with($mocks['passwordResetToken']);
        $this->accountLockoutGuard->expects($this->once())
            ->method('clearFailures')
            ->with($expectedEmail);
        $this->expectSessionRevocation($testData['userId']);
        $this->publisher->expects($this->once())
            ->method('publish')
            ->with($mocks['user']);
    }

    /**
     * @param array<string, string> $testData
     * @param array<string, PasswordResetTokenInterface|User> $mocks
     */
    private function setupEventPublishingExpectations(array $testData, array $mocks): void
    {
        $this->tokenRepository->expects($this->once())
            ->method('save')
            ->with($mocks['passwordResetToken']);

        $this->accountLockoutGuard->expects($this->once())
            ->method('clearFailures')
            ->with(strtolower(trim($testData['userEmail'])));

        $this->expectSessionRevocation($testData['userId']);

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with($mocks['user']);
    }

    private function expectSessionRevocation(string $userId): void
    {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                static fn (SignOutAllCommand $cmd): bool => $cmd->userId === $userId
                    && $cmd->reason === 'password_reset'
            ));
    }
}
