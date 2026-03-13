<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\DTO\IssuedSession;
use App\User\Application\Factory\Generator\AccessTokenGeneratorInterface;
use App\User\Application\Factory\Generator\IdGeneratorInterface;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\AuthSessionFactoryInterface;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use DateTimeImmutable;

/**
 * @psalm-api
 */
final readonly class SessionIssuer implements SessionIssuerInterface
{
    public function __construct(
        private AuthSessionRepositoryInterface $authSessionRepository,
        private AuthRefreshTokenRepositoryInterface $authRefreshTokenRepository,
        private AccessTokenGeneratorInterface $accessTokenGenerator,
        private AuthTokenFactoryInterface $authTokenFactory,
        private AuthSessionFactoryInterface $authSessionFactory,
        private IdGeneratorInterface $idGenerator,
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

        return $this->authSessionFactory->create(
            $this->idGenerator->generate(),
            $user->getId(),
            $ipAddress,
            $userAgent,
            $issuedAt,
            $issuedAt->modify(sprintf('+%d seconds', $ttlSeconds)),
            $rememberMe
        );
    }
}
