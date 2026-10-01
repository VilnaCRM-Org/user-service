<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Converter;

use App\Shared\Infrastructure\Converter\Base64UrlConverter;
use App\Tests\Unit\UnitTestCase;

final class Base64UrlConverterTest extends UnitTestCase
{
    public function testEncodesWithUrlAlphabetAndNoPadding(): void
    {
        self::assertSame('-_8', (new Base64UrlConverter())->encode("\xfb\xff"));
    }

    public function testDecodesUrlAlphabetWithoutPadding(): void
    {
        self::assertSame("\xfb\xff", (new Base64UrlConverter())->decode('-_8'));
    }

    public function testRoundTripsBinaryValues(): void
    {
        $converter = new Base64UrlConverter();
        $value = random_bytes(64);

        self::assertSame($value, $converter->decode($converter->encode($value)));
    }

    /**
     * @dataProvider invalidValues
     */
    public function testRejectsValuesOutsideTheUrlAlphabet(string $value): void
    {
        self::assertNull((new Base64UrlConverter())->decode($value));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidValues(): iterable
    {
        yield 'empty' => [''];
        yield 'standard alphabet' => ['+/8'];
        yield 'padding' => ['-_8='];
        yield 'leading invalid character' => ['.-_8'];
        yield 'trailing invalid character' => ['-_8.'];
        yield 'impossible length' => ['A'];
        yield 'trailing newline' => ["-_8\n"];
    }
}
