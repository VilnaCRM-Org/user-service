<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\DTO\RefreshTokenCommandResponse;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\User;
use DateInterval;
use DateTimeImmutable;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class AuthTokenFactory implements AuthTokenFactoryInterface
{
    private DateInterval $refreshTokenTtl;

    public function __construct(
        private UuidFactory $uuidFactory,
        private UlidFactory $ulidFactory,
        string $refreshTokenTtlSpec = 'P1M',
        private int $accessTokenTtlSeconds = 900,
        private string $jwtIssuer = 'vilnacrm-user-service',
        private string $jwtAudience = 'vilnacrm-api',
    ) {
        $this->refreshTokenTtl = new DateInterval($refreshTokenTtlSpec);
    }

    /**
     * @return array<int|string|array<string>>
     *
     * @psalm-return array{sub: string, iss: non-empty-string, aud: non-empty-string, exp: int, iat: int, nbf: int, jti: string, sid: string, roles: list{'ROLE_USER'}}
     */
    #[\Override]
    public function buildJwtPayload(
        User $user,
        string $sessionId,
        DateTimeImmutable $issuedAt
    ): array {
        $issuedAtTimestamp = $issuedAt->getTimestamp();

        return [
            'sub' => $user->getId(),
            'iss' => $this->jwtIssuer,
            'aud' => $this->jwtAudience,
            'exp' => $issuedAtTimestamp + $this->accessTokenTtlSeconds,
            'iat' => $issuedAtTimestamp,
            'nbf' => $issuedAtTimestamp,
            'jti' => (string) $this->uuidFactory->create(),
            'sid' => $sessionId,
            'roles' => ['ROLE_USER'],
        ];
    }

    #[\Override]
    public function generateOpaqueToken(): string
    {
        return rtrim(
            strtr(base64_encode(random_bytes(32)), '+/', '-_'),
            '='
        );
    }

    #[\Override]
    public function createRefreshToken(
        string $sessionId,
        string $plainToken,
        DateTimeImmutable $issuedAt
    ): AuthRefreshToken {
        return new AuthRefreshToken(
            (string) $this->ulidFactory->create(),
            $sessionId,
            $plainToken,
            $issuedAt->add($this->refreshTokenTtl)
        );
    }

    #[\Override]
    public function createRefreshTokenResponse(
        string $accessToken,
        string $refreshToken
    ): RefreshTokenCommandResponse {
        return new RefreshTokenCommandResponse($accessToken, $refreshToken);
    }
}
