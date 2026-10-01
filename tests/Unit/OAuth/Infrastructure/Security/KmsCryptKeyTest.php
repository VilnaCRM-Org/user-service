<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\Security;

use App\OAuth\Infrastructure\Security\KmsCryptKey;
use App\Tests\Unit\UnitTestCase;
use League\OAuth2\Server\CryptKeyInterface;

final class KmsCryptKeyTest extends UnitTestCase
{
    public function testHoldsNoLocalKeyMaterial(): void
    {
        $key = new KmsCryptKey();

        self::assertInstanceOf(CryptKeyInterface::class, $key);
        self::assertSame('', $key->getKeyPath());
        self::assertNull($key->getPassPhrase());
        self::assertSame('', $key->getKeyContents());
    }
}
