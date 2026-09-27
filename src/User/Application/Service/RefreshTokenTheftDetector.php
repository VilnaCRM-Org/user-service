<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Infrastructure\Publisher\RefreshTokenPublisherInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final readonly class RefreshTokenTheftDetector
{
    public function __construct(
        private AuthRefreshTokenRepositoryInterface $refreshTokenRepository,
        private AuthSessionRepositoryInterface $authSessionRepository,
        private RefreshTokenPublisherInterface $publisher,
    ) {
    }

    /**
     * @param list<AuthRefreshToken>|null $tokens
     */
    public function respondToTheft(
        AuthRefreshToken $oldToken,
        AuthSession $session,
        User $user,
        string $reason,
        ?array $tokens = null
    ): never {
        $this->revokeSessionAndTokens($oldToken, $session, $tokens);
        try {
            $this->publisher->publishTheftDetected(
                $session->getId(),
                $user->getId(),
                $session->getIpAddress(),
                $reason
            );
        } finally {
            $this->throwUnauthorized();
        }
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

    private function throwUnauthorized(): never
    {
        throw new UnauthorizedHttpException(
            'Bearer',
            'Invalid refresh token.'
        );
    }
}
