<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\CompleteTwoFactorCommand;
use App\User\Application\CommandHandler\CompleteTwoFactorCommandHandler;
use App\User\Application\DTO\IssuedSession;
use App\User\Application\Processor\EventPublisher\TwoFactorEventsInterface;
use App\User\Application\Processor\Issuer\SessionIssuerInterface;
use App\User\Application\Validator\Verifier\TwoFactorCodeVerifierInterface;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Uid\Ulid;

final class CompleteTwoFactorCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private PendingTwoFactorRepositoryInterface&MockObject $pendingTwoFactorRepository;
    private SessionIssuerInterface&MockObject $sessionIssuer;
    private TwoFactorCodeVerifierInterface&MockObject $twoFactorCodeVerifier;
    private TwoFactorEventsInterface&MockObject $events;
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
        $this->sessionIssuer = $this->createMock(SessionIssuerInterface::class);
        $this->twoFactorCodeVerifier = $this->createMock(TwoFactorCodeVerifierInterface::class);
        $this->events = $this->createMock(TwoFactorEventsInterface::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());
    }

    public function testInvokeThrowsUnauthorizedWhenPendingSessionIsMissing(): void
    {
        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->with('missing-session')
            ->willReturn(null);
        $this->userRepository->expects($this->never())->method('findById');
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired two-factor session.');
        $this->createHandler()->__invoke(new CompleteTwoFactorCommand(
            'missing-session',
            '123456',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        ));
    }

    public function testInvokeThrowsUnauthorizedWhenPendingSessionIsExpired(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $expired = $this->createPendingSession($user->getId(), '-1 second');
        $this->configurePendingLookupOnce($expired);
        $this->userRepository->expects($this->never())->method('findById');
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired two-factor session.');
        $this->createHandler()->__invoke(new CompleteTwoFactorCommand(
            $expired->getId(),
            '123456',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        ));
    }

    public function testInvokeThrowsUnauthorizedWhenUserDoesNotRequireTwoFactor(): void
    {
        $user = $this->createUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $this->configureLookupsOnce($pending, $user);
        $this->twoFactorCodeVerifier->expects($this->never())->method('verifyAndResolveMethod');
        $this->events->expects($this->never())->method('publishCompleted');
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired two-factor session.');
        $this->createHandler()->__invoke(new CompleteTwoFactorCommand(
            $pending->getId(),
            '123456',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        ));
    }

    public function testInvokeThrowsUnauthorizedWhenTotpSecretIsMissing(): void
    {
        $user = $this->createTwoFactorEnabledUserWithoutSecret();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $this->configureLookupsOnce($pending, $user);
        $this->twoFactorCodeVerifier->expects($this->once())
            ->method('verifyAndResolveMethod')
            ->with($user, '123456')
            ->willReturn(null);
        $this->events->expects($this->once())->method('publishFailed');
        $this->pendingTwoFactorRepository->expects($this->never())->method('consumeIfActive');
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');
        $this->createHandler()->__invoke(new CompleteTwoFactorCommand(
            $pending->getId(),
            '123456',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        ));
    }

    public function testInvokeThrowsUnauthorizedWhenCodeFormatIsInvalid(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $this->configureLookupsOnce($pending, $user);
        $this->twoFactorCodeVerifier->expects($this->once())
            ->method('verifyAndResolveMethod')
            ->with($user, 'abc-123')
            ->willReturn(null);
        $this->events->expects($this->once())->method('publishFailed');
        $this->pendingTwoFactorRepository->expects($this->never())->method('consumeIfActive');
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');
        $this->createHandler()->__invoke(new CompleteTwoFactorCommand(
            $pending->getId(),
            'abc-123',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        ));
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
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $this->configureLookupsOnce($pending, $user);

        $this->twoFactorCodeVerifier->expects($this->once())
            ->method('verifyAndResolveMethod')
            ->with($user, 'EF55-GH66')
            ->willReturn(null);

        $this->events->expects($this->once())->method('publishFailed');
        $this->pendingTwoFactorRepository->expects($this->never())->method('consumeIfActive');
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');
        $this->createHandler()->__invoke(new CompleteTwoFactorCommand(
            $pending->getId(),
            'EF55-GH66',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        ));
    }

    public function testInvokeThrowsUnauthorizedWhenTotpVerificationFails(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $this->configureLookupsOnce($pending, $user);

        $this->twoFactorCodeVerifier->expects($this->once())
            ->method('verifyAndResolveMethod')
            ->with($user, '123456')
            ->willReturn(null);

        $this->events->expects($this->once())->method('publishFailed');
        $this->pendingTwoFactorRepository->expects($this->never())->method('consumeIfActive');
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');
        $this->createHandler()->__invoke(new CompleteTwoFactorCommand(
            $pending->getId(),
            '123456',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        ));
    }

    public function testInvokeSucceedsWithValidTotpCode(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $this->configureLookupsOnce($pending, $user);
        $this->expectTotpVerification($user, '123456');

        $issued = new IssuedSession('session-id', 'access-token', 'refresh-token');
        $this->expectIssuedSession($issued);
        $this->pendingTwoFactorRepository->expects($this->once())
            ->method('consumeIfActive')
            ->with($pending->getId(), $this->isInstanceOf(DateTimeImmutable::class))
            ->willReturn(true);
        $this->events->expects($this->once())->method('publishCompleted');
        $command = $this->createCommand($pending->getId(), '123456');

        $this->createHandler()->__invoke($command);
        $this->assertResponseTokens($command, 'access-token', 'refresh-token');
    }

    public function testInvokeThrowsUnauthorizedWhenPendingSessionConsumeFails(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $this->configureLookupsOnce($pending, $user);
        $this->expectTotpVerification($user, '123456');
        $this->pendingTwoFactorRepository->expects($this->once())
            ->method('consumeIfActive')
            ->with($pending->getId(), $this->isInstanceOf(DateTimeImmutable::class))
            ->willReturn(false);
        $this->sessionIssuer->expects($this->never())->method('issue');
        $this->events->expects($this->never())->method('publishCompleted');
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired two-factor session.');

        $this->createHandler()->__invoke(new CompleteTwoFactorCommand(
            $pending->getId(),
            '123456',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        ));
    }

    private function assertInvalidTwoFactorCodeRejected(string $twoFactorCode): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $this->configureLookupsOnce($pending, $user);
        $this->twoFactorCodeVerifier->method('verifyAndResolveMethod')->willReturn(null);
        $this->events->method('publishFailed');
        try {
            $this->createHandler()->__invoke(new CompleteTwoFactorCommand(
                $pending->getId(),
                $twoFactorCode,
                $this->faker->ipv4(),
                $this->faker->userAgent()
            ));
            $this->fail('Expected UnauthorizedHttpException to be thrown.');
        } catch (UnauthorizedHttpException $exception) {
            $this->assertSame('Invalid two-factor code.', $exception->getMessage());
        }
    }

    private function configurePendingLookupOnce(PendingTwoFactor $pending): void
    {
        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->with($pending->getId())
            ->willReturn($pending);
    }

    private function configureLookupsOnce(PendingTwoFactor $pending, User $user): void
    {
        $this->configurePendingLookupOnce($pending);
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

    private function expectIssuedSession(IssuedSession $issued): void
    {
        $this->sessionIssuer->expects($this->once())
            ->method('issue')
            ->willReturn($issued);
    }

    private function createCommand(string $pendingId, string $code): CompleteTwoFactorCommand
    {
        return new CompleteTwoFactorCommand(
            $pendingId,
            $code,
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );
    }

    private function assertResponseTokens(
        CompleteTwoFactorCommand $command,
        string $expectedAccessToken,
        string $expectedRefreshToken
    ): void {
        $response = $command->getResponse();
        $this->assertSame($expectedAccessToken, $response->getAccessToken());
        $this->assertSame($expectedRefreshToken, $response->getRefreshToken());
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

    private function createUser(): User
    {
        return $this->userFactory->create(
            $this->faker->email(),
            $this->faker->firstName(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
    }

    private function createTwoFactorEnabledUser(): User
    {
        $user = $this->createUser();
        $user->setTwoFactorEnabled(true);
        $user->setTwoFactorSecret('JBSWY3DPEHPK3PXP');
        return $user;
    }

    private function createTwoFactorEnabledUserWithoutSecret(): User
    {
        $user = $this->createUser();
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
