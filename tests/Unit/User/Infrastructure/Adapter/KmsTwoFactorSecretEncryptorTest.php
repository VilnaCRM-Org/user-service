<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Adapter;

use App\Tests\Unit\UnitTestCase;
use App\User\Infrastructure\Adapter\KmsTwoFactorSecretEncryptor;
use Aws\Command;
use Aws\Kms\Exception\KmsException;
use Aws\Kms\KmsClient;
use Aws\MockHandler;
use Aws\Result;
use InvalidArgumentException;
use RuntimeException;

final class KmsTwoFactorSecretEncryptorTest extends UnitTestCase
{
    private const KEY_ID = 'arn:aws:kms:eu-central-1:123456789012:key/two-factor';

    private MockHandler $kms;
    private KmsTwoFactorSecretEncryptor $encryptor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->kms = new MockHandler();
        $this->encryptor = new KmsTwoFactorSecretEncryptor(
            new KmsClient([
                'version' => 'latest',
                'region' => 'eu-central-1',
                'credentials' => ['key' => 'unit', 'secret' => 'unit'],
                'handler' => $this->kms,
            ]),
            self::KEY_ID
        );
    }

    public function testEncryptBindsTheUserIdEncryptionContextToTheTwoFactorKey(): void
    {
        $userId = $this->faker->uuid();
        $this->kms->append(new Result(['CiphertextBlob' => "\x01\x02kms-blob"]));

        $payload = $this->encryptor->encrypt('JBSWY3DPEHPK3PXP', $userId);

        $this->assertSame(base64_encode("\x01\x02kms-blob"), $payload);
        $this->assertKmsCall('Encrypt', [
            'KeyId' => self::KEY_ID,
            'Plaintext' => 'JBSWY3DPEHPK3PXP',
            'EncryptionContext' => ['user_id' => $userId],
            'EncryptionAlgorithm' => 'SYMMETRIC_DEFAULT',
        ]);
    }

    public function testDecryptRequiresTheSameUserIdEncryptionContextAndKey(): void
    {
        $userId = $this->faker->uuid();
        $this->kms->append(new Result(['Plaintext' => 'JBSWY3DPEHPK3PXP']));

        $secret = $this->encryptor->decrypt(base64_encode('kms-blob'), $userId);

        $this->assertSame('JBSWY3DPEHPK3PXP', $secret);
        $this->assertKmsCall('Decrypt', [
            'KeyId' => self::KEY_ID,
            'CiphertextBlob' => 'kms-blob',
            'EncryptionContext' => ['user_id' => $userId],
            'EncryptionAlgorithm' => 'SYMMETRIC_DEFAULT',
        ]);
    }

    public function testDecryptFailsClosedWhenKmsRejectsTheEncryptionContext(): void
    {
        $this->kms->append($this->kmsError('Decrypt', 'InvalidCiphertextException'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to decrypt two-factor secret.');

        $this->encryptor->decrypt(base64_encode('kms-blob'), $this->faker->uuid());
    }

    public function testDecryptFailureKeepsTheKmsErrorAsPrevious(): void
    {
        $error = $this->kmsError('Decrypt', 'AccessDeniedException');
        $this->kms->append($error);

        try {
            $this->encryptor->decrypt(base64_encode('kms-blob'), $this->faker->uuid());
            $this->fail('Decrypt must fail closed.');
        } catch (RuntimeException $exception) {
            $this->assertNotInstanceOf(KmsException::class, $exception);
            $this->assertSame($error, $exception->getPrevious());
        }
    }

    public function testEncryptFailsClosedWhenKmsRejectsTheRequest(): void
    {
        $error = $this->kmsError('Encrypt', 'AccessDeniedException');
        $this->kms->append($error);

        try {
            $this->encryptor->encrypt('JBSWY3DPEHPK3PXP', $this->faker->uuid());
            $this->fail('Encrypt must fail closed.');
        } catch (RuntimeException $exception) {
            $this->assertNotInstanceOf(KmsException::class, $exception);
            $this->assertSame('Failed to encrypt two-factor secret.', $exception->getMessage());
            $this->assertSame($error, $exception->getPrevious());
        }
    }

    public function testEncryptRejectsAnEmptyCiphertextFromKms(): void
    {
        $this->kms->append(new Result(['CiphertextBlob' => '']));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to encrypt two-factor secret.');

        $this->encryptor->encrypt('JBSWY3DPEHPK3PXP', $this->faker->uuid());
    }

    public function testEncryptRejectsAMissingCiphertextFromKms(): void
    {
        $this->kms->append(new Result([]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to encrypt two-factor secret.');

        $this->encryptor->encrypt('JBSWY3DPEHPK3PXP', $this->faker->uuid());
    }

    public function testDecryptRejectsAnEmptyPlaintextFromKms(): void
    {
        $this->kms->append(new Result(['Plaintext' => '']));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to decrypt two-factor secret.');

        $this->encryptor->decrypt(base64_encode('kms-blob'), $this->faker->uuid());
    }

    public function testDecryptRejectsAMissingPlaintextFromKms(): void
    {
        $this->kms->append(new Result([]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to decrypt two-factor secret.');

        $this->encryptor->decrypt(base64_encode('kms-blob'), $this->faker->uuid());
    }

    public function testEncryptAcceptsThePlaintextSizeBound(): void
    {
        $secret = str_repeat('A', KmsTwoFactorSecretEncryptor::MAX_SECRET_BYTES);
        $this->kms->append(new Result(['CiphertextBlob' => 'kms-blob']));

        $this->encryptor->encrypt($secret, $this->faker->uuid());

        $this->assertSame($secret, $this->kms->getLastCommand()->offsetGet('Plaintext'));
    }

    public function testEncryptRefusesAnOversizedSecretWithoutCallingKms(): void
    {
        $this->assertSame(4096, KmsTwoFactorSecretEncryptor::MAX_SECRET_BYTES);

        $this->assertRefused(
            fn () => $this->encryptor->encrypt(str_repeat('A', 4097), $this->faker->uuid()),
            'Two-factor secret must be between 1 and 4096 bytes.'
        );
    }

    public function testEncryptRefusesAnEmptySecretWithoutCallingKms(): void
    {
        $this->assertRefused(
            fn () => $this->encryptor->encrypt('', $this->faker->uuid()),
            'Two-factor secret must be between 1 and 4096 bytes.'
        );
    }

    public function testEncryptRefusesABlankUserIdWithoutCallingKms(): void
    {
        $this->assertRefused(
            fn () => $this->encryptor->encrypt('JBSWY3DPEHPK3PXP', ' '),
            'Two-factor encryption context user_id must not be empty.'
        );
    }

    public function testDecryptRefusesABlankUserIdWithoutCallingKms(): void
    {
        $this->assertRefused(
            fn () => $this->encryptor->decrypt(base64_encode('kms-blob'), ''),
            'Two-factor encryption context user_id must not be empty.'
        );
    }

    public function testDecryptAcceptsThePayloadSizeBound(): void
    {
        $blob = str_repeat("\xAB", 6144);
        $this->assertSame(
            KmsTwoFactorSecretEncryptor::MAX_PAYLOAD_LENGTH,
            strlen(base64_encode($blob))
        );
        $this->kms->append(new Result(['Plaintext' => 'JBSWY3DPEHPK3PXP']));

        $this->encryptor->decrypt(base64_encode($blob), $this->faker->uuid());

        $this->assertSame($blob, $this->kms->getLastCommand()->offsetGet('CiphertextBlob'));
    }

    public function testDecryptRefusesAnOversizedPayloadWithoutCallingKms(): void
    {
        $this->assertSame(8192, KmsTwoFactorSecretEncryptor::MAX_PAYLOAD_LENGTH);

        $this->assertRefused(
            fn () => $this->encryptor->decrypt(
                base64_encode(str_repeat("\xAB", 6145)),
                $this->faker->uuid()
            ),
            'Two-factor secret payload is invalid.'
        );
    }

    public function testDecryptRefusesAnInvalidBase64PayloadWithoutCallingKms(): void
    {
        $this->assertRefused(
            fn () => $this->encryptor->decrypt('not base64!', $this->faker->uuid()),
            'Two-factor secret payload is invalid.'
        );
    }

    public function testDecryptRefusesAnEmptyPayloadWithoutCallingKms(): void
    {
        $this->assertRefused(
            fn () => $this->encryptor->decrypt('', $this->faker->uuid()),
            'Two-factor secret payload is invalid.'
        );
    }

    /**
     * @param array<string, array<string, string>|string> $expected
     */
    private function assertKmsCall(string $operation, array $expected): void
    {
        $command = $this->kms->getLastCommand();
        $this->assertSame($operation, $command->getName());
        $parameters = $command->toArray();
        unset($parameters['@http'], $parameters['@context']);
        ksort($parameters);
        ksort($expected);
        $this->assertSame($expected, $parameters);
    }

    private function assertRefused(callable $call, string $message): void
    {
        try {
            $call();
            $this->fail('The input must be refused.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame($message, $exception->getMessage());
        }

        $this->assertNull($this->kms->getLastCommand());
    }

    private function kmsError(string $operation, string $code): KmsException
    {
        return new KmsException(
            sprintf('%s failed.', $operation),
            new Command($operation),
            ['code' => $code]
        );
    }
}
