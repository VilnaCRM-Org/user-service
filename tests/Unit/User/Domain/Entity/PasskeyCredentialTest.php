<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\PasskeyCredential;
use DateTimeImmutable;

final class PasskeyCredentialTest extends UnitTestCase
{
    public function testCredentialStoresMetadataAndSerializedRecord(): void
    {
        $createdAt = new DateTimeImmutable();
        $credential = new PasskeyCredential(
            'passkey-id',
            'user-id',
            'credential-id',
            '{"record":true}',
            'Work laptop',
            $createdAt
        );

        self::assertSame('passkey-id', $credential->getId());
        self::assertSame('user-id', $credential->getUserId());
        self::assertSame('credential-id', $credential->getCredentialId());
        self::assertSame('{"record":true}', $credential->getCredentialRecord());
        self::assertSame('Work laptop', $credential->getLabel());
        self::assertSame($createdAt, $credential->getCreatedAt());
        self::assertNull($credential->getLastUsedAt());
    }

    public function testMarkUsedUpdatesCredentialRecordAndLastUsedAt(): void
    {
        $credential = new PasskeyCredential(
            'passkey-id',
            'user-id',
            'credential-id',
            '{"record":false}',
            'Work laptop',
            new DateTimeImmutable()
        );
        $usedAt = new DateTimeImmutable();

        $credential->markUsed('{"record":true}', $usedAt);

        self::assertSame('{"record":true}', $credential->getCredentialRecord());
        self::assertSame($usedAt, $credential->getLastUsedAt());
    }
}
