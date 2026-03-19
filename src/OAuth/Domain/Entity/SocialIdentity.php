<?php

declare(strict_types=1);

namespace App\OAuth\Domain\Entity;

use App\OAuth\Domain\ValueObject\OAuthProvider;
use DateTimeImmutable;

final class SocialIdentity
{
    private DateTimeImmutable $lastUsedAt;

    public function __construct(
        private string $id,
        private OAuthProvider $provider,
        private string $providerId,
        private string $userId,
        private DateTimeImmutable $createdAt,
    ) {
        $this->lastUsedAt = $createdAt;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @internal For Doctrine ODM hydration only.
     *
     * @psalm-suppress PossiblyUnusedMethod Doctrine hydration
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getProvider(): OAuthProvider
    {
        return $this->provider;
    }

    /**
     * @internal For Doctrine ODM hydration only.
     *
     * @psalm-suppress PossiblyUnusedMethod Doctrine hydration
     */
    public function setProvider(OAuthProvider $provider): void
    {
        $this->provider = $provider;
    }

    public function getProviderId(): string
    {
        return $this->providerId;
    }

    /**
     * @internal For Doctrine ODM hydration only.
     *
     * @psalm-suppress PossiblyUnusedMethod Doctrine hydration
     */
    public function setProviderId(string $providerId): void
    {
        $this->providerId = $providerId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * @internal For Doctrine ODM hydration only.
     *
     * @psalm-suppress PossiblyUnusedMethod Doctrine hydration
     */
    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @internal For Doctrine ODM hydration only.
     *
     * @psalm-suppress PossiblyUnusedMethod Doctrine hydration
     */
    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getLastUsedAt(): DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    /**
     * @internal For Doctrine ODM hydration only.
     *
     * @psalm-suppress PossiblyUnusedMethod Doctrine hydration
     */
    public function setLastUsedAt(DateTimeImmutable $lastUsedAt): void
    {
        $this->lastUsedAt = $lastUsedAt;
    }

    public function touchLastUsed(DateTimeImmutable $now): void
    {
        $this->lastUsedAt = $now;
    }
}
