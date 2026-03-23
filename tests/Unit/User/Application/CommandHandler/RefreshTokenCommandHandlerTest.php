<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\User\Application\Command\RefreshTokenCommand;
use App\User\Domain\Entity\AuthRefreshToken;
use DateTimeImmutable;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Uid\Ulid;

final class RefreshTokenCommandHandlerTest extends RefreshTokenCommandHandlerTestCase
{
    public function testInvokeRotatesTokenAndIssuesNewTokens(): void
    {
        $plainToken = 'valid-refresh-token-value';
        $oldToken = $this->createValidRefreshToken($plainToken);
        $session = $this->createValidSession($oldToken->getSessionId());
        $user = $this->createUser();
        $this->expectTokenLookup($oldToken, $plainToken);
        $this->expectSessionLookup($session);
        $this->expectUserLookup($user);
        $capturedPayload = [];
        $this->expectSuccessfulRotation($session, $user, 'new-access-token', $capturedPayload);
        $command = $this->invokeHandler($plainToken);
        $response = $command->getResponse();
        $this->assertSame('new-access-token', $response->getAccessToken());
        $expectedRefresh = 'test-opaque-token-1234567890-abcdefghijklmn';
        $this->assertSame($expectedRefresh, $response->getRefreshToken());
        $this->assertOpaqueTokenFormat($response->getRefreshToken());
        $this->assertTrue($oldToken->isRotated());
        $this->assertJwtPayloadContents($capturedPayload, $user->getId(), $session->getId());
    }

    public function testInvokeThrows401WhenTokenNotFound(): void
    {
        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->willReturn(null);

        $this->authSessionRepository
            ->expects($this->never())
            ->method('findById');

        $this->userRepository
            ->expects($this->never())
            ->method('findById');

        $this->expectException(
            UnauthorizedHttpException::class
        );
        $this->expectExceptionMessage(
            'Invalid refresh token.'
        );

        $handler = $this->createHandler();
        $handler->__invoke(
            new RefreshTokenCommand('invalid-token')
        );
    }

    public function testInvokeThrows401WhenTokenIsExpired(): void
    {
        $plainToken = 'expired-token';
        $token = $this->createExpiredRefreshToken($plainToken);
        $this->expectTokenLookup($token, $plainToken);
        $this->expectNeverSessionOrUserLookup();
        $this->expectInvalidTokenException();
        $this->createHandler()->__invoke(
            new RefreshTokenCommand($plainToken)
        );
    }

    public function testInvokeThrows401WhenTokenIsRevoked(): void
    {
        $plainToken = 'revoked-token';
        $token = $this->createValidRefreshToken($plainToken);
        $token->revoke();
        $this->expectTokenLookup($token, $plainToken);
        $this->expectNeverSessionOrUserLookup();
        $this->expectInvalidTokenException();
        $this->createHandler()->__invoke(
            new RefreshTokenCommand($plainToken)
        );
    }

    public function testGraceReuseSucceedsWithinWindow(): void
    {
        $plainToken = 'rotated-token';
        $token = $this->createValidRefreshToken($plainToken);
        $token->markAsRotated(new DateTimeImmutable('-30 seconds'));
        $session = $this->createValidSession($token->getSessionId());
        $user = $this->createUser();
        $this->expectTokenLookup($token, $plainToken);
        $this->expectSessionLookup($session);
        $this->expectUserLookup($user);
        $this->expectSuccessfulGraceReuse($session, $user, 'grace-access-token');
        $command = $this->invokeHandler($plainToken);
        $this->assertSame('grace-access-token', $command->getResponse()->getAccessToken());
        $this->assertOpaqueTokenFormat($command->getResponse()->getRefreshToken());
        $this->assertTrue($token->isGraceUsed());
    }

    public function testConcurrentRotationUsesLatestRotatedTokenWithinGraceWindow(): void
    {
        $plainToken = 'concurrent-rotated-token';
        $oldToken = $this->createValidRefreshToken($plainToken);
        $latestToken = $this->createValidRefreshToken($plainToken);
        $latestToken->markAsRotated(new DateTimeImmutable('-30 seconds'));
        $session = $this->createValidSession($oldToken->getSessionId());
        $user = $this->createUser();
        $this->expectConsecutiveTokenLookups($plainToken, $oldToken, $latestToken);
        $this->expectFailedAtomicRotation();
        $this->expectSessionLookup($session);
        $this->expectUserLookup($user);
        $this->expectSuccessfulGraceReuse($session, $user, 'concurrent-access-token');
        $command = $this->invokeHandler($plainToken);
        $this->assertSame('concurrent-access-token', $command->getResponse()->getAccessToken());
        $this->assertOpaqueTokenFormat($command->getResponse()->getRefreshToken());
        $this->assertTrue($latestToken->isGraceUsed());
    }

    public function testConcurrentRotationThrowsWhenLatestTokenIsNotRotated(): void
    {
        $plainToken = 'concurrent-not-rotated-token';
        $oldToken = $this->createValidRefreshToken($plainToken);
        $latestToken = $this->createValidRefreshToken($plainToken);
        $session = $this->createValidSession($oldToken->getSessionId());
        $user = $this->createUser();
        $this->expectConsecutiveTokenLookups($plainToken, $oldToken, $latestToken);
        $this->expectFailedAtomicRotation();
        $this->expectSessionLookup($session);
        $this->expectUserLookup($user);
        $this->refreshTokenRepository->expects($this->never())->method('save');
        $this->publisher->expects($this->never())->method('publishTokenRotated');
        $this->publisher->expects($this->never())->method('publishTheftDetected');
        $this->expectInvalidTokenException();
        $this->createHandler()->__invoke(new RefreshTokenCommand($plainToken));
    }

