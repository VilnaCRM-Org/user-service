<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\RefreshTokenCommand;
use App\User\Application\Factory\AccessTokenFactoryInterface;
use App\User\Application\Factory\AuthTokenFactoryInterface;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Publisher\RefreshTokenPublisherInterface;
use DateTimeImmutable;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final readonly class RefreshTokenCommandHandler implements
    CommandHandlerInterface
{
    private const DEFAULT_GRACE_WINDOW_SECONDS = 60;

    public function __construct(
        private AuthRefreshTokenRepositoryInterface $refreshTokenRepository,
        private AuthSessionRepositoryInterface $authSessionRepository,
        private UserRepositoryInterface $userRepository,
        private AccessTokenFactoryInterface $accessTokenFactory,
        private AuthTokenFactoryInterface $authTokenFactory,
        private RefreshTokenPublisherInterface $publisher,
        private int $refreshTokenGraceWindowSeconds = self::DEFAULT_GRACE_WINDOW_SECONDS,
    ) {
    }

    public function __invoke(RefreshTokenCommand $command): void
    {
        $oldToken = $this->resolveRefreshToken($command->refreshToken);
        $session = $this->resolveSession($oldToken->getSessionId());
        $user = $this->resolveUser($session->getUserId());
        $currentTime = new DateTimeImmutable();

        if ($this->handleIfAlreadyRotated($oldToken, $session, $user, $command, $currentTime)) {
            return;
        }

        $this->rotateActiveToken($oldToken, $user, $session, $command, $currentTime);
    }

    private function resolveRefreshToken(
        string $plainToken
    ): AuthRefreshToken {
        $hash = hash('sha256', $plainToken);
        $token = $this->refreshTokenRepository->findByTokenHash($hash);

        if (!$token instanceof AuthRefreshToken) {
            $this->throwUnauthorized();
        }

        if ($token->isExpired() || $token->isRevoked()) {
            $this->throwUnauthorized();
        }

        return $token;
    }

    private function handleIfAlreadyRotated(
        AuthRefreshToken $oldToken,
        AuthSession $session,
        User $user,
        RefreshTokenCommand $command,
        DateTimeImmutable $currentTime
    ): bool {
        if ($this->tryHandleRotatedToken($oldToken, $session, $user, $command, $currentTime)) {
            return true;
        }

        return $this->tryHandleConcurrentRotation(
            $oldToken->getTokenHash(),
            $session,
            $user,
            $command,
            $currentTime,
            $command->refreshToken
        );
    }

    private function handleRotatedToken(
        AuthRefreshToken $oldToken,
        AuthSession $session,
        User $user,
        RefreshTokenCommand $command,
        DateTimeImmutable $currentTime
    ): void {
        if (!$oldToken->isWithinGracePeriod(
            $currentTime,
            $this->refreshTokenGraceWindowSeconds
        )) {
            $this->handleTheftDetection($oldToken, $session, $user, 'grace_period_expired');
        }

        $tokens = $this->refreshTokenRepository->findBySessionId($session->getId());
        $this->handleGraceWindowReuse(
            $oldToken,
            $session,
            $user,
            $command,
            $currentTime,
            $tokens
        );
    }

    /**
     * @param list<AuthRefreshToken> $tokens
     */
    private function hasLaterRotation(
        AuthRefreshToken $oldToken,
        array $tokens
    ): bool {
        $oldRotatedAt = $oldToken->getRotatedAt();
        assert($oldRotatedAt instanceof DateTimeImmutable);

        foreach ($tokens as $sessionToken) {
            if ($this->isLaterRotatedToken(
                $sessionToken,
                $oldToken,
                $oldRotatedAt
            )) {
                return true;
            }
        }

        return false;
    }

    private function isLaterRotatedToken(
        AuthRefreshToken $candidateToken,
        AuthRefreshToken $oldToken,
        DateTimeImmutable $oldRotatedAt
    ): bool {
        if ($candidateToken->getId() === $oldToken->getId()) {
            return false;
        }

        if ($candidateToken->isRevoked()) {
            return false;
        }

        $candidateRotatedAt = $candidateToken->getRotatedAt();

        return $candidateRotatedAt instanceof DateTimeImmutable
            && $candidateRotatedAt > $oldRotatedAt;
    }

    /**
     * @param list<AuthRefreshToken> $tokens
     */
    private function handleGraceWindowReuse(
        AuthRefreshToken $oldToken,
        AuthSession $session,
        User $user,
        RefreshTokenCommand $command,
        DateTimeImmutable $currentTime,
        array $tokens
    ): void {
        if ($this->hasLaterRotation($oldToken, $tokens)) {
            $this->handleTheftDetection(
                $oldToken,
                $session,
                $user,
                'superseded_rotation',
                $tokens
            );
        }

        $this->assertGraceWindowIsEligible($oldToken, $session, $user, $currentTime, $tokens);
        $oldToken->markGraceUsed();
        $this->setResponseWithNewTokens($command, $user, $session);
        $this->publishRotatedEvent($session, $user);
    }

    /**
     * @param list<AuthRefreshToken>|null $tokens
     */
    private function handleTheftDetection(
        AuthRefreshToken $oldToken,
        AuthSession $session,
        User $user,
        string $reason,
        ?array $tokens = null
    ): never {
        $this->revokeSessionAndTokens($oldToken, $session, $tokens);
        $this->publisher->publishTheftDetected(
            $session->getId(),
            $user->getId(),
            $session->getIpAddress(),
            $reason
        );

        $this->throwUnauthorized();
    }

    /**
     * @param list<AuthRefreshToken>|null $tokens
     */
    private function revokeSessionAndTokens(
        AuthRefreshToken $oldToken,
        AuthSession $session,
        ?array $tokens = null
    ): void {
        if (!$session->isRevoked()) {
            $session->revoke();
            $this->authSessionRepository->save($session);
        }

        $this->revokeRefreshTokens(
            $this->resolveTokensForRevocation(
                $oldToken,
                $session,
                $tokens
            )
        );
    }

    /**
     * @param list<AuthRefreshToken>|null $tokens
     *
     * @return list<AuthRefreshToken>
     */
    private function resolveTokensForRevocation(
        AuthRefreshToken $oldToken,
        AuthSession $session,
        ?array $tokens
    ): array {
        if ($tokens === null) {
            $tokens = $this->refreshTokenRepository->findBySessionId(
                $session->getId()
            );
        }

        if ($tokens !== []) {
            return $tokens;
        }

        return [$oldToken];
    }

    /**
     * @param list<AuthRefreshToken> $tokens
     */
    private function revokeRefreshTokens(array $tokens): void
    {
        foreach ($tokens as $token) {
            if ($token->isRevoked()) {
                continue;
            }

            $token->revoke();
            $this->refreshTokenRepository->save($token);
        }
    }

    private function resolveSession(string $sessionId): AuthSession
    {
        $session = $this->authSessionRepository->findById($sessionId);
        if (!$session instanceof AuthSession) {
            $this->throwUnauthorized();
        }
        if ($session->isRevoked() || $session->isExpired()) {
            $this->throwUnauthorized();
        }

        return $session;
    }

    private function tryHandleRotatedToken(
        AuthRefreshToken $oldToken,
        AuthSession $session,
        User $user,
        RefreshTokenCommand $command,
        DateTimeImmutable $currentTime
    ): bool {
        if (!$oldToken->isRotated()) {
            return false;
        }

        $this->handleRotatedToken($oldToken, $session, $user, $command, $currentTime);

        return true;
    }

    private function tryHandleConcurrentRotation(
        string $tokenHash,
        AuthSession $session,
        User $user,
        RefreshTokenCommand $command,
        DateTimeImmutable $currentTime,
        string $plainToken
    ): bool {
        $markedSuccessfully = $this->refreshTokenRepository->markAsRotatedIfActive(
            $tokenHash,
            $currentTime
        );
        if ($markedSuccessfully) {
            return false;
        }

        $latestToken = $this->resolveRefreshToken($plainToken);
        if (!$latestToken->isRotated()) {
            $this->throwUnauthorized();
        }

        $this->handleRotatedToken(
            $latestToken,
            $session,
            $user,
            $command,
            $currentTime
        );

        return true;
    }

    private function rotateActiveToken(
        AuthRefreshToken $oldToken,
        User $user,
        AuthSession $session,
        RefreshTokenCommand $command,
        DateTimeImmutable $currentTime
    ): void {
        $oldToken->markAsRotated($currentTime);
        $this->setResponseWithNewTokens($command, $user, $session);
        $this->publishRotatedEvent($session, $user);
    }

    private function setResponseWithNewTokens(
        RefreshTokenCommand $command,
        User $user,
        AuthSession $session
    ): void {
        $issuedAt = new DateTimeImmutable();

        $newRefreshPlain = $this->authTokenFactory->generateOpaqueToken();
        $this->refreshTokenRepository->save(
            $this->authTokenFactory->createRefreshToken(
                $session->getId(),
                $newRefreshPlain,
                $issuedAt
            )
        );

        $accessToken = $this->accessTokenFactory->create(
            $this->authTokenFactory->buildJwtPayload($user, $session->getId(), $issuedAt)
        );

        $command->setTokens($accessToken, $newRefreshPlain);
    }

    /**
     * @param list<AuthRefreshToken> $tokens
     */
    private function assertGraceWindowIsEligible(
        AuthRefreshToken $oldToken,
        AuthSession $session,
        User $user,
        DateTimeImmutable $currentTime,
        array $tokens
    ): void {
        if ($this->consumeGraceWindow($oldToken, $currentTime)) {
            return;
        }

        $this->handleTheftDetection(
            $oldToken,
            $session,
            $user,
            'double_grace_use',
            $tokens
        );
    }

    private function consumeGraceWindow(
        AuthRefreshToken $oldToken,
        DateTimeImmutable $currentTime
    ): bool {
        if ($oldToken->isGraceUsed()) {
            return false;
        }

        $graceWindowStartedAt = $currentTime->setTimestamp(
            $currentTime->getTimestamp() - $this->refreshTokenGraceWindowSeconds
        );

        return $this->refreshTokenRepository->markGraceUsedIfEligible(
            $oldToken->getTokenHash(),
            $graceWindowStartedAt,
            $currentTime
        );
    }

    private function resolveUser(string $userId): User
    {
        $user = $this->userRepository->findById($userId);
        if (!$user instanceof User) {
            $this->throwUnauthorized();
        }

        return $user;
    }

    private function publishRotatedEvent(
        AuthSession $session,
        User $user
    ): void {
        $this->publisher->publishTokenRotated(
            $session->getId(),
            $user->getId()
        );
    }

    private function throwUnauthorized(): never
    {
        throw new UnauthorizedHttpException(
            'Bearer',
            'Invalid refresh token.'
        );
    }
}
