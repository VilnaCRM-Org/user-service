<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\User\Application\Command\RefreshTokenCommand;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\User;
use DateTimeImmutable;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Uid\Ulid;

final class RefreshTokenTheftDetectionTest extends RefreshTokenCommandHandlerTestCase
{
    public function testTheftDetectedWhenGraceEligibilityCheckFails(): void
    {
        $plainToken = 'grace-eligibility-fails-token';
        $token = $this->createValidRefreshToken($plainToken);
        $token->markAsRotated(new DateTimeImmutable('-30 seconds'));
        $session = $this->createValidSession($token->getSessionId());
        $user = $this->createUser();
        $this->expectTokenLookup($token, $plainToken);
        $this->expectSessionLookup($session);
        $this->expectUserLookup($user);
        $this->expectFailedGraceEligibility();
        $this->expectTokenRevocationSave();
        $this->expectTheftDetectionResponse($session, $user, 'double_grace_use', [$token]);
        $this->expectInvalidTokenException();
        $this->createHandler()->__invoke(new RefreshTokenCommand($plainToken));
    }

    public function testTheftDetectedWhenGraceUsedTwice(): void
    {
        $plainToken = 'double-grace-token';
        $token = $this->createValidRefreshToken($plainToken);
        $token->markAsRotated(new DateTimeImmutable('-30 seconds'));
        $token->markGraceUsed();
        $session = $this->createValidSession($token->getSessionId());
        $user = $this->createUser();
        $this->expectTokenLookup($token, $plainToken);
        $this->expectSessionLookup($session);
        $this->expectUserLookup($user);
        $this->expectNeverGraceEligibilityCheck();
        $this->expectTokenRevocationSave();
        $this->expectTheftDetectionResponse($session, $user, 'double_grace_use', [$token]);
        $this->expectException(UnauthorizedHttpException::class);
        $this->createHandler()->__invoke(new RefreshTokenCommand($plainToken));
    }

    public function testTheftDetectedWhenGracePeriodExpired(): void
    {
        $plainToken = 'post-grace-token';
        $token = $this->createValidRefreshToken($plainToken);
        $token->markAsRotated(new DateTimeImmutable('-120 seconds'));
        $session = $this->createValidSession($token->getSessionId());
        $user = $this->createUser();
        $this->expectTokenLookup($token, $plainToken);
        $this->expectSessionLookup($session);
        $this->expectUserLookup($user);
        $this->expectTokenRevocationSave();
        $this->expectTheftDetectionResponse($session, $user, 'grace_period_expired', [$token]);
        $this->expectException(UnauthorizedHttpException::class);
        $this->createHandler()->__invoke(new RefreshTokenCommand($plainToken));
    }

    public function testTheftDetectionUsesOldTokenWhenSessionLookupReturnsEmpty(): void
    {
        $plainToken = 'fallback-old-token';
        $token = $this->createValidRefreshToken($plainToken);
        $token->markAsRotated(new DateTimeImmutable('-30 seconds'));
        $token->markGraceUsed();
        $session = $this->createValidSession($token->getSessionId());
        $user = $this->createUser();
        $this->expectTokenLookup($token, $plainToken);
        $this->expectSessionLookup($session);
        $this->expectUserLookup($user);
        $this->expectNeverGraceEligibilityCheck();
        $this->expectTokenRevocationSave();
        $this->expectTheftDetectionResponse($session, $user, 'double_grace_use', []);
        $this->expectException(UnauthorizedHttpException::class);
        $this->createHandler()->__invoke(new RefreshTokenCommand($plainToken));
    }

    public function testTheftDetectionSkipsAlreadyRevokedTokens(): void
    {
        $plainToken = 'skip-revoked-token';
        $token = $this->createValidRefreshToken($plainToken);
        $token->markAsRotated(new DateTimeImmutable('-30 seconds'));
        $token->markGraceUsed();
        $session = $this->createValidSession($token->getSessionId());
        $user = $this->createUser();
        $alreadyRevokedToken = $this->createRevokedToken($session->getId());
        $this->expectTokenLookup($token, $plainToken);
        $this->expectSessionLookup($session);
        $this->expectUserLookup($user);
        $this->expectNeverGraceEligibilityCheck();
        $this->refreshTokenRepository->expects($this->never())->method('save');
        $this->expectTheftDetectionResponse(
            $session,
            $user,
            'double_grace_use',
            [$alreadyRevokedToken]
        );
        $this->expectException(UnauthorizedHttpException::class);
        $this->createHandler()->__invoke(new RefreshTokenCommand($plainToken));
    }

    public function testTheftDetectionRevokesLaterActiveTokenAfterRevokedToken(): void
    {
        $plainToken = 'revoked-then-active-token';
        $token = $this->createValidRefreshToken($plainToken);
        $token->markAsRotated(new DateTimeImmutable('-30 seconds'));
        $token->markGraceUsed();
        $session = $this->createValidSession($token->getSessionId());
        $user = $this->createUser();
        $alreadyRevokedToken = $this->createRevokedToken($session->getId());
        $activeToken = $this->createActiveToken($session->getId());
        $this->expectTokenLookup($token, $plainToken);
        $this->expectSessionLookup($session);
        $this->expectUserLookup($user);
        $this->expectNeverGraceEligibilityCheck();
        $this->expectSpecificTokenRevocation($activeToken);
        $sessionTokens = [$alreadyRevokedToken, $activeToken];
        $this->expectTheftDetectionResponse($session, $user, 'double_grace_use', $sessionTokens);
        $this->expectException(UnauthorizedHttpException::class);
        $this->createHandler()->__invoke(new RefreshTokenCommand($plainToken));
    }

    private function expectNeverGraceEligibilityCheck(): void
    {
        $this->refreshTokenRepository
            ->expects($this->never())
            ->method('markGraceUsedIfEligible');
    }

    private function expectFailedGraceEligibility(): void
    {
        $this->expectGraceEligibilityCheck(false);
    }

    private function expectTokenRevocationSave(): void
    {
        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (
                    AuthRefreshToken $t
                ): bool => $t->isRevoked()
            ));
    }

    private function expectSpecificTokenRevocation(
        AuthRefreshToken $expectedToken
    ): void {
        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (
                    AuthRefreshToken $t
                ): bool => $t->getId() === $expectedToken->getId()
                    && $t->isRevoked()
            ));
    }

    /**
     * @param array<AuthRefreshToken> $sessionTokens
     */
    private function expectTheftDetectionResponse(
        AuthSession $session,
        User $user,
        string $reason,
        array $sessionTokens
    ): void {
        $this->refreshTokenRepository->expects($this->once())
            ->method('findBySessionId')->with($session->getId())
            ->willReturn($sessionTokens);
        $this->authSessionRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (AuthSession $s): bool => $s->isRevoked()
            ));
        $this->eventPublisher
            ->expects($this->once())
            ->method('publishTheftDetected')
            ->with($session->getId(), $user->getId(), $session->getIpAddress(), $reason);
    }

    private function createRevokedToken(
        string $sessionId
    ): AuthRefreshToken {
        $token = new AuthRefreshToken(
            (string) new Ulid(),
            $sessionId,
            'already-revoked-token',
            new DateTimeImmutable('+1 month')
        );
        $token->revoke();
        return $token;
    }

    private function createActiveToken(
        string $sessionId
    ): AuthRefreshToken {
        return new AuthRefreshToken(
            (string) new Ulid(),
            $sessionId,
            'active-token',
            new DateTimeImmutable('+1 month')
        );
    }
}
