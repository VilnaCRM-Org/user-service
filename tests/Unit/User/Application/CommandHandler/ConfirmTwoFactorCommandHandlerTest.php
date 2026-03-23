<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\ConfirmTwoFactorCommand;
use App\User\Application\CommandHandler\ConfirmTwoFactorCommandHandler;
use App\User\Application\Factory\RecoveryCodeBatchFactoryInterface;
use App\User\Application\Validator\TwoFactorCodeValidatorInterface;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Publisher\SessionPublisherInterface;
use App\User\Infrastructure\Publisher\TwoFactorPublisherInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Uid\Ulid;

final class ConfirmTwoFactorCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private AuthSessionRepositoryInterface&MockObject $authSessionRepository;
    private TwoFactorCodeValidatorInterface&MockObject $twoFactorCodeVerifier;
    private RecoveryCodeBatchFactoryInterface&MockObject $recoveryCodeBatchFactory;
    private TwoFactorPublisherInterface&MockObject $events;
    private SessionPublisherInterface&MockObject $sessionEvents;
    private UserFactory $userFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->authSessionRepository = $this->createMock(AuthSessionRepositoryInterface::class);
        $this->twoFactorCodeVerifier = $this->createMock(TwoFactorCodeValidatorInterface::class);
        $this->recoveryCodeBatchFactory = $this->createMock(
            RecoveryCodeBatchFactoryInterface::class
        );
        $this->events = $this->createMock(TwoFactorPublisherInterface::class);
        $this->sessionEvents = $this->createMock(SessionPublisherInterface::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());
    }

    public function testSuccessfulConfirmEnablesTwoFactor(): void
    {
        $user = $this->createUserWithSecret();
        $code = '123456';
        $sessionId = $this->faker->uuid();
        $expectedCodes = $this->createExpectedRecoveryCodes();

        $this->configureUserLookup($user);
        $this->expectTotpVerification($user, $code);
        $this->expectUserSavedWithTwoFactorEnabled();
        $this->expectGeneratedRecoveryCodes($user, $expectedCodes);
        $this->configureSessionLookupOnce($user, []);
        $this->expectSuccessEvents();

        $command = $this->invokeHandler($user->getEmail(), $code, $sessionId);
        $codes = $command->getResponse()->getRecoveryCodes();
        $this->assertCount(RecoveryCode::COUNT, $codes);
    }

    public function testInvalidCodeThrowsUnauthorized(): void
    {
        $user = $this->createUserWithSecret();
        $this->userRepository->method('findByEmail')->willReturn($user);

        $this->twoFactorCodeVerifier->expects($this->once())
            ->method('verifyAndConsumeOrFail')
            ->with($user, '000000')
            ->willThrowException(
                new UnauthorizedHttpException('Bearer', 'Invalid two-factor code.')
            );

        $this->recoveryCodeBatchFactory->expects($this->never())->method('create');
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');
        $this->createHandler()->__invoke(new ConfirmTwoFactorCommand(
            $user->getEmail(),
            '000000',
            $this->faker->uuid()
        ));
    }

    public function testUserWithoutSecretThrowsUnauthorized(): void
    {
        $user = $this->createUser($this->faker->email());
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->twoFactorCodeVerifier->expects($this->never())->method('verifyAndConsumeOrFail');
        $this->expectException(UnauthorizedHttpException::class);
        $this->createHandler()->__invoke(new ConfirmTwoFactorCommand(
            $user->getEmail(),
            '123456',
            $this->faker->uuid()
        ));
    }

    public function testUserNotFoundThrowsUnauthorized(): void
    {
        $this->userRepository
            ->method('findByEmail')
            ->willReturn(null);

        $this->expectException(UnauthorizedHttpException::class);

        $handler = $this->createHandler();
        $handler->__invoke(new ConfirmTwoFactorCommand(
            $this->faker->email(),
            '123456',
            $this->faker->uuid()
        ));
    }

    public function testRevokesOtherSessionsOnSuccess(): void
    {
        $user = $this->createUserWithSecret();
        $otherSession = $this->createSession('other-session-id', $user->getId());

        $this->configureUserLookupStub($user);
        $this->twoFactorCodeVerifier->method('verifyAndConsumeOrFail');
        $this->configureSessionLookupOnce($user, [$otherSession]);

        $this->authSessionRepository->expects($this->once())->method('save')
            ->with($this->callback(
                static fn (AuthSession $s): bool => $s->getId() === 'other-session-id'
                    && $s->isRevoked()
            ));

        $this->recoveryCodeBatchFactory->method('create')->willReturn([]);
        $this->events->method('publishEnabled');
        $this->sessionEvents->method('publishAllSessionsRevoked');

        $this->invokeHandler($user->getEmail(), '123456', 'current-session-id');
    }

    public function testDoesNotRevokeCurrentSession(): void
    {
        $user = $this->createUserWithSecret();
        $currentSessionId = 'current-session-id';
        $currentSession = $this->createSession($currentSessionId, $user->getId());

        $this->configureUserLookupStub($user);
        $this->twoFactorCodeVerifier->method('verifyAndConsumeOrFail');
        $this->authSessionRepository->method('findByUserId')->willReturn([$currentSession]);

        $this->authSessionRepository->expects($this->never())->method('save');
        $this->recoveryCodeBatchFactory->method('create')->willReturn([]);
        $this->events->method('publishEnabled');
        $this->sessionEvents->method('publishAllSessionsRevoked');

        $this->invokeHandler($user->getEmail(), '123456', $currentSessionId);
        $this->assertFalse($currentSession->isRevoked());
    }

    public function testEmitsTwoFactorEnabledEvent(): void
    {
        $user = $this->createUserWithSecret();
        $this->configureUserLookupStub($user);
        $this->twoFactorCodeVerifier->method('verifyAndConsumeOrFail');
        $this->configureRecoveryAndSessions();

        $this->events->expects($this->once())
            ->method('publishEnabled')
            ->with($user->getId(), $user->getEmail());

        $this->sessionEvents->method('publishAllSessionsRevoked');

        $this->invokeHandler($user->getEmail(), '123456', $this->faker->uuid());
    }

    public function testEmitsAllSessionsRevokedEvent(): void
    {
        $user = $this->createUserWithSecret();
        $otherSession = $this->createSession('other-id', $user->getId());

        $this->configureUserLookupStub($user);
        $this->twoFactorCodeVerifier->method('verifyAndConsumeOrFail');
        $this->authSessionRepository->method('findByUserId')->willReturn([$otherSession]);
        $this->authSessionRepository->method('save');
        $this->recoveryCodeBatchFactory->method('create')->willReturn([]);

        $this->events->method('publishEnabled');
        $this->sessionEvents->expects($this->once())
            ->method('publishAllSessionsRevoked')
            ->with($user->getId(), 'two_factor_enabled', 1);

        $this->invokeHandler($user->getEmail(), '123456', 'current-session-id');
    }

    private function configureUserLookup(User $user): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($user->getEmail())
            ->willReturn($user);
    }

    private function configureUserLookupStub(User $user): void
    {
        $this->userRepository->method('findByEmail')->willReturn($user);
    }

    private function expectTotpVerification(User $user, string $code): void
    {
        $this->twoFactorCodeVerifier->expects($this->once())
            ->method('verifyAndConsumeOrFail')
            ->with($user, $code);
    }

    private function expectUserSavedWithTwoFactorEnabled(): void
    {
        $this->userRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (User $user): bool => $user->isTwoFactorEnabled()));
    }

    /**
     * @param list<string> $expectedCodes
     */
    private function expectGeneratedRecoveryCodes(User $user, array $expectedCodes): void
    {
        $this->recoveryCodeBatchFactory->expects($this->once())
            ->method('create')
            ->with($user)
            ->willReturn($expectedCodes);
    }

    /**
     * @return list<string>
     */
    private function createExpectedRecoveryCodes(): array
    {
        return array_map(
            static fn (): string => (string) new Ulid(),
            array_fill(0, RecoveryCode::COUNT, null)
        );
    }

    private function expectSuccessEvents(): void
    {
        $this->events->expects($this->once())->method('publishEnabled');
        $this->sessionEvents->expects($this->once())->method('publishAllSessionsRevoked');
    }

    /**
     * @param array<int, AuthSession> $sessions
     */
    private function configureSessionLookupOnce(User $user, array $sessions): void
    {
        $this->authSessionRepository
            ->expects($this->once())
            ->method('findByUserId')
            ->with($user->getId())
            ->willReturn($sessions);
    }

    private function configureRecoveryAndSessions(): void
    {
        $this->recoveryCodeBatchFactory->method('create')->willReturn([]);
        $this->authSessionRepository->method('findByUserId')->willReturn([]);
    }

    private function invokeHandler(
        string $email,
        string $code,
        string $sessionId
    ): ConfirmTwoFactorCommand {
        $handler = $this->createHandler();
        $command = new ConfirmTwoFactorCommand($email, $code, $sessionId);
        $handler->__invoke($command);
        return $command;
    }

    private function createSession(string $sessionId, string $userId): AuthSession
    {
        return new AuthSession(
            $sessionId,
            $userId,
            '127.0.0.1',
            'Mozilla/5.0',
            new DateTimeImmutable(),
            new DateTimeImmutable('+1 hour'),
            false
        );
    }

    private function createHandler(): ConfirmTwoFactorCommandHandler
    {
        return new ConfirmTwoFactorCommandHandler(
            $this->userRepository,
            $this->authSessionRepository,
            $this->twoFactorCodeVerifier,
            $this->recoveryCodeBatchFactory,
            $this->events,
            $this->sessionEvents,
        );
    }

    private function createUserWithSecret(): User
    {
        $user = $this->createUser($this->faker->email());
        $user->setTwoFactorSecret('encrypted-secret');

        return $user;
    }

    private function createUser(string $email): User
    {
        return $this->userFactory->create(
            $email,
            $this->faker->firstName(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString(
                $this->faker->uuid()
            )
        );
    }
}
