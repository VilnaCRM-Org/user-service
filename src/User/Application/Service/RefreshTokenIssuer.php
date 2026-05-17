<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\User\Application\DTO\RefreshTokenCommandResponse;
use App\User\Application\Factory\AccessTokenFactoryInterface;
use App\User\Application\Factory\AuthTokenFactoryInterface;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Infrastructure\Publisher\RefreshTokenPublisherInterface;
use DateTimeImmutable;

final readonly class RefreshTokenIssuer
{
    public function __construct(
        private AuthRefreshTokenRepositoryInterface $refreshTokenRepository,
        private AccessTokenFactoryInterface $accessTokenFactory,
        private AuthTokenFactoryInterface $authTokenFactory,
        private RefreshTokenPublisherInterface $publisher,
    ) {
    }

    public function issueRotatedTokens(
        User $user,
        AuthSession $session
    ): RefreshTokenCommandResponse {
        $issuedAt = new DateTimeImmutable();

        $newRefreshPlain = $this->authTokenFactory->generateOpaqueToken();
        $this->refreshTokenRepository->save(
            $this->createRefreshToken($session, $newRefreshPlain, $issuedAt)
        );

        $accessToken = $this->accessTokenFactory->create(
            $this->authTokenFactory->buildJwtPayload(
                $user,
                $session->getId(),
                $issuedAt
            )
        );

        $this->publisher->publishTokenRotated(
            $session->getId(),
            $user->getId()
        );

        return new RefreshTokenCommandResponse($accessToken, $newRefreshPlain);
    }

    private function createRefreshToken(
        AuthSession $session,
        string $plainToken,
        DateTimeImmutable $issuedAt
    ): AuthRefreshToken {
        return $this->authTokenFactory->createRefreshToken(
            $session->getId(),
            $plainToken,
            $issuedAt
        );
    }
}
