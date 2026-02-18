<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\CompleteTwoFactorCommand;
use App\User\Application\CommandHandler\CompleteTwoFactorCommandHandler;
use App\User\Application\Factory\AuthTokenFactoryInterface;
use App\User\Domain\Contract\AccessTokenGeneratorInterface;
use App\User\Domain\Contract\TOTPVerifierInterface;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\RecoveryCodeUsedEvent;
use App\User\Domain\Event\TwoFactorCompletedEvent;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Ulid;

final class CompleteTwoFactorCommandHandlerSuccessPathTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private PendingTwoFactorRepositoryInterface&MockObject $pendingTwoFactorRepository;
    private RecoveryCodeRepositoryInterface&MockObject $recoveryCodeRepository;
    private AuthSessionRepositoryInterface&MockObject $authSessionRepository;
    private AuthRefreshTokenRepositoryInterface&MockObject $authRefreshTokenRepository;
    private TOTPVerifierInterface&MockObject $totpVerifier;
    private AccessTokenGeneratorInterface&MockObject $accessTokenGenerator;
    private AuthTokenFactoryInterface&MockObject $authTokenFactory;
    private EventBusInterface&MockObject $eventBus;
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
        $this->authTokenFactory = $this->createMock(AuthTokenFactoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
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

        $this->ulidFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($sessionId);

        $opaqueToken = str_repeat('a', 43);
        $refreshTokenEntity = new AuthRefreshToken(
            '01ARZ3NDEKTSV4RRFFQ69G5FC1',
            (string) $sessionId,
            $opaqueToken,
            (new DateTimeImmutable())->modify('+1 month')
        );

        $testJwtPayload = [
            'sub' => $user->getId(),
            'iss' => 'vilnacrm-user-service',
            'aud' => 'vilnacrm-api',
            'exp' => time() + 900,
            'iat' => time(),
            'nbf' => time(),
            'jti' => 'e2c4b1bb-8f59-4f95-b16d-4d90945141ad',
            'sid' => (string) $sessionId,
            'roles' => ['ROLE_USER'],
        ];

        $this->authTokenFactory
            ->expects($this->once())
            ->method('generateOpaqueToken')
            ->willReturn($opaqueToken);

        $this->authTokenFactory
            ->expects($this->once())
            ->method('createRefreshToken')
            ->with((string) $sessionId, $opaqueToken, $this->isInstanceOf(DateTimeImmutable::class))
            ->willReturn($refreshTokenEntity);

        $this->authTokenFactory
            ->expects($this->once())
            ->method('buildJwtPayload')
            ->with($user, (string) $sessionId, $this->isInstanceOf(DateTimeImmutable::class))
            ->willReturn($testJwtPayload);

        $this->authTokenFactory
            ->expects($this->once())
            ->method('nextEventId')
            ->willReturn('ee625573-fd9a-4f86-b98c-4f21bec8f204');

        $this->accessTokenGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($this->callback(
                static fn (array $payload): bool => isset($payload['sub'], $payload['sid'], $payload['jti'], $payload['roles'])
                    && $payload['sub'] === $user->getId()
                    && $payload['sid'] === (string) $sessionId
                    && $payload['jti'] === 'e2c4b1bb-8f59-4f95-b16d-4d90945141ad'
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
            ->with($refreshTokenEntity);

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
        $this->assertSame($opaqueToken, $command->getResponse()->getRefreshToken());
        $this->assertNotSame(
            $command->getResponse()->getAccessToken(),
            $command->getResponse()->getRefreshToken()
        );
        $this->assertFalse($command->getResponse()->isRememberMe());
    }

    public function testInvokeCompletesTwoFactorWithRememberMeFromPendingSession(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pendingSession = $this->createPendingSessionWithRememberMe($user->getId(), '+5 minutes', true);

        $this->pendingTwoFactorRepository->method('findById')->willReturn($pendingSession);
        $this->userRepository->method('findById')->willReturn($user);
        $this->totpVerifier->method('verify')->willReturn(true);
        $this->recoveryCodeRepository->expects($this->never())->method('findByUserId');

        $sessionId = Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FC8');
        $this->ulidFactory->method('create')->willReturn($sessionId);

        $this->setupAuthTokenFactory($sessionId);

        $this->authSessionRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (AuthSession $session): bool => $session->isRememberMe() === true
                    && ($session->getExpiresAt()->getTimestamp() - $session->getCreatedAt()->getTimestamp()) === 2592000
            ));

        $this->authRefreshTokenRepository->method('save');
        $this->pendingTwoFactorRepository->method('delete');
        $this->eventBus->method('publish');

        $handler = $this->createHandler();
        $command = new CompleteTwoFactorCommand(
            $pendingSession->getId(),
            '123456',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );

        $handler->__invoke($command);

        $this->assertTrue($command->getResponse()->isRememberMe());
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
        $this->ulidFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($sessionId);

        $this->setupAuthTokenFactory($sessionId, 2);

        $this->accessTokenGenerator
            ->expects($this->once())
            ->method('generate')
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

        $sessionId = new Ulid();
        $this->ulidFactory->method('create')->willReturn($sessionId);
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

        $sessionId = new Ulid();
        $this->ulidFactory->method('create')->willReturn($sessionId);
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

        $sessionId = new Ulid();
        $this->ulidFactory->method('create')->willReturn($sessionId);
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

        $sessionId = new Ulid();
        $this->ulidFactory->method('create')->willReturn($sessionId);
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

    /**
     * @param int<1, max> $nextEventIdCallCount
     */
    private function setupAuthTokenFactory(Ulid $sessionId, int $nextEventIdCallCount = 1): void
    {
        $opaqueToken = str_repeat('a', 43);
        $refreshTokenEntity = new AuthRefreshToken(
            (string) new Ulid(),
            (string) $sessionId,
            $opaqueToken,
            (new DateTimeImmutable())->modify('+1 month')
        );

        $this->authTokenFactory->method('generateOpaqueToken')->willReturn($opaqueToken);
        $this->authTokenFactory->method('createRefreshToken')->willReturn($refreshTokenEntity);
        $this->authTokenFactory->method('buildJwtPayload')->willReturn([
            'sub' => 'user-id',
            'iss' => 'vilnacrm-user-service',
            'aud' => 'vilnacrm-api',
            'exp' => time() + 900,
            'iat' => time(),
            'nbf' => time(),
            'jti' => 'test-jti',
            'sid' => (string) $sessionId,
            'roles' => ['ROLE_USER'],
        ]);
        $this->authTokenFactory
            ->expects($this->exactly($nextEventIdCallCount))
            ->method('nextEventId')
            ->willReturnCallback(static fn () => (string) Ulid::generate());
    }

    private function setupTokenGeneration(): void
    {
        $opaqueToken = str_repeat('a', 43);

        $this->authTokenFactory
            ->method('generateOpaqueToken')
            ->willReturn($opaqueToken);

        $this->authTokenFactory
            ->method('createRefreshToken')
            ->willReturnCallback(static fn (string $sessionId, string $plainToken, DateTimeImmutable $issuedAt) => new AuthRefreshToken(
                (string) new Ulid(),
                $sessionId,
                $plainToken,
                $issuedAt->modify('+1 month')
            ));

        $this->authTokenFactory
            ->method('buildJwtPayload')
            ->willReturn([]);

        $this->authTokenFactory
            ->method('nextEventId')
            ->willReturnCallback(static fn () => (string) Ulid::generate());

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
            $this->authTokenFactory,
            $this->eventBus,
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

    private function createPendingSessionWithRememberMe(
        string $userId,
        string $expiresAtModifier,
        bool $rememberMe
    ): PendingTwoFactor {
        $createdAt = new DateTimeImmutable('now');

        return new PendingTwoFactor(
            (string) new Ulid(),
            $userId,
            $createdAt,
            $createdAt->modify($expiresAtModifier),
            $rememberMe
        );
    }
}
