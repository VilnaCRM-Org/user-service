<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\CompleteTwoFactorCommand;
use App\User\Application\CommandHandler\CompleteTwoFactorCommandHandler;
use App\User\Application\Service\IssuedSession;
use App\User\Application\Service\SessionIssuanceServiceInterface;
use App\User\Application\Service\TwoFactorCodeVerifierInterface;
use App\User\Application\Service\TwoFactorEventPublisherInterface;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Ulid;

final class CompleteTwoFactorCommandHandlerSuccessPathTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private PendingTwoFactorRepositoryInterface&MockObject $pendingTwoFactorRepository;
    private SessionIssuanceServiceInterface&MockObject $sessionIssuanceService;
    private TwoFactorCodeVerifierInterface&MockObject $codeVerifier;
    private TwoFactorEventPublisherInterface&MockObject $eventPublisher;
    private UserFactory $userFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->pendingTwoFactorRepository = $this->createMock(PendingTwoFactorRepositoryInterface::class);
        $this->sessionIssuanceService = $this->createMock(SessionIssuanceServiceInterface::class);
        $this->codeVerifier = $this->createMock(TwoFactorCodeVerifierInterface::class);
        $this->eventPublisher = $this->createMock(TwoFactorEventPublisherInterface::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());
    }

    public function testInvokeCompletesTwoFactorAndIssuesTokens(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pendingSession = $this->createPendingSession($user->getId(), '+5 minutes');
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $sessionId = 'test-session-id';
        $accessToken = 'issued-access-token';
        $refreshToken = str_repeat('a', 43);

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

        $this->codeVerifier
            ->expects($this->once())
            ->method('resolveVerificationMethod')
            ->with($user, '123456')
            ->willReturn('totp');

        $this->codeVerifier
            ->expects($this->never())
            ->method('countRemainingCodes');

        $this->sessionIssuanceService
            ->expects($this->once())
            ->method('issue')
            ->with($user, $ipAddress, $userAgent, false, $this->isInstanceOf(DateTimeImmutable::class))
            ->willReturn(new IssuedSession($sessionId, $accessToken, $refreshToken));

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('delete')
            ->with($pendingSession);

        $this->eventPublisher
            ->expects($this->never())
            ->method('publishRecoveryCodeUsed');

        $this->eventPublisher
            ->expects($this->once())
            ->method('publishCompleted')
            ->with($user->getId(), $sessionId, $ipAddress, $userAgent, 'totp');

        $handler = $this->createHandler();
        $command = new CompleteTwoFactorCommand(
            $pendingSession->getId(),
            '123456',
            $ipAddress,
            $userAgent
        );

        $handler->__invoke($command);

        $this->assertSame($accessToken, $command->getResponse()->getAccessToken());
        $this->assertSame($refreshToken, $command->getResponse()->getRefreshToken());
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
        $this->codeVerifier->method('resolveVerificationMethod')->willReturn('totp');

        $this->sessionIssuanceService
            ->expects($this->once())
            ->method('issue')
            ->with($user, $this->anything(), $this->anything(), true, $this->anything())
            ->willReturn(new IssuedSession('session-id', 'access-token', 'refresh-token'));

        $this->pendingTwoFactorRepository->method('delete');
        $this->eventPublisher->method('publishCompleted');

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
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $sessionId = 'test-session-id';

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

        $this->codeVerifier
            ->expects($this->once())
            ->method('resolveVerificationMethod')
            ->with($user, 'AB12-CD34')
            ->willReturn('recovery_code');

        $this->codeVerifier
            ->expects($this->once())
            ->method('countRemainingCodes')
            ->with($user->getId())
            ->willReturn(0);

        $this->sessionIssuanceService
            ->expects($this->once())
            ->method('issue')
            ->willReturn(new IssuedSession($sessionId, 'issued-access-token', str_repeat('a', 43)));

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('delete')
            ->with($pendingSession);

        $this->eventPublisher
            ->expects($this->once())
            ->method('publishRecoveryCodeUsed')
            ->with($user->getId(), 0);

        $this->eventPublisher
            ->expects($this->once())
            ->method('publishCompleted')
            ->with($user->getId(), $sessionId, $ipAddress, $userAgent, 'recovery_code');

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
        $this->assertSame(0, $command->getResponse()->getRecoveryCodesRemaining());
        $this->assertSame(
            'All recovery codes have been used. Regenerate immediately.',
            $command->getResponse()->getWarningMessage()
        );
    }

    public function testInvokeUsesLaterUnusedRecoveryCodeWhenEarlierCodeIsUsed(): void
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

        $this->codeVerifier
            ->expects($this->once())
            ->method('resolveVerificationMethod')
            ->with($user, 'CC33-DD44')
            ->willReturn('recovery_code');

        $this->codeVerifier
            ->expects($this->once())
            ->method('countRemainingCodes')
            ->willReturn(1);

        $this->sessionIssuanceService
            ->method('issue')
            ->willReturn(new IssuedSession('session-id', 'test-access-token', 'refresh-token'));

        $this->pendingTwoFactorRepository->method('delete');
        $this->eventPublisher->method('publishRecoveryCodeUsed');
        $this->eventPublisher->method('publishCompleted');

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

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($pendingSession);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $this->codeVerifier
            ->expects($this->once())
            ->method('resolveVerificationMethod')
            ->willReturn('recovery_code');

        $this->codeVerifier
            ->expects($this->once())
            ->method('countRemainingCodes')
            ->willReturn(2);

        $this->sessionIssuanceService
            ->method('issue')
            ->willReturn(new IssuedSession('session-id', 'access-token', 'refresh-token'));

        $publishedRecoveryEvents = [];
        $this->eventPublisher
            ->method('publishRecoveryCodeUsed')
            ->willReturnCallback(static function (string $userId, int $remaining) use (&$publishedRecoveryEvents): void {
                $publishedRecoveryEvents[] = $remaining;
            });

        $this->pendingTwoFactorRepository->method('delete');
        $this->eventPublisher->method('publishCompleted');

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
        $this->assertSame([2], $publishedRecoveryEvents);
    }

    public function testRecoveryCodeSignInWithoutWarningWhenManyCodesRemain(): void
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

        $this->codeVerifier
            ->expects($this->once())
            ->method('resolveVerificationMethod')
            ->willReturn('recovery_code');

        $this->codeVerifier
            ->expects($this->once())
            ->method('countRemainingCodes')
            ->willReturn(5);

        $publishedRecoveryEvents = [];
        $this->eventPublisher
            ->method('publishRecoveryCodeUsed')
            ->willReturnCallback(static function (string $userId, int $remaining) use (&$publishedRecoveryEvents): void {
                $publishedRecoveryEvents[] = $remaining;
            });

        $this->sessionIssuanceService
            ->method('issue')
            ->willReturn(new IssuedSession('session-id', 'access-token', 'refresh-token'));

        $this->pendingTwoFactorRepository->method('delete');
        $this->eventPublisher->method('publishCompleted');

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
        $this->assertSame([5], $publishedRecoveryEvents);
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

        $this->codeVerifier
            ->expects($this->once())
            ->method('resolveVerificationMethod')
            ->willReturn('totp');

        $this->codeVerifier
            ->expects($this->never())
            ->method('countRemainingCodes');

        $this->sessionIssuanceService
            ->method('issue')
            ->willReturn(new IssuedSession('session-id', 'access-token', 'refresh-token'));

        $this->eventPublisher
            ->expects($this->once())
            ->method('publishCompleted')
            ->with($user->getId(), 'session-id', $this->anything(), $this->anything(), 'totp');

        $this->eventPublisher
            ->expects($this->never())
            ->method('publishRecoveryCodeUsed');

        $this->pendingTwoFactorRepository->method('delete');

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

    public function testTotpSignInFallsBackToStoredSecretWhenDecryptFails(): void
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

        $this->codeVerifier
            ->expects($this->once())
            ->method('resolveVerificationMethod')
            ->willReturn('totp');

        $this->sessionIssuanceService
            ->method('issue')
            ->willReturn(new IssuedSession('session-id', 'test-access-token', 'refresh-token'));

        $this->eventPublisher->method('publishCompleted');
        $this->pendingTwoFactorRepository->method('delete');

        $handler = $this->createHandler();
        $command = new CompleteTwoFactorCommand(
            $pendingSession->getId(),
            '123456',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );

        $handler->__invoke($command);

        $this->assertSame('test-access-token', $command->getResponse()->getAccessToken());
    }

    private function createHandler(): CompleteTwoFactorCommandHandler
    {
        return new CompleteTwoFactorCommandHandler(
            $this->userRepository,
            $this->pendingTwoFactorRepository,
            $this->sessionIssuanceService,
            $this->codeVerifier,
            $this->eventPublisher,
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