    public function testGraceReuseIgnoresTokenWithSameIdEvenWhenItHasLaterRotation(): void
    {
        $plainToken = 'rotated-token-with-duplicate-id';
        $oldToken = $this->createValidRefreshToken($plainToken);
        $oldToken->markAsRotated(new DateTimeImmutable('-20 seconds'));

        $duplicateIdToken = new AuthRefreshToken(
            $oldToken->getId(),
            $oldToken->getSessionId(),
            $this->faker->sha256(),
            new DateTimeImmutable('+1 month')
        );
        $duplicateIdToken->markAsRotated(new DateTimeImmutable('-10 seconds'));

        $this->assertGraceReuseWithCandidateTokens(
            $oldToken,
            $plainToken,
            [$duplicateIdToken],
            'duplicate-id-access-token'
        );
    }

    public function testGraceReuseDoesNotTreatEqualRotationTimeAsLaterRotation(): void
    {
        $plainToken = 'rotated-token-equal-rotation-time';
        $oldToken = $this->createValidRefreshToken($plainToken);
        $rotatedAt = new DateTimeImmutable('-20 seconds');
        $oldToken->markAsRotated($rotatedAt);

        $sameTimeToken = $this->createValidRefreshToken($this->faker->sha256());
        $sameTimeToken->markAsRotated($rotatedAt);

        $this->assertGraceReuseWithCandidateTokens(
            $oldToken,
            $plainToken,
            [$sameTimeToken],
            'equal-time-access-token'
        );
    }

    public function testGraceReuseIgnoresUnrotatedSessionToken(): void
    {
        $plainToken = 'rotated-token-with-unrotated-candidate';
        $oldToken = $this->createValidRefreshToken($plainToken);
        $oldToken->markAsRotated(new DateTimeImmutable('-20 seconds'));
        $unrotatedToken = $this->createValidRefreshToken($this->faker->sha256());

        $this->assertGraceReuseWithCandidateTokens(
            $oldToken,
            $plainToken,
            [$unrotatedToken],
            'unrotated-candidate-access-token'
        );
    }

    public function testInvokeThrows401WhenSessionNotFound(): void
    {
        $plainToken = 'valid-token';
        $token = $this->createValidRefreshToken($plainToken);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByTokenHash')
            ->willReturn($token);

        $this->authSessionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $this->expectException(
            UnauthorizedHttpException::class
        );
        $this->expectExceptionMessage(
            'Invalid refresh token.'
        );

        $handler = $this->createHandler();
        $handler->__invoke(
            new RefreshTokenCommand($plainToken)
        );
    }

    public function testInvokeThrows401WhenSessionIsRevoked(): void
    {
        $plainToken = 'valid-token-revoked-session';
        $token = $this->createValidRefreshToken($plainToken);
        $session = $this->createValidSession(
            $token->getSessionId()
        );
        $session->revoke();
        $this->expectTokenLookup($token, $plainToken);
        $this->expectSessionLookup($session);
        $this->expectNeverUserLookup();
        $this->expectInvalidTokenException();
        $this->createHandler()->__invoke(
            new RefreshTokenCommand($plainToken)
        );
    }

    public function testInvokeThrows401WhenSessionIsExpired(): void
    {
        $plainToken = 'valid-token-expired-session';
        $token = $this->createValidRefreshToken($plainToken);
        $session = $this->createExpiredSession(
            $token->getSessionId()
        );
        $this->expectTokenLookup($token, $plainToken);
        $this->expectSessionLookup($session);
        $this->expectNeverUserLookup();
        $this->expectInvalidTokenException();
        $this->createHandler()->__invoke(
            new RefreshTokenCommand($plainToken)
        );
    }

    public function testInvokeThrows401WhenUserNotFound(): void
    {
        $plainToken = 'valid-token-user-missing';
        $token = $this->createValidRefreshToken($plainToken);
        $session = $this->createValidSession(
            $token->getSessionId()
        );
        $this->expectTokenLookup($token, $plainToken);
        $this->expectSessionLookup($session);
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);
        $this->expectInvalidTokenException();
        $this->createHandler()->__invoke(
            new RefreshTokenCommand($plainToken)
        );
    }

    /**
     * @param list<AuthRefreshToken> $candidateTokens
     */
    private function assertGraceReuseWithCandidateTokens(
        AuthRefreshToken $oldToken,
        string $plainToken,
        array $candidateTokens,
        string $expectedAccessToken
    ): void {
        $session = $this->createValidSession($oldToken->getSessionId());
        $user = $this->createUser();

        $this->expectTokenLookup($oldToken, $plainToken);
        $this->expectSessionLookup($session);
        $this->expectUserLookup($user);
        $this->refreshTokenRepository->expects($this->once())
            ->method('findBySessionId')
            ->with($session->getId())
            ->willReturn($candidateTokens);
        $this->expectSuccessfulGraceReuse($session, $user, $expectedAccessToken);

        $command = $this->invokeHandler($plainToken);

        $this->assertSame($expectedAccessToken, $command->getResponse()->getAccessToken());
        $this->assertOpaqueTokenFormat($command->getResponse()->getRefreshToken());
        $this->assertTrue($oldToken->isGraceUsed());
    }

    private function createExpiredRefreshToken(
        string $plainToken
    ): AuthRefreshToken {
        return new AuthRefreshToken(
            (string) new Ulid(),
            (string) new Ulid(),
            $plainToken,
            new DateTimeImmutable('-1 hour')
        );
    }
}
