<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\CompleteTwoFactorCommand;
use App\User\Application\CommandHandler\CompleteTwoFactorCommandHandler;
use App\User\Application\DTO\IssuedSession;
use App\User\Application\Factory\IssuedSessionFactoryInterface;
use App\User\Application\Validator\Verifier\TwoFactorCodeVerifierInterface;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Publisher\TwoFactorPublisherInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Ulid;

final class CompleteTwoFactorCommandHandlerSuccessPathTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private PendingTwoFactorRepositoryInterface&MockObject $pendingTwoFactorRepository;
    private IssuedSessionFactoryInterface&MockObject $sessionIssuer;
    private TwoFactorCodeVerifierInterface&MockObject $twoFactorCodeVerifier;
    private TwoFactorPublisherInterface&MockObject $events;
    private UserFactory $userFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->pendingTwoFactorRepository = $this->createMock(
            PendingTwoFactorRepositoryInterface::class
        );
        $this->sessionIssuer = $this->createMock(IssuedSessionFactoryInterface::class);
        $this->twoFactorCodeVerifier = $this->createMock(TwoFactorCodeVerifierInterface::class);
        $this->events = $this->createMock(TwoFactorPublisherInterface::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());
    }

    public function testInvokeCompletesTwoFactorAndIssuesTokens(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();

        $this->configureLookupsOnce($pending, $user);
        $this->expectTotpVerification($user, '123456');
        $this->expectIssuedSession('issued-access-token', 'issued-refresh-token');
        $this->expectPendingConsumeAndCompletion($pending);

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

        $this->twoFactorCodeVerifier->method('verifyAndResolveMethod')->willReturn('totp');

        $issued = new IssuedSession((string) new Ulid(), 'access-token', 'refresh-token');
        $this->sessionIssuer->method('create')->willReturn($issued);

        $this->pendingTwoFactorRepository->method('consumeIfActive')->willReturn(true);
        $this->events->method('publishCompleted');

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
        $this->expectRecoveryCodeVerification($user, 'AB12-CD34', 0);
        $this->expectIssuedSession('issued-access-token', 'issued-refresh-token');
        $this->expectPendingConsumeAndCompletion($pending);
        $this->events->expects($this->once())->method('publishRecoveryCodeUsed');

        $command = $this->invokeHandlerWith($pending->getId(), 'AB12-CD34', $ip, $ua);
        $this->assertZeroRemainingCodesResponse($command, 'issued-access-token');
    }

    public function testInvokeUsesLaterUnusedRecoveryCodeWhenEarlierCodeIsUsed(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $this->configureLookupsOnce($pending, $user);
        $this->expectRecoveryCodeVerification($user, 'CC33-DD44', 4);
        $this->expectIssuedSession('test-access-token', 'test-refresh-token');
        $this->expectPendingConsumeAndCompletion($pending);
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
        $this->expectRecoveryCodeVerification($user, 'AB12-CD34', 2);
        $this->expectIssuedSession('access-token', 'refresh-token');
        $this->expectPendingConsumeAndCompletion($pending);
        $this->events->expects($this->once())->method('publishRecoveryCodeUsed');
        $command = $this->invokeHandlerWith(
            $pending->getId(),
            'AB12-CD34',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );
        $this->assertSame(2, $command->getResponse()->getRecoveryCodesRemaining());
        $this->assertStringContainsString(
            '2',
            (string) $command->getResponse()->getWarningMessage()
        );
    }

    public function testRecoveryCodeSignInWithoutWarningWhenManyCodesRemain(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');

        $this->configureLookupsOnce($pending, $user);
        $this->expectRecoveryCodeVerification($user, 'AB12-CD34', 6);
        $this->expectIssuedSession('access-token', 'refresh-token');
        $this->expectPendingConsumeAndCompletion($pending);

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

        $this->twoFactorCodeVerifier->expects($this->once())
            ->method('verifyAndResolveMethod')
            ->with($user, '123456')
            ->willReturn('totp');

        $this->twoFactorCodeVerifier->expects($this->never())->method('countRemainingCodes');
        $this->twoFactorCodeVerifier->expects($this->never())->method('consumeRecoveryCodeOrFail');

        $this->expectIssuedSession('access-token', 'refresh-token');

        $this->events->expects($this->once())->method('publishCompleted');
        $this->pendingTwoFactorRepository->method('consumeIfActive')->willReturn(true);

        $command = $this->invokeHandlerWith(
            $pending->getId(),
            '123456',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );
        $this->assertNull($command->getResponse()->getRecoveryCodesRemaining());
        $this->assertNull($command->getResponse()->getWarningMessage());
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

    private function expectTotpVerification(User $user, string $code): void
    {
        $this->twoFactorCodeVerifier->expects($this->once())
            ->method('verifyAndResolveMethod')
            ->with($user, $code)
            ->willReturn('totp');
    }

    private function expectRecoveryCodeVerification(
        User $user,
        string $code,
        int $remainingCodes
    ): void {
        $this->twoFactorCodeVerifier->expects($this->once())
            ->method('verifyAndResolveMethod')
            ->with($user, $code)
            ->willReturn('recovery_code');
        $this->twoFactorCodeVerifier->expects($this->once())
            ->method('consumeRecoveryCodeOrFail')
            ->with($user, $code);
        $this->twoFactorCodeVerifier->expects($this->once())
            ->method('countRemainingCodes')
            ->with($user->getId())
            ->willReturn($remainingCodes);
    }

    private function expectIssuedSession(string $accessToken, string $refreshToken): void
    {
        $issued = new IssuedSession((string) new Ulid(), $accessToken, $refreshToken);
        $this->sessionIssuer->expects($this->once())
            ->method('create')
            ->willReturn($issued);
    }

    private function expectPendingConsumeAndCompletion(PendingTwoFactor $pending): void
    {
        $this->pendingTwoFactorRepository->expects($this->once())
            ->method('consumeIfActive')
            ->with($pending->getId(), $this->isInstanceOf(DateTimeImmutable::class))
            ->willReturn(true);
        $this->events->expects($this->once())->method('publishCompleted');
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
            $this->sessionIssuer,
            $this->twoFactorCodeVerifier,
            $this->events,
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
