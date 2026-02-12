<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\ConfirmTwoFactorCommand;
use App\User\Application\CommandHandler\ConfirmTwoFactorCommandHandler;
use App\User\Domain\Contract\TOTPVerifierInterface;
use App\User\Domain\Contract\TwoFactorSecretEncryptorInterface;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\AllSessionsRevokedEvent;
use App\User\Domain\Event\TwoFactorEnabledEvent;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Factory\UuidFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class ConfirmTwoFactorCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private RecoveryCodeRepositoryInterface&MockObject $recoveryCodeRepository;
    private AuthSessionRepositoryInterface&MockObject $authSessionRepository;
    private TwoFactorSecretEncryptorInterface&MockObject $encryptor;
    private TOTPVerifierInterface&MockObject $totpVerifier;
    private EventBusInterface&MockObject $eventBus;
    private UserFactory $userFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(
            UserRepositoryInterface::class
        );
        $this->recoveryCodeRepository = $this->createMock(
            RecoveryCodeRepositoryInterface::class
        );
        $this->authSessionRepository = $this->createMock(
            AuthSessionRepositoryInterface::class
        );
        $this->encryptor = $this->createMock(
            TwoFactorSecretEncryptorInterface::class
        );
        $this->totpVerifier = $this->createMock(
            TOTPVerifierInterface::class
        );
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(
            new SharedUuidFactory()
        );
    }

    public function testSuccessfulConfirmEnablesTwoFactor(): void
    {
        $user = $this->createUserWithSecret();
        $code = '123456';
        $sessionId = $this->faker->uuid();

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($user->getEmail())
            ->willReturn($user);

        $this->encryptor
            ->expects($this->once())
            ->method('decrypt')
            ->with('encrypted-secret')
            ->willReturn('JBSWY3DPEHPK3PXP');

        $this->totpVerifier
            ->expects($this->once())
            ->method('verify')
            ->with('JBSWY3DPEHPK3PXP', $code)
            ->willReturn(true);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (User $u): bool => $u->isTwoFactorEnabled()
            ));

        $this->recoveryCodeRepository
            ->expects($this->exactly(8))
            ->method('save')
            ->with($this->isInstanceOf(RecoveryCode::class));

        $this->authSessionRepository
            ->expects($this->once())
            ->method('findByUserId')
            ->with($user->getId())
            ->willReturn([]);

        $this->eventBus
            ->expects($this->exactly(2))
            ->method('publish');

        $handler = $this->createHandler();
        $command = new ConfirmTwoFactorCommand(
            $user->getEmail(),
            $code,
            $sessionId
        );
        $handler->__invoke($command);

        $response = $command->getResponse();
        $codes = $response->getRecoveryCodes();
        $this->assertCount(8, $codes);

        foreach ($codes as $recoveryCode) {
            $this->assertMatchesRegularExpression(
                '/^[A-Za-z0-9]{4}-[A-Za-z0-9]{4}$/',
                $recoveryCode
            );
            $this->assertSame(strtoupper($recoveryCode), $recoveryCode);
        }
    }

    public function testRecoveryCodesAreStoredAsHashes(): void
    {
        $user = $this->createUserWithSecret();
        $sessionId = $this->faker->uuid();

        $this->userRepository
            ->method('findByEmail')
            ->willReturn($user);

        $this->encryptor
            ->method('decrypt')
            ->willReturn('JBSWY3DPEHPK3PXP');

        $this->totpVerifier
            ->method('verify')
            ->willReturn(true);

        $savedCodes = [];
        $this->recoveryCodeRepository
            ->expects($this->exactly(8))
            ->method('save')
            ->willReturnCallback(
                static function (RecoveryCode $code) use (&$savedCodes): void {
                    $savedCodes[] = $code;
                }
            );

        $this->authSessionRepository
            ->method('findByUserId')
            ->willReturn([]);

        $handler = $this->createHandler();
        $command = new ConfirmTwoFactorCommand(
            $user->getEmail(),
            '123456',
            $sessionId
        );
        $handler->__invoke($command);

        $plaintextCodes = $command->getResponse()->getRecoveryCodes();
        $this->assertCount(8, $savedCodes);

        foreach ($savedCodes as $index => $savedCode) {
            $this->assertTrue(
                $savedCode->matchesCode($plaintextCodes[$index])
            );
            $this->assertNotSame(
                $plaintextCodes[$index],
                $savedCode->getCodeHash()
            );
        }
    }

    public function testInvalidCodeThrowsUnauthorized(): void
    {
        $user = $this->createUserWithSecret();
        $sessionId = $this->faker->uuid();

        $this->userRepository
            ->method('findByEmail')
            ->willReturn($user);

        $this->encryptor
            ->method('decrypt')
            ->willReturn('JBSWY3DPEHPK3PXP');

        $this->totpVerifier
            ->method('verify')
            ->willReturn(false);

        $this->recoveryCodeRepository
            ->expects($this->never())
            ->method('save');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');

        $handler = $this->createHandler();
        $handler->__invoke(new ConfirmTwoFactorCommand(
            $user->getEmail(),
            '000000',
            $sessionId
        ));
    }

    public function testUserWithoutSecretThrowsUnauthorized(): void
    {
        $user = $this->createUser($this->faker->email());
        $sessionId = $this->faker->uuid();

        $this->userRepository
            ->method('findByEmail')
            ->willReturn($user);

        $this->encryptor
            ->expects($this->never())
            ->method('decrypt');

        $this->expectException(UnauthorizedHttpException::class);

        $handler = $this->createHandler();
        $handler->__invoke(new ConfirmTwoFactorCommand(
            $user->getEmail(),
            '123456',
            $sessionId
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
        $currentSessionId = 'current-session-id';
        $otherSession = new AuthSession(
            'other-session-id',
            $user->getId(),
            '127.0.0.1',
            'Mozilla/5.0',
            new DateTimeImmutable(),
            new DateTimeImmutable('+1 hour'),
            false
        );

        $this->userRepository
            ->method('findByEmail')
            ->willReturn($user);

        $this->encryptor
            ->method('decrypt')
            ->willReturn('JBSWY3DPEHPK3PXP');

        $this->totpVerifier
            ->method('verify')
            ->willReturn(true);

        $this->authSessionRepository
            ->expects($this->once())
            ->method('findByUserId')
            ->with($user->getId())
            ->willReturn([$otherSession]);

        $this->authSessionRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (AuthSession $s): bool => $s->getId() === 'other-session-id'
                    && $s->isRevoked()
            ));

        $handler = $this->createHandler();
        $command = new ConfirmTwoFactorCommand(
            $user->getEmail(),
            '123456',
            $currentSessionId
        );
        $handler->__invoke($command);
    }

    public function testDoesNotRevokeCurrentSession(): void
    {
        $user = $this->createUserWithSecret();
        $currentSessionId = 'current-session-id';
        $currentSession = new AuthSession(
            $currentSessionId,
            $user->getId(),
            '127.0.0.1',
            'Mozilla/5.0',
            new DateTimeImmutable(),
            new DateTimeImmutable('+1 hour'),
            false
        );

        $this->userRepository
            ->method('findByEmail')
            ->willReturn($user);

        $this->encryptor
            ->method('decrypt')
            ->willReturn('JBSWY3DPEHPK3PXP');

        $this->totpVerifier
            ->method('verify')
            ->willReturn(true);

        $this->authSessionRepository
            ->method('findByUserId')
            ->willReturn([$currentSession]);

        $this->authSessionRepository
            ->expects($this->never())
            ->method('save');

        $handler = $this->createHandler();
        $command = new ConfirmTwoFactorCommand(
            $user->getEmail(),
            '123456',
            $currentSessionId
        );
        $handler->__invoke($command);

        $this->assertFalse($currentSession->isRevoked());
    }

    public function testEmitsTwoFactorEnabledEvent(): void
    {
        $user = $this->createUserWithSecret();
        $sessionId = $this->faker->uuid();

        $this->userRepository
            ->method('findByEmail')
            ->willReturn($user);

        $this->encryptor
            ->method('decrypt')
            ->willReturn('JBSWY3DPEHPK3PXP');

        $this->totpVerifier
            ->method('verify')
            ->willReturn(true);

        $this->authSessionRepository
            ->method('findByUserId')
            ->willReturn([]);

        $publishedEvents = [];
        $this->eventBus
            ->expects($this->exactly(2))
            ->method('publish')
            ->willReturnCallback(
                static function ($event) use (&$publishedEvents): void {
                    $publishedEvents[] = $event;
                }
            );

        $handler = $this->createHandler();
        $command = new ConfirmTwoFactorCommand(
            $user->getEmail(),
            '123456',
            $sessionId
        );
        $handler->__invoke($command);

        $this->assertInstanceOf(
            TwoFactorEnabledEvent::class,
            $publishedEvents[0]
        );
        $this->assertSame(
            $user->getId(),
            $publishedEvents[0]->userId
        );
        $this->assertSame(
            $user->getEmail(),
            $publishedEvents[0]->email
        );
    }

    public function testEmitsAllSessionsRevokedEvent(): void
    {
        $user = $this->createUserWithSecret();
        $currentSessionId = 'current-session-id';
        $otherSession = new AuthSession(
            'other-id',
            $user->getId(),
            '127.0.0.1',
            'Mozilla/5.0',
            new DateTimeImmutable(),
            new DateTimeImmutable('+1 hour'),
            false
        );

        $this->userRepository
            ->method('findByEmail')
            ->willReturn($user);

        $this->encryptor
            ->method('decrypt')
            ->willReturn('JBSWY3DPEHPK3PXP');

        $this->totpVerifier
            ->method('verify')
            ->willReturn(true);

        $this->authSessionRepository
            ->method('findByUserId')
            ->willReturn([$otherSession]);

        $publishedEvents = [];
        $this->eventBus
            ->method('publish')
            ->willReturnCallback(
                static function ($event) use (&$publishedEvents): void {
                    $publishedEvents[] = $event;
                }
            );

        $handler = $this->createHandler();
        $command = new ConfirmTwoFactorCommand(
            $user->getEmail(),
            '123456',
            $currentSessionId
        );
        $handler->__invoke($command);

        $this->assertInstanceOf(
            AllSessionsRevokedEvent::class,
            $publishedEvents[1]
        );
        $this->assertSame(
            $user->getId(),
            $publishedEvents[1]->userId
        );
        $this->assertSame(
            'two_factor_enabled',
            $publishedEvents[1]->reason
        );
        $this->assertSame(1, $publishedEvents[1]->revokedCount);
    }

    private function createHandler(): ConfirmTwoFactorCommandHandler
    {
        return new ConfirmTwoFactorCommandHandler(
            $this->userRepository,
            $this->recoveryCodeRepository,
            $this->authSessionRepository,
            $this->encryptor,
            $this->totpVerifier,
            $this->eventBus,
            new UuidFactory(),
            new UlidFactory(),
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
