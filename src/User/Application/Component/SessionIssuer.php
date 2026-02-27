<?php

declare(strict_types=1);

namespace App\User\Application\Component;

use App\User\Application\DTO\IssuedSession;
use App\User\Application\Factory\AuthTokenFactoryInterface;
use App\User\Domain\Contract\AccessTokenGeneratorInterface;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\Uid\Factory\UlidFactory;

final readonly class SessionIssuer implements SessionIssuerInterface
{
    public function __construct(
        private AuthSessionRepositoryInterface $authSessionRepository,
        private AuthRefreshTokenRepositoryInterface $authRefreshTokenRepository,
        private AccessTokenGeneratorInterface $accessTokenGenerator,
        private AuthTokenFactoryInterface $authTokenFactory,
        private UlidFactory $ulidFactory,
        private int $standardSessionTtlSeconds = 900,
        private int $rememberMeSessionTtlSeconds = 2592000,
    ) {
    }

    #[\Override]
    public function issue(
        User $user,
        string $ipAddress,
        string $userAgent,
        bool $rememberMe,
        DateTimeImmutable $issuedAt
    ): IssuedSession {
        $session = $this->createSession($user, $ipAddress, $userAgent, $rememberMe, $issuedAt);
        $this->authSessionRepository->save($session);

        $refreshToken = $this->authTokenFactory->generateOpaqueToken();
        $this->authRefreshTokenRepository->save(
            $this->authTokenFactory->createRefreshToken(
                $session->getId(),
                $refreshToken,
                $issuedAt
            )
        );

        $accessToken = $this->accessTokenGenerator->generate(
            $this->authTokenFactory->buildJwtPayload($user, $session->getId(), $issuedAt)
        );

        return new IssuedSession($session->getId(), $accessToken, $refreshToken);
    }

    private function createSession(
        User $user,
        string $ipAddress,
        string $userAgent,
        bool $rememberMe,
        DateTimeImmutable $issuedAt
    ): AuthSession {
        $ttlSeconds = $rememberMe
            ? $this->rememberMeSessionTtlSeconds
            : $this->standardSessionTtlSeconds;

        return new AuthSession(
            (string) $this->ulidFactory->create(),
            $user->getId(),
            $ipAddress,
            $userAgent,
            $issuedAt,
            $issuedAt->modify(sprintf('+%d seconds', $ttlSeconds)),
            $rememberMe
        );
    }
}
