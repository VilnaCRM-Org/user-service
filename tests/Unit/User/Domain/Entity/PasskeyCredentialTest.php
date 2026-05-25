<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\PasskeyCredential;
use DateTimeImmutable;

use function json_encode;

use const JSON_THROW_ON_ERROR;

final class PasskeyCredentialTest extends UnitTestCase
{
    public function testCredentialStoresMetadataAndSerializedRecord(): void
    {
        $createdAt = new DateTimeImmutable();
        $fixture = $this->createCredentialFixture();
        $credential = $this->createCredentialFromFixture($fixture, $createdAt);

        self::assertSame($fixture['id'], $credential->getId());
        self::assertSame($fixture['userId'], $credential->getUserId());
        self::assertSame($fixture['credentialId'], $credential->getCredentialId());
        self::assertSame($fixture['record'], $credential->getCredentialRecord());
        self::assertSame($fixture['label'], $credential->getLabel());
        self::assertSame($createdAt, $credential->getCreatedAt());
        self::assertNull($credential->getLastUsedAt());
    }

    public function testMarkUsedUpdatesCredentialRecordAndLastUsedAt(): void
    {
        $updatedRecord = json_encode(['record' => $this->faker->boolean()], JSON_THROW_ON_ERROR);
        $credential = new PasskeyCredential(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $this->faker->uuid(),
            json_encode(['record' => $this->faker->boolean()], JSON_THROW_ON_ERROR),
            $this->faker->words(2, true),
            new DateTimeImmutable()
        );
        $usedAt = new DateTimeImmutable();

        $credential->markUsed($updatedRecord, $usedAt);

        self::assertSame($updatedRecord, $credential->getCredentialRecord());
        self::assertSame($usedAt, $credential->getLastUsedAt());
    }

    /**
     * @return array{
     *     id: string,
     *     userId: string,
     *     credentialId: string,
     *     record: string,
     *     label: string
     * }
     */
    private function createCredentialFixture(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'userId' => $this->faker->uuid(),
            'credentialId' => $this->faker->uuid(),
            'record' => json_encode(['record' => $this->faker->boolean()], JSON_THROW_ON_ERROR),
            'label' => $this->faker->words(2, true),
        ];
    }

    /**
     * @param array{
     *     id: string,
     *     userId: string,
     *     credentialId: string,
     *     record: string,
     *     label: string
     * } $fixture
     */
    private function createCredentialFromFixture(
        array $fixture,
        DateTimeImmutable $createdAt
    ): PasskeyCredential {
        return new PasskeyCredential(
            $fixture['id'],
            $fixture['userId'],
            $fixture['credentialId'],
            $fixture['record'],
            $fixture['label'],
            $createdAt
        );
    }
}
