<?php

declare(strict_types=1);

namespace App\Tests\Shared\Auth\Factory;

use App\User\Domain\Contract\AccessTokenGeneratorInterface;
use DateTimeImmutable;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class TestAccessTokenFactory
{
    private const ACCESS_TOKEN_TTL_SECONDS = 900;
    private const JWT_ISSUER = 'vilnacrm-user-service';
    private const JWT_AUDIENCE = 'vilnacrm-api';

    public function __construct(
        private AccessTokenGeneratorInterface $accessTokenGenerator,
        private UuidFactory $uuidFactory,
        private UlidFactory $ulidFactory,
    ) {
    }

    public function createUserToken(
        string $userId,
        ?string $sessionId = null
    ): string {
        return $this->createToken($userId, ['ROLE_USER'], $sessionId);
    }

    public function createServiceToken(
        string $subject = 'test-service',
        ?string $sessionId = null
    ): string {
        return $this->createToken($subject, ['ROLE_SERVICE'], $sessionId);
    }

    /**
     * @param list<string> $roles
     */
    public function createToken(
        string $subject,
        array $roles,
        ?string $sessionId = null,
        ?DateTimeImmutable $issuedAt = null
    ): string {
        $issuedAt ??= new DateTimeImmutable();
        $sessionId ??= (string) $this->ulidFactory->create();

        $timestamp = $issuedAt->getTimestamp();

        return $this->accessTokenGenerator->generate([
            'sub' => $subject,
            'iss' => self::JWT_ISSUER,
            'aud' => self::JWT_AUDIENCE,
            'exp' => $timestamp + self::ACCESS_TOKEN_TTL_SECONDS,
            'iat' => $timestamp,
            'nbf' => $timestamp,
            'jti' => (string) $this->uuidFactory->create(),
            'sid' => $sessionId,
            'roles' => $roles,
        ]);
    }
}
