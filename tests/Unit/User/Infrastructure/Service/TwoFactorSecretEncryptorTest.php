<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Service;

use App\Tests\Unit\UnitTestCase;
use App\User\Infrastructure\Service\OpenSslEncryptTestDouble;
use App\User\Infrastructure\Service\TwoFactorSecretEncryptor;
use RuntimeException;

final class TwoFactorSecretEncryptorTest extends UnitTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/OpenSslEncryptOverride.inc';
    }

    public function testEncryptAndDecryptRoundTrip(): void
    {
        $encryptor = new TwoFactorSecretEncryptor(
            base64_encode('0123456789abcdef0123456789abcdef')
        );
        $secret = 'JBSWY3DPEHPK3PXP';

        $cipherText = $encryptor->encrypt($secret);
        $plainSecret = $encryptor->decrypt($cipherText);

        $this->assertNotSame($secret, $cipherText);
        $this->assertSame($secret, $plainSecret);
    }

    public function testEncryptUsesRandomIv(): void
    {
        $encryptor = new TwoFactorSecretEncryptor(
            base64_encode('0123456789abcdef0123456789abcdef')
        );

        $first = $encryptor->encrypt('JBSWY3DPEHPK3PXP');
        $second = $encryptor->encrypt('JBSWY3DPEHPK3PXP');

        $this->assertNotSame($first, $second);
    }

    public function testEncryptThrowsWhenOpenSslEncryptionFails(): void
    {
        $encryptor = new TwoFactorSecretEncryptor(
            base64_encode('0123456789abcdef0123456789abcdef')
        );

        OpenSslEncryptTestDouble::enableFailure();

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Failed to encrypt two-factor secret.');

            $encryptor->encrypt('JBSWY3DPEHPK3PXP');
        } finally {
            OpenSslEncryptTestDouble::disableFailure();
        }
    }

    public function testConstructorRejectsInvalidKey(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'TWO_FACTOR_ENCRYPTION_KEY must be 32-byte base64.'
        );

        new TwoFactorSecretEncryptor('invalid-key');
    }

    public function testDecryptRejectsInvalidPayloadEncoding(): void
    {
        $encryptor = new TwoFactorSecretEncryptor(
            base64_encode('0123456789abcdef0123456789abcdef')
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid payload encoding.');

        $encryptor->decrypt('###');
    }

    public function testDecryptRejectsPayloadTooShort(): void
    {
        $encryptor = new TwoFactorSecretEncryptor(
            base64_encode('0123456789abcdef0123456789abcdef')
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid payload length.');

        $encryptor->decrypt(base64_encode('tooshort'));
    }

    public function testDecryptRejectsPayloadAtIvAndTagBoundaryLength(): void
    {
        $encryptor = new TwoFactorSecretEncryptor(
            base64_encode('0123456789abcdef0123456789abcdef')
        );

        $boundaryRawPayload = str_repeat('A', 28);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid payload length.');

        $encryptor->decrypt(base64_encode($boundaryRawPayload));
    }

    public function testDecryptAllowsMinimumPayloadLengthValidationAndFailsDuringDecrypt(): void
    {
        $encryptor = new TwoFactorSecretEncryptor(
            base64_encode('0123456789abcdef0123456789abcdef')
        );

        $minimumLengthRawPayload = str_repeat('B', 29);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to decrypt secret.');

        $encryptor->decrypt(base64_encode($minimumLengthRawPayload));
    }

    public function testDecryptRejectsTamperedCiphertext(): void
    {
        $encryptor = new TwoFactorSecretEncryptor(
            base64_encode('0123456789abcdef0123456789abcdef')
        );

        $encrypted = $encryptor->encrypt('JBSWY3DPEHPK3PXP');
        $decoded = base64_decode($encrypted, true);
        self::assertIsString($decoded);

        $tampered = $decoded;
        $tampered[strlen($tampered) - 1] = chr(
            ord($tampered[strlen($tampered) - 1]) ^ 0xFF
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to decrypt secret.');

        $encryptor->decrypt(base64_encode($tampered));
    }
}
