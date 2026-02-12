<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\CompleteTwoFactorCommand;
use App\User\Application\CommandHandler\CompleteTwoFactorCommandHandler;
use App\User\Domain\Contract\AccessTokenGeneratorInterface;
use App\User\Domain\Contract\TOTPVerifierInterface;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\RecoveryCodeUsedEvent;
use App\User\Domain\Event\TwoFactorCompletedEvent;
use App\User\Domain\Event\TwoFactorFailedEvent;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

final class CompleteTwoFactorCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private PendingTwoFactorRepositoryInterface&MockObject $pendingTwoFactorRepository;
    private RecoveryCodeRepositoryInterface&MockObject $recoveryCodeRepository;
    private AuthSessionRepositoryInterface&MockObject $authSessionRepository;
    private AuthRefreshTokenRepositoryInterface&MockObject $authRefreshTokenRepository;
    private TOTPVerifierInterface&MockObject $totpVerifier;
    private AccessTokenGeneratorInterface&MockObject $accessTokenGenerator;
    private EventBusInterface&MockObject $eventBus;
    private UuidFactory&MockObject $uuidFactory;
    private UlidFactory&MockObject $ulidFactory;
    private UserFactory $userFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->pendingTwoFactorRepository = $this->createMock(PendingTwoFactorRepositoryInterface::class);
        $this->recoveryCodeRepository = $this->createMock(RecoveryCodeRepositoryInterface::class);
        $this->authSessionRepository = $this->createMock(AuthSessionRepositoryInterface::class);
        $this->authRefreshTokenRepository = $this->createMock(AuthRefreshTokenRepositoryInterface::class);
        $this->totpVerifier = $this->createMock(TOTPVerifierInterface::class);
        $this->accessTokenGenerator = $this->createMock(AccessTokenGeneratorInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->uuidFactory = $this->createMock(UuidFactory::class);
        $this->ulidFactory = $this->createMock(UlidFactory::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());
    }

    /** @SuppressWarnings(PHPMD.CyclomaticComplexity) */
    public function testInvokeCompletesTwoFactorAndIssuesTokens(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pendingSession = $this->createPendingSession($user->getId(), '+5 minutes');
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->with($pendingSession->getId())
            ->willReturn($pendingSession);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($user->getId())
            ->willReturn($user);

        $this->totpVerifier
            ->expects($this->once())
            ->method('verify')
            ->with('JBSWY3DPEHPK3PXP', '123456')
            ->willReturn(true);

        $this->recoveryCodeRepository
            ->expects($this->never())
            ->method('findByUserId');

        $sessionId = Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FC0');
        $refreshTokenId = Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FC1');

        $this->ulidFactory
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($sessionId, $refreshTokenId);

        $jti = Uuid::fromString('e2c4b1bb-8f59-4f95-b16d-4d90945141ad');
        $eventId = Uuid::fromString('ee625573-fd9a-4f86-b98c-4f21bec8f204');

        $this->uuidFactory
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($jti, $eventId);

        $this->accessTokenGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($this->callback(
                static fn (array $payload): bool => isset($payload['sub'], $payload['sid'], $payload['jti'], $payload['roles'])
                    && $payload['sub'] === $user->getId()
                    && $payload['sid'] === (string) $sessionId
                    && $payload['jti'] === (string) $jti
                    && is_int($payload['exp'] ?? null)
                    && is_int($payload['iat'] ?? null)
                    && ($payload['exp'] - $payload['iat']) === 900
                    && $payload['roles'] === ['ROLE_USER']
            ))
            ->willReturn('issued-access-token');

        $this->authSessionRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (AuthSession $session): bool => $session->getId() === (string) $sessionId
                    && $session->getUserId() === $user->getId()
                    && $session->getIpAddress() === $ipAddress
                    && $session->getUserAgent() === $userAgent
                    && $session->isRememberMe() === false
            ));

        $this->authRefreshTokenRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (AuthRefreshToken $token): bool => $token->getId() === (string) $refreshTokenId
                    && $token->getSessionId() === (string) $sessionId
            ));

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('delete')
            ->with($pendingSession);

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(TwoFactorCompletedEvent::class));

        $handler = $this->createHandler();
        $command = new CompleteTwoFactorCommand(
            $pendingSession->getId(),
            '123456',
            $ipAddress,
            $userAgent
        );

        $handler->__invoke($command);

        $this->assertSame('issued-access-token', $command->getResponse()->getAccessToken());
        $this->assertNotEmpty($command->getResponse()->getRefreshToken());
        $this->assertNotSame(
            $command->getResponse()->getAccessToken(),
            $command->getResponse()->getRefreshToken()
        );
        $this->assertOpaqueTokenFormat($command->getResponse()->getRefreshToken());
    }

    public function testInvokeCompletesTwoFactorWithRecoveryCodeAndIssuesTokens(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pendingSession = $this->createPendingSession($user->getId(), '+5 minutes');
        $recoveryCode = new RecoveryCode(
            (string) new Ulid(),
            $user->getId(),
            'AB12-CD34'
        );
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->with($pendingSession->getId())
            ->willReturn($pendingSession);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($user->getId())
            ->willReturn($user);

        $this->totpVerifier
            ->expects($this->never())
            ->method('verify');

        $this->recoveryCodeRepository
            ->expects($this->exactly(2))
            ->method('findByUserId')
            ->with($user->getId())
            ->willReturn([$recoveryCode]);

        $this->recoveryCodeRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (RecoveryCode $code): bool => $code->getId() === $recoveryCode->getId()
                    && $code->isUsed()
            ));

        $sessionId = Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FC2');
        $refreshTokenId = Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FC3');

        $this->ulidFactory
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($sessionId, $refreshTokenId);

        $jti = Uuid::fromString('4a8b7db3-79f2-4cf1-9de1-d7a9dcc0f901');
        $recoveryEventId = Uuid::fromString('e7e7e7e7-1111-4444-8888-aaaaaaaaaaaa');
        $completedEventId = Uuid::fromString('34aa3f1f-2f53-4a09-8f8d-54bca0fd3c43');

        $this->uuidFactory
            ->expects($this->exactly(3))
            ->method('create')
            ->willReturnOnConsecutiveCalls($jti, $recoveryEventId, $completedEventId);

        $this->accessTokenGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($this->callback(
                static fn (array $payload): bool => isset($payload['sub'], $payload['sid'], $payload['jti'], $payload['roles'])
                    && $payload['sub'] === $user->getId()
                    && $payload['sid'] === (string) $sessionId
                    && $payload['jti'] === (string) $jti
                    && is_int($payload['exp'] ?? null)
                    && is_int($payload['iat'] ?? null)
                    && ($payload['exp'] - $payload['iat']) === 900
                    && $payload['roles'] === ['ROLE_USER']
            ))
            ->willReturn('issued-access-token');

        $this->authSessionRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(AuthSession::class));

        $this->authRefreshTokenRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(AuthRefreshToken::class));

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('delete')
            ->with($pendingSession);

        $publishedEvents = [];
        $this->eventBus
            ->expects($this->exactly(2))
            ->method('publish')
            ->willReturnCallback(static function ($event) use (&$publishedEvents): void {
                $publishedEvents[] = $event;
            });

        $handler = $this->createHandler();
        $command = new CompleteTwoFactorCommand(
            $pendingSession->getId(),
            'AB12-CD34',
            $ipAddress,
            $userAgent
        );

        $handler->__invoke($command);

        $this->assertSame('issued-access-token', $command->getResponse()->getAccessToken());
        $this->assertNotEmpty($command->getResponse()->getRefreshToken());
        $this->assertNotSame(
            $command->getResponse()->getAccessToken(),
            $command->getResponse()->getRefreshToken()
        );
        $this->assertOpaqueTokenFormat($command->getResponse()->getRefreshToken());
        $this->assertSame(0, $command->getResponse()->getRecoveryCodesRemaining());
        $this->assertSame(
            'All recovery codes have been used. Regenerate immediately.',
            $command->getResponse()->getWarningMessage()
        );

        $this->assertInstanceOf(RecoveryCodeUsedEvent::class, $publishedEvents[0]);
        $this->assertSame(0, $publishedEvents[0]->remainingCount);
        $this->assertInstanceOf(TwoFactorCompletedEvent::class, $publishedEvents[1]);
        $this->assertSame('recovery_code', $publishedEvents[1]->method);
    }

    public function testInvokeThrowsUnauthorizedWhenPendingSessionIsMissing(): void
    {
        $pendingSessionId = 'missing-session';

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->with($pendingSessionId)
            ->willReturn(null);

        $this->userRepository
            ->expects($this->never())
            ->method('findById');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired two-factor session.');

        $handler = $this->createHandler();
        $handler->__invoke(
            new CompleteTwoFactorCommand(
                $pendingSessionId,
                '123456',
                $this->faker->ipv4(),
                $this->faker->userAgent()
            )
        );
    }

    public function testInvokeThrowsUnauthorizedWhenPendingSessionIsExpired(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $expiredPendingSession = $this->createPendingSession($user->getId(), '-1 second');

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->with($expiredPendingSession->getId())
            ->willReturn($expiredPendingSession);

        $this->userRepository
            ->expects($this->never())
            ->method('findById');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired two-factor session.');

        $handler = $this->createHandler();
        $handler->__invoke(
            new CompleteTwoFactorCommand(
                $expiredPendingSession->getId(),
                '123456',
                $this->faker->ipv4(),
                $this->faker->userAgent()
            )
        );
    }

    public function testInvokeThrowsUnauthorizedWhenUserDoesNotRequireTwoFactor(): void
    {
        $user = $this->userFactory->create(
            $this->faker->email(),
            $this->faker->firstName(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
        $pendingSession = $this->createPendingSession($user->getId(), '+5 minutes');

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->with($pendingSession->getId())
            ->willReturn($pendingSession);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($user->getId())
            ->willReturn($user);

        $this->totpVerifier
            ->expects($this->never())
            ->method('verify');

        $this->eventBus
            ->expects($this->never())
            ->method('publish');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired two-factor session.');

        $handler = $this->createHandler();
        $handler->__invoke(
            new CompleteTwoFactorCommand(
                $pendingSession->getId(),
                '123456',
                $this->faker->ipv4(),
                $this->faker->userAgent()
            )
        );
    }

    public function testInvokeThrowsUnauthorizedWhenTotpSecretIsMissing(): void
    {
        $user = $this->createTwoFactorEnabledUserWithoutSecret();
        $pendingSession = $this->createPendingSession($user->getId(), '+5 minutes');

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->with($pendingSession->getId())
            ->willReturn($pendingSession);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($user->getId())
            ->willReturn($user);

        $this->totpVerifier
            ->expects($this->never())
            ->method('verify');

        $this->recoveryCodeRepository
            ->expects($this->never())
            ->method('findByUserId');

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(TwoFactorFailedEvent::class));

        $this->pendingTwoFactorRepository
            ->expects($this->never())
            ->method('delete');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');

        $handler = $this->createHandler();
        $handler->__invoke(
            new CompleteTwoFactorCommand(
                $pendingSession->getId(),
                '123456',
                $this->faker->ipv4(),
                $this->faker->userAgent()
            )
        );
    }

    public function testInvokeThrowsUnauthorizedWhenCodeFormatIsInvalid(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pendingSession = $this->createPendingSession($user->getId(), '+5 minutes');

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->with($pendingSession->getId())
            ->willReturn($pendingSession);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($user->getId())
            ->willReturn($user);

        $this->totpVerifier
            ->expects($this->never())
            ->method('verify');

        $this->recoveryCodeRepository
            ->expects($this->never())
            ->method('findByUserId');

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(TwoFactorFailedEvent::class));

        $this->pendingTwoFactorRepository
            ->expects($this->never())
            ->method('delete');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');

        $handler = $this->createHandler();
        $handler->__invoke(
            new CompleteTwoFactorCommand(
                $pendingSession->getId(),
                'abc-123',
                $this->faker->ipv4(),
                $this->faker->userAgent()
            )
        );
    }

    public function testInvokeRejectsTotpCodeWithLeadingCharacter(): void
    {
        $this->assertInvalidTwoFactorCodeRejected('A123456');
    }

    public function testInvokeRejectsTotpCodeWithTrailingCharacter(): void
    {
        $this->assertInvalidTwoFactorCodeRejected('1234567');
    }

    public function testInvokeRejectsRecoveryCodeWithLeadingCharacter(): void
    {
        $this->assertInvalidTwoFactorCodeRejected('XXAB1-CD23');
    }

    public function testInvokeRejectsRecoveryCodeWithTrailingCharacter(): void
    {
        $this->assertInvalidTwoFactorCodeRejected('AB1C-D234X');
    }

    public function testInvokeThrowsUnauthorizedWhenRecoveryCodeVerificationFails(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pendingSession = $this->createPendingSession($user->getId(), '+5 minutes');
        $usedRecoveryCode = new RecoveryCode(
            (string) new Ulid(),
            $user->getId(),
            'AA11-BB22'
        );
        $usedRecoveryCode->markAsUsed();

        $anotherRecoveryCode = new RecoveryCode(
            (string) new Ulid(),
            $user->getId(),
            'CC33-DD44'
        );

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->with($pendingSession->getId())
            ->willReturn($pendingSession);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($user->getId())
            ->willReturn($user);

        $this->totpVerifier
            ->expects($this->never())
            ->method('verify');

        $this->recoveryCodeRepository
            ->expects($this->once())
            ->method('findByUserId')
            ->with($user->getId())
            ->willReturn([$usedRecoveryCode, $anotherRecoveryCode]);

        $this->recoveryCodeRepository
            ->expects($this->never())
            ->method('save');

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(TwoFactorFailedEvent::class));

        $this->pendingTwoFactorRepository
            ->expects($this->never())
            ->method('delete');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');

        $handler = $this->createHandler();
        $handler->__invoke(
            new CompleteTwoFactorCommand(
                $pendingSession->getId(),
                'EF55-GH66',
                $this->faker->ipv4(),
                $this->faker->userAgent()
            )
        );
    }

    public function testInvokeUsesLaterUnusedRecoveryCodeWhenEarlierCodeIsUsed(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pendingSession = $this->createPendingSession($user->getId(), '+5 minutes');

        $usedRecoveryCode = new RecoveryCode(
            (string) new Ulid(),
            $user->getId(),
            'AA11-BB22'
        );
        $usedRecoveryCode->markAsUsed();

        $matchingRecoveryCode = new RecoveryCode(
            (string) new Ulid(),
            $user->getId(),
            'CC33-DD44'
        );

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->with($pendingSession->getId())
            ->willReturn($pendingSession);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($user->getId())
            ->willReturn($user);

        $this->recoveryCodeRepository
            ->expects($this->exactly(2))
            ->method('findByUserId')
            ->with($user->getId())
            ->willReturn([$usedRecoveryCode, $matchingRecoveryCode]);

        $this->recoveryCodeRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (RecoveryCode $code): bool => $code->getId() === $matchingRecoveryCode->getId()
                    && $code->isUsed()
            ));

        $this->setupTokenGeneration();

        $this->eventBus
            ->expects($this->exactly(2))
            ->method('publish');

        $handler = $this->createHandler();
        $command = new CompleteTwoFactorCommand(
            $pendingSession->getId(),
            'CC33-DD44',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );

        $handler->__invoke($command);

        $this->assertSame('test-access-token', $command->getResponse()->getAccessToken());
    }

    public function testInvokeThrowsUnauthorizedWhenTotpVerificationFails(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pendingSession = $this->createPendingSession($user->getId(), '+5 minutes');

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->with($pendingSession->getId())
            ->willReturn($pendingSession);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($user->getId())
            ->willReturn($user);

        $this->totpVerifier
            ->expects($this->once())
            ->method('verify')
            ->with('JBSWY3DPEHPK3PXP', '123456')
            ->willReturn(false);

        $this->recoveryCodeRepository
            ->expects($this->never())
            ->method('findByUserId');

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(TwoFactorFailedEvent::class));

        $this->pendingTwoFactorRepository
            ->expects($this->never())
            ->method('delete');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');

        $handler = $this->createHandler();
        $handler->__invoke(
            new CompleteTwoFactorCommand(
                $pendingSession->getId(),
                '123456',
                $this->faker->ipv4(),
                $this->faker->userAgent()
            )
        );
    }

    public function testRecoveryCodeSignInIncludesWarningWhenFewCodesRemain(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pendingSession = $this->createPendingSession($user->getId(), '+5 minutes');
        $matchingCode = new RecoveryCode(
            (string) new Ulid(),
            $user->getId(),
            'AB12-CD34'
        );
        $otherCode1 = new RecoveryCode(
            (string) new Ulid(),
            $user->getId(),
            'EF56-GH78'
        );
        $otherCode2 = new RecoveryCode(
            (string) new Ulid(),
            $user->getId(),
            'IJ90-KL12'
        );

        $allCodes = [$matchingCode, $otherCode1, $otherCode2];

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($pendingSession);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $this->recoveryCodeRepository
            ->expects($this->exactly(2))
            ->method('findByUserId')
            ->willReturn($allCodes);

        $this->recoveryCodeRepository
            ->expects($this->once())
            ->method('save');

        $this->setupTokenGeneration();

        $publishedEvents = [];
        $this->eventBus
            ->expects($this->exactly(2))
            ->method('publish')
            ->willReturnCallback(static function ($event) use (&$publishedEvents): void {
                $publishedEvents[] = $event;
            });

        $handler = $this->createHandler();
        $command = new CompleteTwoFactorCommand(
            $pendingSession->getId(),
            'AB12-CD34',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );

        $handler->__invoke($command);

        $this->assertSame(2, $command->getResponse()->getRecoveryCodesRemaining());
        $this->assertStringContainsString(
            '2',
            (string) $command->getResponse()->getWarningMessage()
        );
        $this->assertInstanceOf(RecoveryCodeUsedEvent::class, $publishedEvents[0]);
        $this->assertSame(2, $publishedEvents[0]->remainingCount);
    }

    public function testRecoveryCodeSignInWithoutWarningWhenManyCodesRemain(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pendingSession = $this->createPendingSession($user->getId(), '+5 minutes');
        $matchingCode = new RecoveryCode(
            (string) new Ulid(),
            $user->getId(),
            'AB12-CD34'
        );

        $otherCodes = [];
        for ($i = 0; $i < 5; ++$i) {
            $otherCodes[] = new RecoveryCode(
                (string) new Ulid(),
                $user->getId(),
                sprintf('XX%02d-YY%02d', $i, $i)
            );
        }

        $allCodes = array_merge([$matchingCode], $otherCodes);

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($pendingSession);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $this->recoveryCodeRepository
            ->expects($this->exactly(2))
            ->method('findByUserId')
            ->willReturn($allCodes);

        $this->recoveryCodeRepository
            ->expects($this->once())
            ->method('save');

        $this->setupTokenGeneration();

        $publishedEvents = [];
        $this->eventBus
            ->expects($this->exactly(2))
            ->method('publish')
            ->willReturnCallback(static function ($event) use (&$publishedEvents): void {
                $publishedEvents[] = $event;
            });

        $handler = $this->createHandler();
        $command = new CompleteTwoFactorCommand(
            $pendingSession->getId(),
            'AB12-CD34',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );

        $handler->__invoke($command);

        $this->assertNull($command->getResponse()->getRecoveryCodesRemaining());
        $this->assertNull($command->getResponse()->getWarningMessage());
        $this->assertInstanceOf(RecoveryCodeUsedEvent::class, $publishedEvents[0]);
        $this->assertSame(5, $publishedEvents[0]->remainingCount);
    }

    public function testTotpSignInDoesNotIncludeRecoveryCodeInfo(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pendingSession = $this->createPendingSession($user->getId(), '+5 minutes');

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($pendingSession);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $this->totpVerifier
            ->expects($this->once())
            ->method('verify')
            ->willReturn(true);

        $this->recoveryCodeRepository
            ->expects($this->never())
            ->method('findByUserId');

        $this->setupTokenGeneration();

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(TwoFactorCompletedEvent::class));

        $handler = $this->createHandler();
        $command = new CompleteTwoFactorCommand(
            $pendingSession->getId(),
            '123456',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );

        $handler->__invoke($command);

        $this->assertNull($command->getResponse()->getRecoveryCodesRemaining());
        $this->assertNull($command->getResponse()->getWarningMessage());
    }

    private function setupTokenGeneration(): void
    {
        $this->ulidFactory
            ->method('create')
            ->willReturnCallback(static fn () => new Ulid());

        $this->uuidFactory
            ->method('create')
            ->willReturnCallback(static fn () => Uuid::v4());

        $this->accessTokenGenerator
            ->method('generate')
            ->willReturn('test-access-token');

        $this->authSessionRepository
            ->method('save');

        $this->authRefreshTokenRepository
            ->method('save');

        $this->pendingTwoFactorRepository
            ->method('delete');
    }

    private function assertInvalidTwoFactorCodeRejected(string $twoFactorCode): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pendingSession = $this->createPendingSession($user->getId(), '+5 minutes');

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->with($pendingSession->getId())
            ->willReturn($pendingSession);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($user->getId())
            ->willReturn($user);

        $this->totpVerifier
            ->expects($this->never())
            ->method('verify');

        $this->recoveryCodeRepository
            ->expects($this->never())
            ->method('findByUserId');

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(TwoFactorFailedEvent::class));

        $this->pendingTwoFactorRepository
            ->expects($this->never())
            ->method('delete');

        $handler = $this->createHandler();

        try {
            $handler->__invoke(
                new CompleteTwoFactorCommand(
                    $pendingSession->getId(),
                    $twoFactorCode,
                    $this->faker->ipv4(),
                    $this->faker->userAgent()
                )
            );
            $this->fail('Expected UnauthorizedHttpException to be thrown.');
        } catch (UnauthorizedHttpException $exception) {
            $this->assertSame('Invalid two-factor code.', $exception->getMessage());
        }
    }

    private function assertOpaqueTokenFormat(string $token): void
    {
        $this->assertSame(43, strlen($token));
        $this->assertStringNotContainsString('=', $token);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9\-_]+$/', $token);
    }

    private function createHandler(): CompleteTwoFactorCommandHandler
    {
        return new CompleteTwoFactorCommandHandler(
            $this->userRepository,
            $this->pendingTwoFactorRepository,
            $this->recoveryCodeRepository,
            $this->authSessionRepository,
            $this->authRefreshTokenRepository,
            $this->totpVerifier,
            $this->accessTokenGenerator,
            $this->eventBus,
            $this->uuidFactory,
            $this->ulidFactory,
        );
    }

    private function createTwoFactorEnabledUser(): User
    {
        $user = $this->userFactory->create(
            $this->faker->email(),
            $this->faker->firstName(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );

        $user->setTwoFactorEnabled(true);
        $user->setTwoFactorSecret('JBSWY3DPEHPK3PXP');

        return $user;
    }

    private function createTwoFactorEnabledUserWithoutSecret(): User
    {
        $user = $this->userFactory->create(
            $this->faker->email(),
            $this->faker->firstName(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );

        $user->setTwoFactorEnabled(true);
        $user->setTwoFactorSecret(null);

        return $user;
    }

    private function createPendingSession(
        string $userId,
        string $expiresAtModifier
    ): PendingTwoFactor {
        $createdAt = new DateTimeImmutable('now');

        return new PendingTwoFactor(
            (string) new Ulid(),
            $userId,
            $createdAt,
            $createdAt->modify($expiresAtModifier)
        );
    }
}
