<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Passkey;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Passkey\PasskeyEncoding;
use InvalidArgumentException;

final class PasskeyEncodingTest extends UnitTestCase
{
    public function testDecodeAcceptsAlreadyPaddedBase64UrlValue(): void
    {
        self::assertSame('test', (new PasskeyEncoding())->decode('dGVzdA=='));
    }

    public function testDecodeAcceptsUnpaddedBase64UrlValues(): void
    {
        $encoding = new PasskeyEncoding();

        self::assertSame('t', $encoding->decode('dA'));
        self::assertSame('te', $encoding->decode('dGU'));
    }

    public function testDecodeRejectsInvalidBase64UrlValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64url value.');

        (new PasskeyEncoding())->decode('%%%%');
    }
}
