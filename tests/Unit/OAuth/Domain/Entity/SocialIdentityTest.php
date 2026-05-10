<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Domain\Entity;

use App\OAuth\Domain\Entity\SocialIdentity;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\Tests\Unit\UnitTestCase;
use DateTimeImmutable;

final class SocialIdentityTest extends UnitTestCase
{
    public function testConstructorSetsProperties(): void
    {
        $id = $this->faker->uuid();
        $provider = new OAuthProvider('github');
        $providerId = $this->faker->numerify('######');
        $userId = $this->faker->uuid();
        $createdAt = new DateTimeImmutable();

        $identity = new SocialIdentity(
            $id,
            $provider,
            $providerId,
            $userId,
            $createdAt,
        );

        $this->assertSame($id, $identity->getId());
        $this->assertSame($provider, $identity->getProvider());
        $this->assertSame($providerId, $identity->getProviderId());
        $this->assertSame($userId, $identity->getUserId());
        $this->assertSame($createdAt, $identity->getCreatedAt());
        $this->assertSame($createdAt, $identity->getLastUsedAt());
    }

    public function testTouchLastUsedUpdatesTimestamp(): void
    {
        $createdAt = new DateTimeImmutable('2025-01-01');
        $identity = $this->createIdentity(createdAt: $createdAt);

        $newTime = new DateTimeImmutable('2025-06-15');
        $identity->touchLastUsed($newTime);

        $this->assertSame($newTime, $identity->getLastUsedAt());
        $this->assertSame($createdAt, $identity->getCreatedAt());
    }

    public function testSetIdUpdatesId(): void
    {
        $identity = $this->createIdentity();
        $newId = $this->faker->uuid();

        $identity->setId($newId);

        $this->assertSame($newId, $identity->getId());
    }

    public function testSetProviderUpdatesProvider(): void
    {
        $identity = $this->createIdentity();
        $newProvider = new OAuthProvider('facebook');

        $identity->setProvider($newProvider);

        $this->assertSame($newProvider, $identity->getProvider());
    }

    public function testSetProviderIdUpdatesProviderId(): void
    {
        $identity = $this->createIdentity();
        $newProviderId = $this->faker->numerify('######');

        $identity->setProviderId($newProviderId);

        $this->assertSame($newProviderId, $identity->getProviderId());
    }

    public function testSetUserIdUpdatesUserId(): void
    {
        $identity = $this->createIdentity();
        $newUserId = $this->faker->uuid();

        $identity->setUserId($newUserId);

        $this->assertSame($newUserId, $identity->getUserId());
    }

    public function testSetCreatedAtUpdatesCreatedAt(): void
    {
        $identity = $this->createIdentity();
        $newCreatedAt = new DateTimeImmutable('2024-01-01');

        $identity->setCreatedAt($newCreatedAt);

        $this->assertSame($newCreatedAt, $identity->getCreatedAt());
    }

    public function testSetLastUsedAtUpdatesLastUsedAt(): void
    {
        $identity = $this->createIdentity();
        $newLastUsedAt = new DateTimeImmutable('2024-06-01');

        $identity->setLastUsedAt($newLastUsedAt);

        $this->assertSame($newLastUsedAt, $identity->getLastUsedAt());
    }

    private function createIdentity(
        ?DateTimeImmutable $createdAt = null,
    ): SocialIdentity {
        return new SocialIdentity(
            $this->faker->uuid(),
            new OAuthProvider('google'),
            $this->faker->numerify('######'),
            $this->faker->uuid(),
            $createdAt ?? new DateTimeImmutable(),
        );
    }
}
