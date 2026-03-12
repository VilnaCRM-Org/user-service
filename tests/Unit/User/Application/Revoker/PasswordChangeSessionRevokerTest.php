<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Revoker;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Processor\Revoker\PasswordChangeSessionRevoker;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @psalm-suppress UnusedVariable
 * @psalm-suppress NoValue
 * @psalm-suppress UnevaluatedCode
 * @psalm-suppress UnusedMethod
 */
final class PasswordChangeSessionRevokerTest extends UnitTestCase
{
    private AuthSessionRepositoryInterface&MockObject $sessionRepo;
    private AuthRefreshTokenRepositoryInterface&MockObject $refreshRepo;
    private PasswordChangeSessionRevoker $revoker;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->sessionRepo = $this->createMock(
            AuthSessionRepositoryInterface::class
        );
        $this->refreshRepo = $this->createMock(
            AuthRefreshTokenRepositoryInterface::class
        );

        $this->revoker = new PasswordChangeSessionRevoker(
            $this->sessionRepo,
            $this->refreshRepo,
        );
    }

    public function testSkipsCurrentSession(): void
    {
        $userId = $this->faker->uuid();
        $currentId = $this->faker->uuid();
        $current = $this->sessionMock($currentId, false);

        $this->sessionRepo->method('findByUserId')
            ->with($userId)->willReturn([$current]);

        $this->sessionRepo->expects($this->never())
            ->method('save');

        $count = $this->revoker->revokeOtherSessions(
            $userId,
            $currentId
        );
        $this->assertSame(0, $count);
    }

    public function testSkipsAlreadyRevokedSessions(): void
    {
        $userId = $this->faker->uuid();
        $revoked = $this->sessionMock($this->faker->uuid(), true);

        $this->sessionRepo->method('findByUserId')
            ->willReturn([$revoked]);

        $this->sessionRepo->expects($this->never())
            ->method('save');

        $count = $this->revoker->revokeOtherSessions(
            $userId,
            $this->faker->uuid()
        );
        $this->assertSame(0, $count);
    }

    public function testRevokesActiveOtherSessions(): void
    {
        $userId = $this->faker->uuid();
        $otherId = $this->faker->uuid();
        $other = $this->activeSessionMock($otherId);

        $this->sessionRepo->method('findByUserId')
            ->willReturn([$other]);
        $this->sessionRepo->expects($this->once())
            ->method('save')->with($other);
        $this->refreshRepo->method('findBySessionId')
            ->with($otherId)->willReturn([]);

        $count = $this->revoker->revokeOtherSessions(
            $userId,
            $this->faker->uuid()
        );
        $this->assertSame(1, $count);
    }

    public function testRevokesRefreshTokensForSession(): void
    {
        $otherId = $this->faker->uuid();
        $other = $this->activeSessionMock($otherId);

        $activeToken = $this->refreshTokenMock(false);
        $revokedToken = $this->refreshTokenMock(true);
        $this->arrangeRefreshTokenRevoke(
            $other,
            [$activeToken, $revokedToken],
            $otherId
        );

        $this->revoker->revokeOtherSessions(
            $this->faker->uuid(),
            $this->faker->uuid()
        );
    }

    public function testReturnsCorrectCountForMultipleSessions(): void
    {
        $currentId = $this->faker->uuid();
        $s1 = $this->activeSessionMock($this->faker->uuid());
        $s2 = $this->activeSessionMock($this->faker->uuid());
        $revoked = $this->sessionMock($this->faker->uuid(), true);
        $current = $this->sessionMock($currentId, false);

        $this->sessionRepo->method('findByUserId')
            ->willReturn([$s1, $s2, $revoked, $current]);
        $this->refreshRepo->method('findBySessionId')
            ->willReturn([]);

        $count = $this->revoker->revokeOtherSessions(
            $this->faker->uuid(),
            $currentId
        );
        $this->assertSame(2, $count);
    }

    public function testReturnsZeroWhenNoSessions(): void
    {
        $this->sessionRepo->method('findByUserId')
            ->willReturn([]);

        $count = $this->revoker->revokeOtherSessions(
            $this->faker->uuid(),
            $this->faker->uuid()
        );
        $this->assertSame(0, $count);
    }

    private function sessionMock(
        string $id,
        bool $isRevoked
    ): AuthSession&MockObject {
        $session = $this->createMock(AuthSession::class);
        $session->method('getId')->willReturn($id);
        $session->method('isRevoked')->willReturn($isRevoked);

        return $session;
    }

    private function activeSessionMock(
        string $id
    ): AuthSession&MockObject {
        $s = $this->createMock(AuthSession::class);
        $s->method('getId')->willReturn($id);
        $s->method('isRevoked')->willReturn(false);

        return $s;
    }

    private function refreshTokenMock(
        bool $isRevoked
    ): AuthRefreshToken&MockObject {
        $t = $this->createMock(AuthRefreshToken::class);
        $t->method('isRevoked')->willReturn($isRevoked);
        if (!$isRevoked) {
            $t->expects($this->once())->method('revoke');
        } else {
            $t->expects($this->never())->method('revoke');
        }

        return $t;
    }

    /**
     * @param array<AuthRefreshToken&MockObject> $tokens
     */
    private function arrangeRefreshTokenRevoke(
        AuthSession&MockObject $session,
        array $tokens,
        string $sessionId
    ): void {
        $this->sessionRepo->method('findByUserId')
            ->willReturn([$session]);
        $this->refreshRepo->method('findBySessionId')
            ->with($sessionId)->willReturn($tokens);
    }
}
