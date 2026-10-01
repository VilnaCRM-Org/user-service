<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Converter;

use App\Shared\Infrastructure\Converter\Base64UrlConverter;
use App\Shared\Infrastructure\Converter\KmsPublicKeyConverter;
use App\Tests\Unit\UnitTestCase;
use RuntimeException;

final class KmsPublicKeyConverterTest extends UnitTestCase
{
    /** RFC 7638 section 3.1 example key thumbprint. */
    private const RFC7638_THUMBPRINT = 'NzbLsXh8uDCcd-6MNwXF4W_7noWXFZAfHkxZsRGC9Xs';
    private const KEY_ARN = 'arn:aws:kms:eu-central-1:123456789012:key/jwt';

    public function testConvertsKmsDerPublicKeyToRfc7638Key(): void
    {
        $key = $this->converter()->convert(self::KEY_ARN, $this->rfcDer());

        self::assertSame(self::KEY_ARN, $key->keyId());
        self::assertSame(self::RFC7638_THUMBPRINT, $key->kid());
        self::assertSame(self::rfcModulus(), $key->toJwk()['n']);
        self::assertSame('AQAB', $key->toJwk()['e']);
    }

    public function testWrapsDerAsPemUsableByOpenSsl(): void
    {
        $der = $this->rfcDer();
        $pem = $this->converter()->convert(self::KEY_ARN, $der)->pem();

        self::assertSame($this->expectedPem($der), $pem);
        self::assertNotFalse(openssl_pkey_get_public($pem));
    }

    public function testRejectsUnreadablePublicKey(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('AWS KMS returned an unreadable JWT public key.');

        $this->converter()->convert(self::KEY_ARN, 'not-a-der-key');
    }

    public function testRejectsNonRsaPublicKey(): void
    {
        $ecKey = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);
        self::assertNotFalse($ecKey);
        $pem = (string) openssl_pkey_get_details($ecKey)['key'];
        $der = (string) base64_decode(
            (string) preg_replace('/-----[A-Z ]+-----|\s+/', '', $pem),
            true
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The JWT signing key must be an RSA key.');

        $this->converter()->convert(self::KEY_ARN, $der);
    }

    private static function rfcModulus(): string
    {
        return implode('', [
            '0vx7agoebGcQSuuPiLJXZptN9nndrQmbXEps2aiAFbWhM78LhWx4cbbfAAtVT86z',
            'wu1RK7aPFFxuhDR1L6tSoc_BJECPebWKRXjBZCiFV4n3oknjhMstn64tZ_2W-5Js',
            'GY4Hc5n9yBXArwl93lqt7_RN5w6Cf0h4QyQ5v-65YGjQR0_FDW2QvzqY368QQMic',
            'AtaSqzs8KJZgnYb9c7d0zgdAZHzu6qMQvRL5hajrn1n91CbOpbISD08qNLyrdkt-',
            'bFTWhAI4vMQFh6WeZu0fM4lFd2NcRwr3XPksINHaQ-G_xBniIqbw0Ls1jF44-csF',
            'Cur-kEgU8awapJzKnqDKgw',
        ]);
    }

    private function converter(): KmsPublicKeyConverter
    {
        return new KmsPublicKeyConverter(new Base64UrlConverter());
    }

    private function expectedPem(string $der): string
    {
        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($der), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
    }

    private function rfcDer(): string
    {
        $modulus = (string) base64_decode(strtr(self::rfcModulus(), '-_', '+/'), true);
        $rsaPublicKey = $this->derSequence(
            $this->derInteger($modulus) . $this->derInteger("\x01\x00\x01")
        );
        $algorithm = $this->derSequence("\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00");

        return $this->derSequence(
            $algorithm . $this->derElement("\x03", "\x00" . $rsaPublicKey)
        );
    }

    private function derInteger(string $bytes): string
    {
        $value = ord($bytes[0]) > 0x7f ? "\x00" . $bytes : $bytes;

        return $this->derElement("\x02", $value);
    }

    private function derSequence(string $content): string
    {
        return $this->derElement("\x30", $content);
    }

    private function derElement(string $tag, string $content): string
    {
        $length = strlen($content);
        if ($length < 0x80) {
            return $tag . chr($length) . $content;
        }

        $lengthBytes = ltrim(pack('N', $length), "\x00");

        return $tag . chr(0x80 | strlen($lengthBytes)) . $lengthBytes . $content;
    }
}
