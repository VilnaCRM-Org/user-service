<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\User\Application\Command\CompleteTwoFactorCommand;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class CompleteTwoFactorCommandHandlerTest extends CompleteTwoFactorCommandHandlerTestCase
{
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
        $this->pendingTwoFactorRepository->expects($this->once())
            ->method('findById')
            ->with($expired->getId())
            ->willReturn($expired);
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
}
