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
        $this->pendingTwoFactorRepository =
            $this->createMock(PendingTwoFactorRepositoryInterface::class);
        $this->sessionIssuanceService = $this->createMock(SessionIssuanceServiceInterface::class);
        $this->codeVerifier = $this->createMock(TwoFactorCodeVerifierInterface::class);
        $this->eventPublisher = $this->createMock(TwoFactorEventPublisherInterface::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());
    }

    public function testInvokeCompletesTwoFactorAndIssuesTokens(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $session = new IssuedSession('test-session-id', 'issued-access-token', str_repeat('a', 43));
        $this->configureLookupsOnce($pending, $user);
        $this->configureVerificationOnce($user, '123456', 'totp');
        $this->codeVerifier->expects($this->never())->method('countRemainingCodes');
        $this->configureIssuanceOnce($user, $ipAddress, $userAgent, false, $session);
        $this->pendingTwoFactorRepository
            ->expects($this->once())->method('delete')->with($pending);
        $this->configureTotpEventExpectations(
            $user->getId(),
            'test-session-id',
            $ipAddress,
            $userAgent
        );
        $command = $this->invokeHandlerWith($pending->getId(), '123456', $ipAddress, $userAgent);
        $this->assertTokensIssued($command, 'issued-access-token', str_repeat('a', 43));
        $this->assertFalse($command->getResponse()->isRememberMe());
    }

    public function testInvokeCompletesTwoFactorWithRememberMeFromPendingSession(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSessionWithRememberMe($user->getId(), '+5 minutes', true);
        $session = new IssuedSession('session-id', 'access-token', 'refresh-token');
        $this->pendingTwoFactorRepository->method('findById')
            ->willReturn($pending);
        $this->userRepository->method('findById')
            ->willReturn($user);
        $this->codeVerifier->method('resolveVerificationMethod')->willReturn('totp');
        $this->configureRememberMeIssuanceOnce($user, $session);
        $this->pendingTwoFactorRepository->method('delete');
        $this->eventPublisher->method('publishCompleted');
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
        $session = new IssuedSession('test-session-id', 'issued-access-token', str_repeat('a', 43));
        $this->configureLookupsOnce($pending, $user);
        $this->configureRecoveryVerificationOnce($user, 'AB12-CD34', 0);
        $this->sessionIssuanceService->expects($this->once())
            ->method('issue')->willReturn($session);
        $this->pendingTwoFactorRepository->expects($this->once())->method('delete')->with($pending);
        $this->configureRecoveryEventExpectations($user->getId(), 0, 'test-session-id', $ip, $ua);
        $command = $this->invokeHandlerWith($pending->getId(), 'AB12-CD34', $ip, $ua);
        $this->assertZeroRemainingCodesResponse($command, 'issued-access-token');
    }

    public function testInvokeUsesLaterUnusedRecoveryCodeWhenEarlierCodeIsUsed(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $this->configureLookupsOnce($pending, $user);
        $this->configureRecoveryVerificationOnce($user, 'CC33-DD44', 1);
        $this->sessionIssuanceService->method('issue')->willReturn(
            new IssuedSession('session-id', 'test-access-token', 'refresh-token')
        );
        $this->pendingTwoFactorRepository->method('delete');
        $this->eventPublisher->method('publishRecoveryCodeUsed');
        $this->eventPublisher->method('publishCompleted');
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
        $this->configureRecoveryVerificationOnce($user, 'AB12-CD34', 2);
        $session = new IssuedSession('session-id', 'access-token', 'refresh-token');
        $this->sessionIssuanceService->method('issue')->willReturn($session);
        $published = [];
        $this->captureRecoveryCodeEvents($published);
        $this->pendingTwoFactorRepository->method('delete');
        $this->eventPublisher->method('publishCompleted');
        $ip = $this->faker->ipv4();
        $ua = $this->faker->userAgent();
        $command = $this->invokeHandlerWith($pending->getId(), 'AB12-CD34', $ip, $ua);
        $this->assertSame(2, $command->getResponse()->getRecoveryCodesRemaining());
        $warning = (string) $command->getResponse()->getWarningMessage();
        $this->assertStringContainsString('2', $warning);
        $this->assertSame([2], $published);
    }

    public function testRecoveryCodeSignInWithoutWarningWhenManyCodesRemain(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $this->configureLookupsOnce($pending, $user);
        $this->configureRecoveryVerificationOnce($user, 'AB12-CD34', 5);
        $published = [];
        $this->captureRecoveryCodeEvents($published);
        $this->sessionIssuanceService->method('issue')->willReturn(
            new IssuedSession('session-id', 'access-token', 'refresh-token')
        );
        $this->pendingTwoFactorRepository->method('delete');
        $this->eventPublisher->method('publishCompleted');
        $command = $this->invokeHandlerWith(
            $pending->getId(),
            'AB12-CD34',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );
        $this->assertNull($command->getResponse()->getRecoveryCodesRemaining());
        $this->assertNull($command->getResponse()->getWarningMessage());
        $this->assertSame([5], $published);
    }

    public function testTotpSignInDoesNotIncludeRecoveryCodeInfo(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $this->configureLookupsOnce($pending, $user);
        $this->configureVerificationOnce($user, '123456', 'totp');
        $this->codeVerifier->expects($this->never())->method('countRemainingCodes');
        $this->sessionIssuanceService->method('issue')->willReturn(
            new IssuedSession('session-id', 'access-token', 'refresh-token')
        );
        $this->eventPublisher->expects($this->once())->method('publishCompleted')
            ->with($user->getId(), 'session-id', $this->anything(), $this->anything(), 'totp');
        $this->eventPublisher->expects($this->never())->method('publishRecoveryCodeUsed');
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
        $this->configureVerificationOnce($user, '123456', 'totp');
        $this->sessionIssuanceService->method('issue')->willReturn(
            new IssuedSession('session-id', 'test-access-token', 'refresh-token')
        );
        $this->eventPublisher->method('publishCompleted');
        $this->pendingTwoFactorRepository->method('delete');
        $command = $this->invokeHandlerWith(
            $pending->getId(),
            '123456',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );
        $this->assertSame('test-access-token', $command->getResponse()->getAccessToken());
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

    private function configureVerificationOnce(User $user, string $code, string $method): void
    {
        $this->codeVerifier
            ->expects($this->once())
            ->method('resolveVerificationMethod')
            ->with($user, $code)
            ->willReturn($method);
    }

    private function configureRecoveryVerificationOnce(
        User $user,
        string $code,
        int $remaining
    ): void {
        $this->configureVerificationOnce($user, $code, 'recovery_code');
        $this->codeVerifier
            ->expects($this->once())
            ->method('countRemainingCodes')
            ->with($user->getId())
            ->willReturn($remaining);
    }

    private function configureIssuanceOnce(
        User $user,
        string $ipAddress,
        string $userAgent,
        bool $rememberMe,
        IssuedSession $session
    ): void {
        $this->sessionIssuanceService
            ->expects($this->once())
            ->method('issue')
            ->with(
                $user,
                $ipAddress,
                $userAgent,
                $rememberMe,
                $this->isInstanceOf(DateTimeImmutable::class)
            )
            ->willReturn($session);
    }

    private function configureRememberMeIssuanceOnce(User $user, IssuedSession $session): void
    {
        $this->sessionIssuanceService
            ->expects($this->once())
            ->method('issue')
            ->with($user, $this->anything(), $this->anything(), true, $this->anything())
            ->willReturn($session);
    }

    private function configureTotpEventExpectations(
        string $userId,
        string $sessionId,
        string $ipAddress,
        string $userAgent
    ): void {
        $this->eventPublisher
            ->expects($this->never())
            ->method('publishRecoveryCodeUsed');
        $this->eventPublisher
            ->expects($this->once())
            ->method('publishCompleted')
            ->with($userId, $sessionId, $ipAddress, $userAgent, 'totp');
    }

    private function configureRecoveryEventExpectations(
        string $userId,
        int $remainingCodes,
        string $sessionId,
        string $ipAddress,
        string $userAgent
    ): void {
        $this->eventPublisher
            ->expects($this->once())
            ->method('publishRecoveryCodeUsed')
            ->with($userId, $remainingCodes);
        $this->eventPublisher
            ->expects($this->once())
            ->method('publishCompleted')
            ->with($userId, $sessionId, $ipAddress, $userAgent, 'recovery_code');
    }

    /**
     * @param array<int, int> $published
     */
    private function captureRecoveryCodeEvents(array &$published): void
    {
        $this->eventPublisher->method('publishRecoveryCodeUsed')->willReturnCallback(
            static function (string $userId, int $remaining) use (&$published): void {
                $published[] = $remaining;
            }
        );
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
