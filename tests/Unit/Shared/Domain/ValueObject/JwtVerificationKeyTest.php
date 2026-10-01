<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\JwtVerificationKey;
use App\Tests\Unit\UnitTestCase;

final class JwtVerificationKeyTest extends UnitTestCase
{
    public function testExposesKeyMaterialReferences(): void
    {
        $key = new JwtVerificationKey('arn:key/1', 'kid-1', 'PEM', 'modulus', 'AQAB');

        self::assertSame('arn:key/1', $key->keyId());
        self::assertSame('kid-1', $key->kid());
        self::assertSame('PEM', $key->pem());
    }

    public function testRendersRs256SigningJwk(): void
    {
        $key = new JwtVerificationKey('arn:key/1', 'kid-1', 'PEM', 'modulus', 'AQAB');

        self::assertSame(
            [
                'kty' => 'RSA',
                'use' => 'sig',
                'alg' => 'RS256',
                'kid' => 'kid-1',
                'n' => 'modulus',
                'e' => 'AQAB',
            ],
            $key->toJwk()
        );
    }
}
