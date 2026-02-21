<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\ConfirmTwoFactorCommand;
use App\User\Application\CommandHandler\ConfirmTwoFactorCommandHandler;
use App\User\Application\Service\RecoveryCodeGeneratorInterface;
use App\User\Application\Service\TwoFactorCodeVerifierInterface;
use App\User\Application\Service\TwoFactorEventPublisherInterface;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class ConfirmTwoFactorCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private AuthSessionRepositoryInterface&MockObject $authSessionRepository;
    private TwoFactorCodeVerifierInterface&MockObject $codeVerifier;
    private RecoveryCodeGeneratorInterface&MockObject $recoveryCodeGenerator;
    private TwoFactorEventPublisherInterface&MockObject $eventPublisher;
    private UserFactory $userFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(
            UserRepositoryInterface::class
        );
        $this->authSessionRepository = $this->createMock(
            AuthSessionRepositoryInterface::class
        );
        $this->codeVerifier = $this->createMock(
            TwoFactorCodeVerifierInterface::class
        );
        $this->recoveryCodeGenerator = $this->createMock(
            RecoveryCodeGeneratorInterface::class
        );
        $this->eventPublisher = $this->createMock(
            TwoFactorEventPublisherInterface::class
        );
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
        $expectedCodes = ['AB12-CD34', 'EF56-GH78', 'IJ90-KL12', 'MN34-OP56',
            'QR78-ST90', 'UV12-WX34', 'YZ56-AB78', 'CD90-EF12',
        ];

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($user->getEmail())
            ->willReturn($user);

        $this->codeVerifier
            ->expects($this->once())
            ->method('verifyTotpOrFail')
            ->with($user, $code);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (User $u): bool => $u->isTwoFactorEnabled()
            ));

        $this->recoveryCodeGenerator
            ->expects($this->once())
            ->method('generateAndStore')
            ->with($user)
            ->willReturn($expectedCodes);

        $this->authSessionRepository
            ->expects($this->once())
            ->method('findByUserId')
            ->with($user->getId())
            ->willReturn([]);

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
        $this->assertSame($expectedCodes, $codes);
    }

    public function testInvalidCodeThrowsUnauthorized(): void
    {
        $user = $this->createUserWithSecret();
        $sessionId = $this->faker->uuid();

        $this->userRepository
            ->method('findByEmail')
            ->willReturn($user);

        $this->codeVerifier
            ->expects($this->once())
            ->method('verifyTotpOrFail')
            ->with($user, '000000')
            ->willThrowException(
                new UnauthorizedHttpException('Bearer', 'Invalid two-factor code.')
            );

        $this->recoveryCodeGenerator
            ->expects($this->never())
            ->method('generateAndStore');

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

        $this->codeVerifier
            ->expects($this->never())
            ->method('verifyTotpOrFail');

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

        $this->recoveryCodeGenerator
            ->method('generateAndStore')
            ->willReturn([]);

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

        $this->recoveryCodeGenerator
            ->method('generateAndStore')
            ->willReturn([]);

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

        $this->recoveryCodeGenerator
            ->method('generateAndStore')
            ->willReturn([]);

        $this->authSessionRepository
            ->method('findByUserId')
            ->willReturn([]);

        $this->eventPublisher
            ->expects($this->once())
            ->method('publishEnabled')
            ->with($user->getId(), $user->getEmail());

        $handler = $this->createHandler();
        $command = new ConfirmTwoFactorCommand(
            $user->getEmail(),
            '123456',
            $sessionId
        );
        $handler->__invoke($command);
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

        $this->recoveryCodeGenerator
            ->method('generateAndStore')
            ->willReturn([]);

        $this->authSessionRepository
            ->method('findByUserId')
            ->willReturn([$otherSession]);

        $this->eventPublisher
            ->expects($this->once())
            ->method('publishAllSessionsRevoked')
            ->with($user->getId(), 'two_factor_enabled', 1);

        $handler = $this->createHandler();
        $command = new ConfirmTwoFactorCommand(
            $user->getEmail(),
            '123456',
            $currentSessionId
        );
        $handler->__invoke($command);
    }

    private function createHandler(): ConfirmTwoFactorCommandHandler
    {
        return new ConfirmTwoFactorCommandHandler(
            $this->userRepository,
            $this->authSessionRepository,
            $this->codeVerifier,
            $this->recoveryCodeGenerator,
            $this->eventPublisher,
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
