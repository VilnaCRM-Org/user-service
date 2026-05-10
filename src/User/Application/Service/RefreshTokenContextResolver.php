<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final readonly class RefreshTokenContextResolver
{
    public function __construct(
        private AuthRefreshTokenRepositoryInterface $refreshTokenRepository,
        private AuthSessionRepositoryInterface $authSessionRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @return array{AuthRefreshToken, AuthSession, User}
     */
    public function resolve(string $plainToken): array
    {
        $refreshToken = $this->resolveRefreshToken($plainToken);
        $session = $this->resolveSession($refreshToken->getSessionId());

        return [
            $refreshToken,
            $session,
            $this->resolveUser($session->getUserId()),
        ];
    }

    public function resolveRotatedRefreshToken(
        string $plainToken
    ): AuthRefreshToken {
        $refreshToken = $this->resolveRefreshToken($plainToken);

        if (!$refreshToken->isRotated()) {
            $this->throwUnauthorized();
        }

        return $refreshToken;
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

    private function resolveUser(string $userId): User
    {
        $user = $this->userRepository->findById($userId);
        if (!$user instanceof User) {
            $this->throwUnauthorized();
        }

        return $user;
    }

    private function throwUnauthorized(): never
    {
        throw new UnauthorizedHttpException(
            'Bearer',
            'Invalid refresh token.'
        );
    }
}
