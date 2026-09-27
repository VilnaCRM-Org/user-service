<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\User\Application\Command\CompleteTwoFactorCommand;
use App\User\Application\DTO\IssuedSession;
use App\User\Application\Validator\TwoFactorCodeValidatorInterface;
use App\User\Domain\Entity\PendingTwoFactor;
use DateTimeImmutable;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class CompleteTwoFactorCommandHandlerBruteForceTest extends
    CompleteTwoFactorCommandHandlerTestCase
{
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

    public function testInvokeDoesNotConsumeRecoveryCodeWhenPendingSessionConsumeFails(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $this->configureLookupsOnce($pending, $user);
        $this->twoFactorCodeVerifier->expects($this->once())
            ->method('verifyAndResolveMethod')
            ->with($user, 'AB12-CD34')
            ->willReturn(TwoFactorCodeValidatorInterface::METHOD_RECOVERY_CODE);
        $this->twoFactorCodeVerifier->expects($this->never())->method('consumeRecoveryCodeOrFail');
        $this->pendingTwoFactorRepository->expects($this->once())
            ->method('consumeIfActive')
            ->with($pending->getId(), $this->isInstanceOf(DateTimeImmutable::class))
            ->willReturn(false);
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired two-factor session.');
        $this->createHandler()->__invoke($this->createCommand($pending->getId(), 'AB12-CD34'));
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

    public function testInvokeIncrementsAndPersistsFailedAttemptOnInvalidCode(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $this->configureLookupsOnce($pending, $user);
        $this->twoFactorCodeVerifier->method('verifyAndResolveMethod')->willReturn(null);

        $this->pendingTwoFactorRepository->expects($this->once())
            ->method('save')
            ->with($pending);
        $this->pendingTwoFactorRepository->expects($this->never())->method('delete');

        try {
            $this->createHandler()->__invoke($this->createCommand($pending->getId(), '123456'));
            $this->fail('Expected UnauthorizedHttpException.');
        } catch (UnauthorizedHttpException) {
            $this->assertSame(1, $pending->getFailedAttempts());
        }
    }

    public function testInvokeInvalidatesPendingSessionAfterMaxFailedAttempts(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $pending->setFailedAttempts(PendingTwoFactor::MAX_FAILED_ATTEMPTS - 1);
        $this->configureLookupsOnce($pending, $user);
        $this->twoFactorCodeVerifier->method('verifyAndResolveMethod')->willReturn(null);

        $this->pendingTwoFactorRepository->expects($this->once())
            ->method('delete')
            ->with($pending);
        $this->pendingTwoFactorRepository->expects($this->never())->method('save');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');
        $this->createHandler()->__invoke($this->createCommand($pending->getId(), '123456'));
    }

    public function testInvokeRejectsPendingSessionThatAlreadyExhaustedAttempts(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $pending->setFailedAttempts(PendingTwoFactor::MAX_FAILED_ATTEMPTS);
        $this->pendingTwoFactorRepository->expects($this->once())
            ->method('findById')
            ->with($pending->getId())
            ->willReturn($pending);
        $this->userRepository->expects($this->never())->method('findById');
        $this->twoFactorCodeVerifier->expects($this->never())->method('verifyAndResolveMethod');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired two-factor session.');
        $this->createHandler()->__invoke($this->createCommand($pending->getId(), '123456'));
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
        $command = new CompleteTwoFactorCommand(
            $pending->getId(),
            '123456',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );

        $response = $this->createHandler()->__invoke($command);
        $this->assertResponseTokens($response, 'access-token', 'refresh-token');
    }

    public function testInvokeFailsWithoutCountingAttemptWhenRecoveryConsumeFails(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $this->arrangeRecoveryConsumeFailureAfterSessionConsumed($pending, $user);

        try {
            $this->createHandler()->__invoke($this->createCommand($pending->getId(), 'AB12-CD34'));
            $this->fail('Expected UnauthorizedHttpException.');
        } catch (UnauthorizedHttpException $exception) {
            $this->assertSame('Invalid two-factor code.', $exception->getMessage());
            $this->assertSame(0, $pending->getFailedAttempts());
        }
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
        $this->sessionIssuer->expects($this->never())->method('create');
        $this->events->expects($this->never())->method('publishCompleted');
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired two-factor session.');
        $this->createHandler()->__invoke($this->createCommand($pending->getId(), '123456'));
    }
}
