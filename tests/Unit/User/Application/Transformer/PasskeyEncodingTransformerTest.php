<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Transformer;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Transformer\PasskeyEncodingTransformer;
use InvalidArgumentException;

final class PasskeyEncodingTransformerTest extends UnitTestCase
{
    public function testDecodeAcceptsAlreadyPaddedBase64UrlValue(): void
    {
        self::assertSame('test', (new PasskeyEncodingTransformer())->decode('dGVzdA=='));
    }

    public function testDecodeAcceptsUnpaddedBase64UrlValues(): void
    {
        $encoding = new PasskeyEncodingTransformer();

        self::assertSame('t', $encoding->decode('dA'));
        self::assertSame('te', $encoding->decode('dGU'));
        self::assertSame('test', $encoding->decode('dGVzdA'));
    }

    public function testEncodeProducesUrlSafeBase64WithoutPadding(): void
    {
        $encodedValue = (new PasskeyEncodingTransformer())->encode('test');

        self::assertSame('dGVzdA', $encodedValue);
        self::assertStringNotContainsString('=', $encodedValue);
        self::assertStringNotContainsString('+', $encodedValue);
        self::assertStringNotContainsString('/', $encodedValue);
    }

    public function testRoundTripEncodingAndDecoding(): void
    {
        $encoding = new PasskeyEncodingTransformer();
        $rawValue = $this->faker->sha256();

        self::assertSame($rawValue, $encoding->decode($encoding->encode($rawValue)));
    }

    public function testDecodeRejectsInvalidBase64UrlValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64url value.');

        (new PasskeyEncodingTransformer())->decode('%%%%');
    }
}
