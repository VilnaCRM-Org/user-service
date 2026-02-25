<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\ConfirmTwoFactorCommand;
use App\User\Application\CommandHandler\ConfirmTwoFactorCommandHandler;
use App\User\Application\Factory\AuthTokenFactoryInterface;
use App\User\Domain\Contract\TOTPVerifierInterface;
use App\User\Domain\Contract\TwoFactorSecretEncryptorInterface;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Ulid;

final class ConfirmTwoFactorCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private AuthSessionRepositoryInterface&MockObject $authSessionRepository;
    private TOTPVerifierInterface&MockObject $totpVerifier;
    private TwoFactorSecretEncryptorInterface&MockObject $encryptor;
    private RecoveryCodeRepositoryInterface&MockObject $recoveryCodeRepository;
    private EventBusInterface&MockObject $eventBus;
    private AuthTokenFactoryInterface&MockObject $authTokenFactory;
    private UlidFactory&MockObject $ulidFactory;
    private UserFactory $userFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->authSessionRepository = $this->createMock(AuthSessionRepositoryInterface::class);
        $this->totpVerifier = $this->createMock(TOTPVerifierInterface::class);
        $this->encryptor = $this->createMock(TwoFactorSecretEncryptorInterface::class);
        $this->recoveryCodeRepository = $this->createMock(RecoveryCodeRepositoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->authTokenFactory = $this->createMock(AuthTokenFactoryInterface::class);
        $this->ulidFactory = $this->createMock(UlidFactory::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());

        $this->encryptor->method('decrypt')->willReturnArgument(0);
        $this->authTokenFactory->method('nextEventId')->willReturn('test-event-id');
        $this->ulidFactory->method('create')->willReturn(new Ulid());
    }

    public function testSuccessfulConfirmEnablesTwoFactor(): void
    {
        $user = $this->createUserWithSecret();
        $code = '123456';
        $sessionId = $this->faker->uuid();

        $this->configureUserLookup($user);

        $this->totpVerifier->expects($this->once())
            ->method('verify')
            ->with('encrypted-secret', $code)
            ->willReturn(true);

        $this->userRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (User $u): bool => $u->isTwoFactorEnabled()
            ));

        $this->recoveryCodeRepository->expects($this->exactly(RecoveryCode::COUNT))
            ->method('save')
            ->with($this->isInstanceOf(RecoveryCode::class));

        $this->configureSessionLookupOnce($user, []);
        $this->eventBus->expects($this->exactly(2))->method('publish');

        $command = $this->invokeHandler($user->getEmail(), $code, $sessionId);
        $codes = $command->getResponse()->getRecoveryCodes();
        $this->assertCount(RecoveryCode::COUNT, $codes);
    }

    public function testInvalidCodeThrowsUnauthorized(): void
    {
        $user = $this->createUserWithSecret();
        $this->userRepository->method('findByEmail')->willReturn($user);

        $this->totpVerifier->expects($this->once())
            ->method('verify')
            ->with('encrypted-secret', '000000')
            ->willReturn(false);

        $this->recoveryCodeRepository->expects($this->never())->method('save');
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
        $this->totpVerifier->expects($this->never())->method('verify');
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
        $this->totpVerifier->method('verify')->willReturn(true);
        $this->configureSessionLookupOnce($user, [$otherSession]);

        $this->authSessionRepository->expects($this->once())->method('save')
            ->with($this->callback(
                static fn (AuthSession $s): bool => $s->getId() === 'other-session-id'
                    && $s->isRevoked()
            ));

        $this->recoveryCodeRepository->method('save');
        $this->eventBus->method('publish');

        $this->invokeHandler($user->getEmail(), '123456', 'current-session-id');
    }

    public function testDoesNotRevokeCurrentSession(): void
    {
        $user = $this->createUserWithSecret();
        $currentSessionId = 'current-session-id';
        $currentSession = $this->createSession($currentSessionId, $user->getId());

        $this->configureUserLookupStub($user);
        $this->totpVerifier->method('verify')->willReturn(true);
        $this->authSessionRepository->method('findByUserId')->willReturn([$currentSession]);

        $this->authSessionRepository->expects($this->never())->method('save');
        $this->recoveryCodeRepository->method('save');
        $this->eventBus->method('publish');

        $this->invokeHandler($user->getEmail(), '123456', $currentSessionId);
        $this->assertFalse($currentSession->isRevoked());
    }

    public function testEmitsTwoFactorEnabledEvent(): void
    {
        $user = $this->createUserWithSecret();
        $this->configureUserLookupStub($user);
        $this->totpVerifier->method('verify')->willReturn(true);
        $this->configureRecoveryAndSessions();

        $publishedEvents = [];
        $this->eventBus->expects($this->exactly(2))
            ->method('publish')
            ->willReturnCallback(static function (\App\Shared\Domain\Bus\Event\DomainEvent $event) use (&$publishedEvents): void {
                $publishedEvents[] = $event;
            });

        $this->invokeHandler($user->getEmail(), '123456', $this->faker->uuid());

        $enabledEvent = null;
        foreach ($publishedEvents as $event) {
            if ($event instanceof \App\User\Domain\Event\TwoFactorEnabledEvent) {
                $enabledEvent = $event;
                break;
            }
        }

        $this->assertNotNull($enabledEvent);
        $this->assertSame($user->getId(), $enabledEvent->userId);
        $this->assertSame($user->getEmail(), $enabledEvent->email);
    }

    public function testEmitsAllSessionsRevokedEvent(): void
    {
        $user = $this->createUserWithSecret();
        $otherSession = $this->createSession('other-id', $user->getId());

        $this->configureUserLookupStub($user);
        $this->totpVerifier->method('verify')->willReturn(true);
        $this->authSessionRepository->method('findByUserId')->willReturn([$otherSession]);
        $this->authSessionRepository->method('save');
        $this->recoveryCodeRepository->method('save');

        $publishedEvents = [];
        $this->eventBus->expects($this->exactly(2))
            ->method('publish')
            ->willReturnCallback(static function (\App\Shared\Domain\Bus\Event\DomainEvent $event) use (&$publishedEvents): void {
                $publishedEvents[] = $event;
            });

        $this->invokeHandler($user->getEmail(), '123456', 'current-session-id');

        $revokedEvent = null;
        foreach ($publishedEvents as $event) {
            if ($event instanceof \App\User\Domain\Event\AllSessionsRevokedEvent) {
                $revokedEvent = $event;
                break;
            }
        }

        $this->assertNotNull($revokedEvent);
        $this->assertSame($user->getId(), $revokedEvent->userId);
        $this->assertSame('two_factor_enabled', $revokedEvent->reason);
        $this->assertSame(1, $revokedEvent->revokedCount);
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
        $this->recoveryCodeRepository->method('save');
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
            $this->totpVerifier,
            $this->encryptor,
            $this->recoveryCodeRepository,
            $this->eventBus,
            $this->authTokenFactory,
            $this->ulidFactory,
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
