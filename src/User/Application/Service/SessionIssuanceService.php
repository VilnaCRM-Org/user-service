<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\User\Application\Factory\AuthTokenFactoryInterface;
use App\User\Domain\Contract\AccessTokenGeneratorInterface;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\Uid\Factory\UlidFactory;

/**
 * @psalm-api
 */
final readonly class SessionIssuanceService implements
    SessionIssuanceServiceInterface
{
    private const STANDARD_SESSION_TTL_SECONDS = 900;
    private const REMEMBER_ME_SESSION_TTL_SECONDS = 2592000;

    public function __construct(
        private AuthSessionRepositoryInterface $authSessionRepository,
        private AuthRefreshTokenRepositoryInterface $authRefreshTokenRepository,
        private AccessTokenGeneratorInterface $accessTokenGenerator,
        private AuthTokenFactoryInterface $authTokenFactory,
        private UlidFactory $ulidFactory,
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
        $session = $this->createSession(
            $user,
            $ipAddress,
            $userAgent,
            $rememberMe,
            $issuedAt
        );
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
            ? self::REMEMBER_ME_SESSION_TTL_SECONDS
            : self::STANDARD_SESSION_TTL_SECONDS;

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
