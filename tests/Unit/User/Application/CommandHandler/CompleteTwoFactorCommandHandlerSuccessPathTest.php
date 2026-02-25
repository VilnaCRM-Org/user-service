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
use App\User\Domain\Contract\TwoFactorSecretEncryptorInterface;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
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
    private AuthSessionRepositoryInterface&MockObject $authSessionRepository;
    private AuthRefreshTokenRepositoryInterface&MockObject $authRefreshTokenRepository;
    private AccessTokenGeneratorInterface&MockObject $accessTokenGenerator;
    private AuthTokenFactoryInterface&MockObject $authTokenFactory;
    private TOTPVerifierInterface&MockObject $totpVerifier;
    private TwoFactorSecretEncryptorInterface&MockObject $encryptor;
    private RecoveryCodeRepositoryInterface&MockObject $recoveryCodeRepository;
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
        $this->authSessionRepository = $this->createMock(AuthSessionRepositoryInterface::class);
        $this->authRefreshTokenRepository = $this->createMock(AuthRefreshTokenRepositoryInterface::class);
        $this->accessTokenGenerator = $this->createMock(AccessTokenGeneratorInterface::class);
        $this->authTokenFactory = $this->createMock(AuthTokenFactoryInterface::class);
        $this->totpVerifier = $this->createMock(TOTPVerifierInterface::class);
        $this->encryptor = $this->createMock(TwoFactorSecretEncryptorInterface::class);
        $this->recoveryCodeRepository = $this->createMock(RecoveryCodeRepositoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->ulidFactory = $this->createMock(UlidFactory::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());

        $this->configureDefaultFactories();
    }

    public function testInvokeCompletesTwoFactorAndIssuesTokens(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();

        $this->configureLookupsOnce($pending, $user);

        $this->totpVerifier->expects($this->once())
            ->method('verify')
            ->with($user->getTwoFactorSecret(), '123456')
            ->willReturn(true);

        $this->authSessionRepository->expects($this->once())->method('save');
        $this->authRefreshTokenRepository->expects($this->once())->method('save');

        $this->accessTokenGenerator->expects($this->once())
            ->method('generate')
            ->willReturn('issued-access-token');

        $this->pendingTwoFactorRepository->expects($this->once())->method('delete')->with($pending);

        $this->eventBus->expects($this->once())->method('publish');

        $command = $this->invokeHandlerWith($pending->getId(), '123456', $ipAddress, $userAgent);
        $this->assertSame('issued-access-token', $command->getResponse()->getAccessToken());
        $this->assertNotEmpty($command->getResponse()->getRefreshToken());
        $this->assertNotSame(
            $command->getResponse()->getAccessToken(),
            $command->getResponse()->getRefreshToken()
        );
        $this->assertFalse($command->getResponse()->isRememberMe());
    }

    public function testInvokeCompletesTwoFactorWithRememberMeFromPendingSession(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSessionWithRememberMe($user->getId(), '+5 minutes', true);

        $this->pendingTwoFactorRepository->method('findById')->willReturn($pending);
        $this->userRepository->method('findById')->willReturn($user);

        $this->totpVerifier->method('verify')->willReturn(true);

        $this->authSessionRepository->method('save');
        $this->authRefreshTokenRepository->method('save');

        $this->accessTokenGenerator->method('generate')->willReturn('access-token');

        $this->pendingTwoFactorRepository->method('delete');
        $this->eventBus->method('publish');

        $command = $this->invokeHandlerWith(
            $pending->getId(),
            '123456',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );
        $this->assertTrue($command->getResponse()->isRememberMe());
    }

    public function testInvokeCompletesTwoFactorWithRecoveryCodeAndIssuesTokens(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $ip = $this->faker->ipv4();
        $ua = $this->faker->userAgent();

        $this->configureLookupsOnce($pending, $user);

        $recoveryCode = new RecoveryCode((string) new Ulid(), $user->getId(), 'AB12-CD34');
        $this->recoveryCodeRepository->method('findByUserId')
            ->with($user->getId())
            ->willReturn([$recoveryCode]);

        $this->recoveryCodeRepository->expects($this->once())->method('save')
            ->with($this->callback(static fn (RecoveryCode $c): bool => $c->isUsed()));

        $this->authSessionRepository->expects($this->once())->method('save');
        $this->authRefreshTokenRepository->expects($this->once())->method('save');

        $this->accessTokenGenerator->expects($this->once())
            ->method('generate')
            ->willReturn('issued-access-token');

        $this->pendingTwoFactorRepository->expects($this->once())->method('delete')->with($pending);

        $this->eventBus->expects($this->exactly(2))->method('publish');

        $command = $this->invokeHandlerWith($pending->getId(), 'AB12-CD34', $ip, $ua);
        $this->assertZeroRemainingCodesResponse($command, 'issued-access-token');
    }

    public function testInvokeUsesLaterUnusedRecoveryCodeWhenEarlierCodeIsUsed(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');

        $this->configureLookupsOnce($pending, $user);

        $usedCode = new RecoveryCode((string) new Ulid(), $user->getId(), 'AA11-BB22');
        $usedCode->markAsUsed();
        $targetCode = new RecoveryCode((string) new Ulid(), $user->getId(), 'CC33-DD44');

        $this->recoveryCodeRepository->method('findByUserId')
            ->with($user->getId())
            ->willReturn([$usedCode, $targetCode]);

        $this->recoveryCodeRepository->method('save');

        $this->authSessionRepository->method('save');
        $this->authRefreshTokenRepository->method('save');

        $this->accessTokenGenerator->method('generate')->willReturn('test-access-token');

        $this->pendingTwoFactorRepository->method('delete');
        $this->eventBus->method('publish');

        $command = $this->invokeHandlerWith(
            $pending->getId(),
            'CC33-DD44',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );
        $this->assertSame('test-access-token', $command->getResponse()->getAccessToken());
    }

    public function testRecoveryCodeSignInIncludesWarningWhenFewCodesRemain(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');

        $this->configureLookupsOnce($pending, $user);

        $targetCode = new RecoveryCode((string) new Ulid(), $user->getId(), 'AB12-CD34');
        $unusedCode1 = new RecoveryCode((string) new Ulid(), $user->getId(), 'EF56-GH78');
        $unusedCode2 = new RecoveryCode((string) new Ulid(), $user->getId(), 'IJ90-KL12');

        $this->recoveryCodeRepository->method('findByUserId')
            ->with($user->getId())
            ->willReturn([$targetCode, $unusedCode1, $unusedCode2]);

        $this->recoveryCodeRepository->method('save');

        $this->authSessionRepository->method('save');
        $this->authRefreshTokenRepository->method('save');

        $this->accessTokenGenerator->method('generate')->willReturn('access-token');

        $this->pendingTwoFactorRepository->method('delete');
        $this->eventBus->method('publish');

        $ip = $this->faker->ipv4();
        $ua = $this->faker->userAgent();
        $command = $this->invokeHandlerWith($pending->getId(), 'AB12-CD34', $ip, $ua);
        $this->assertSame(2, $command->getResponse()->getRecoveryCodesRemaining());
        $warning = (string) $command->getResponse()->getWarningMessage();
        $this->assertStringContainsString('2', $warning);
    }

    public function testRecoveryCodeSignInWithoutWarningWhenManyCodesRemain(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');

        $this->configureLookupsOnce($pending, $user);

        $targetCode = new RecoveryCode((string) new Ulid(), $user->getId(), 'AB12-CD34');
        $codes = [$targetCode];
        for ($i = 0; $i < 5; $i++) {
            $codes[] = new RecoveryCode((string) new Ulid(), $user->getId(), sprintf('XX%02d-YY%02d', $i, $i));
        }

        $this->recoveryCodeRepository->method('findByUserId')
            ->with($user->getId())
            ->willReturn($codes);

        $this->recoveryCodeRepository->method('save');

        $this->authSessionRepository->method('save');
        $this->authRefreshTokenRepository->method('save');

        $this->accessTokenGenerator->method('generate')->willReturn('access-token');

        $this->pendingTwoFactorRepository->method('delete');
        $this->eventBus->method('publish');

        $command = $this->invokeHandlerWith(
            $pending->getId(),
            'AB12-CD34',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );
        $this->assertNull($command->getResponse()->getRecoveryCodesRemaining());
        $this->assertNull($command->getResponse()->getWarningMessage());
    }

    public function testTotpSignInDoesNotIncludeRecoveryCodeInfo(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');

        $this->configureLookupsOnce($pending, $user);

        $this->totpVerifier->expects($this->once())
            ->method('verify')
            ->willReturn(true);

        $this->recoveryCodeRepository->expects($this->never())->method('save');

        $this->authSessionRepository->method('save');
        $this->authRefreshTokenRepository->method('save');

        $this->accessTokenGenerator->method('generate')->willReturn('access-token');

        $this->eventBus->expects($this->once())->method('publish');
        $this->pendingTwoFactorRepository->method('delete');

        $command = $this->invokeHandlerWith(
            $pending->getId(),
            '123456',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );
        $this->assertNull($command->getResponse()->getRecoveryCodesRemaining());
        $this->assertNull($command->getResponse()->getWarningMessage());
    }

    public function testTotpSignInFallsBackToStoredSecretWhenDecryptFails(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');

        $this->configureLookupsOnce($pending, $user);

        $this->encryptor->method('decrypt')
            ->willThrowException(new \RuntimeException('Decrypt failed'));

        $this->totpVerifier->expects($this->once())
            ->method('verify')
            ->with($user->getTwoFactorSecret(), '123456')
            ->willReturn(true);

        $this->authSessionRepository->method('save');
        $this->authRefreshTokenRepository->method('save');

        $this->accessTokenGenerator->method('generate')->willReturn('test-access-token');

        $this->eventBus->method('publish');
        $this->pendingTwoFactorRepository->method('delete');

        $command = $this->invokeHandlerWith(
            $pending->getId(),
            '123456',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );
        $this->assertSame('test-access-token', $command->getResponse()->getAccessToken());
    }

    private function configureDefaultFactories(): void
    {
        $this->authTokenFactory->method('generateOpaqueToken')
            ->willReturn('test-opaque-token-1234567890-abcdefghijklmn');
        $this->authTokenFactory->method('createRefreshToken')
            ->willReturnCallback(
                static function (
                    string $sessionId,
                    string $plain,
                    DateTimeImmutable $issuedAt
                ): AuthRefreshToken {
                    return new AuthRefreshToken(
                        (string) new Ulid(),
                        $sessionId,
                        $plain,
                        $issuedAt->modify('+1 month')
                    );
                }
            );
        $this->authTokenFactory->method('buildJwtPayload')
            ->willReturn([
                'sub' => 'test-user-id',
                'iss' => 'vilnacrm-user-service',
                'aud' => 'vilnacrm-api',
                'exp' => time() + 900,
                'iat' => time(),
                'nbf' => time(),
                'jti' => 'test-jti',
                'sid' => 'test-session-id',
                'roles' => ['ROLE_USER'],
            ]);
        $this->authTokenFactory->method('nextEventId')->willReturn('test-event-id');
        $this->encryptor->method('decrypt')->willReturnArgument(0);
        $this->ulidFactory->method('create')->willReturn(new Ulid());
    }

    private function configureLookupsOnce(PendingTwoFactor $pending, User $user): void
    {
        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->with($pending->getId())
            ->willReturn($pending);
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($user->getId())
            ->willReturn($user);
    }

    private function invokeHandlerWith(
        string $pendingId,
        string $code,
        string $ipAddress,
        string $userAgent
    ): CompleteTwoFactorCommand {
        $handler = $this->createHandler();
        $command = new CompleteTwoFactorCommand($pendingId, $code, $ipAddress, $userAgent);
        $handler->__invoke($command);
        return $command;
    }

    private function assertTokensIssued(
        CompleteTwoFactorCommand $command,
        string $expectedAccess,
        string $expectedRefresh
    ): void {
        $this->assertSame($expectedAccess, $command->getResponse()->getAccessToken());
        $this->assertSame($expectedRefresh, $command->getResponse()->getRefreshToken());
        $this->assertNotSame(
            $command->getResponse()->getAccessToken(),
            $command->getResponse()->getRefreshToken()
        );
    }

    private function assertZeroRemainingCodesResponse(
        CompleteTwoFactorCommand $command,
        string $expectedAccess
    ): void {
        $this->assertSame($expectedAccess, $command->getResponse()->getAccessToken());
        $this->assertNotEmpty($command->getResponse()->getRefreshToken());
        $this->assertSame(0, $command->getResponse()->getRecoveryCodesRemaining());
        $this->assertSame(
            'All recovery codes have been used. Regenerate immediately.',
            $command->getResponse()->getWarningMessage()
        );
    }

    private function createHandler(): CompleteTwoFactorCommandHandler
    {
        return new CompleteTwoFactorCommandHandler(
            $this->userRepository,
            $this->pendingTwoFactorRepository,
            $this->authSessionRepository,
            $this->authRefreshTokenRepository,
            $this->accessTokenGenerator,
            $this->authTokenFactory,
            $this->totpVerifier,
            $this->encryptor,
            $this->recoveryCodeRepository,
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
        $pending = new PendingTwoFactor(
            (string) new Ulid(),
            $userId,
            $createdAt,
            $createdAt->modify($expiresAtModifier)
        );

        if ($rememberMe) {
            return $pending->withRememberMe();
        }

        return $pending;
    }
}
