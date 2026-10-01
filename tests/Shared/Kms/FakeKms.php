<?php

declare(strict_types=1);

namespace App\Tests\Shared\Kms;

use Aws\CommandInterface;
use Aws\Kms\Exception\KmsException;
use Aws\Kms\KmsClient;
use Aws\Result;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use OpenSSLAsymmetricKey;
use RuntimeException;

/**
 * In-memory stand-in for AWS KMS asymmetric signing keys, used only by unit
 * tests. It answers GetPublicKey and Sign (RSASSA_PKCS1_V1_5_SHA_256 over a
 * DIGEST) with locally generated RSA keys, so the code under test runs through
 * a real KmsClient without any network call.
 */
final class FakeKms
{
    public const SIGNING_ALGORITHM = 'RSASSA_PKCS1_V1_5_SHA_256';

    private const SHA256_DIGEST_INFO
        = "\x30\x31\x30\x0d\x06\x09\x60\x86\x48\x01\x65\x03\x04\x02\x01\x05\x00\x04\x20";

    /** @var array<string, OpenSSLAsymmetricKey> */
    private static array $keyPool = [];

    /** @var array<string, array{key: OpenSSLAsymmetricKey, usage: string, algorithms: list<string>}> */
    private array $keys = [];

    /** @var list<array{name: string, args: array<string, bool|int|string|null>}> */
    private array $calls = [];

    /**
     * @param list<string> $algorithms
     */
    public function addKey(
        string $keyId,
        string $usage = 'SIGN_VERIFY',
        array $algorithms = [self::SIGNING_ALGORITHM]
    ): void {
        $this->keys[$keyId] = [
            'key' => self::poolKey($keyId),
            'usage' => $usage,
            'algorithms' => $algorithms,
        ];
    }

    public function removeKey(string $keyId): void
    {
        unset($this->keys[$keyId]);
    }

    public function client(): KmsClient
    {
        return new KmsClient([
            'region' => 'eu-central-1',
            'version' => 'latest',
            'credentials' => false,
            'handler' => $this->handler(),
        ]);
    }

    public function publicKeyPem(string $keyId): string
    {
        $details = openssl_pkey_get_details(self::poolKey($keyId));
        if (!is_array($details) || !is_string($details['key'] ?? null)) {
            throw new RuntimeException('Unable to export the fake KMS public key.');
        }

        return $details['key'];
    }

    public function publicKeyDer(string $keyId): string
    {
        $body = preg_replace('/-----[A-Z ]+-----|\s+/', '', $this->publicKeyPem($keyId));

        return (string) base64_decode((string) $body, true);
    }

    /**
     * Signs like KMS does for RSASSA_PKCS1_V1_5_SHA_256 with MessageType=DIGEST.
     */
    public function signDigest(string $keyId, string $digest): string
    {
        $signature = '';
        openssl_private_encrypt(
            self::SHA256_DIGEST_INFO . $digest,
            $signature,
            self::poolKey($keyId),
            OPENSSL_PKCS1_PADDING
        );

        return $signature;
    }

    public function countCalls(string $name): int
    {
        return count(array_filter(
            $this->calls,
            static fn (array $call): bool => $call['name'] === $name
        ));
    }

    /**
     * @return array<string, bool|int|string|null>
     */
    public function lastCallArguments(string $name): array
    {
        $matching = array_values(array_filter(
            $this->calls,
            static fn (array $call): bool => $call['name'] === $name
        ));

        return $matching === [] ? [] : $matching[count($matching) - 1]['args'];
    }

    private function handler(): \Closure
    {
        return fn (CommandInterface $command): PromiseInterface => $this->handle($command);
    }

    private function handle(CommandInterface $command): PromiseInterface
    {
        $arguments = $command->toArray();
        $this->calls[] = ['name' => $command->getName(), 'args' => $arguments];
        $keyId = (string) ($arguments['KeyId'] ?? '');

        if (!isset($this->keys[$keyId])) {
            return Create::rejectionFor(new KmsException(
                'Key not found.',
                $command,
                ['code' => 'NotFoundException']
            ));
        }

        return Create::promiseFor(match ($command->getName()) {
            'GetPublicKey' => $this->publicKeyResult($keyId),
            'Sign' => $this->signResult($keyId, (string) $arguments['Message']),
        });
    }

    private function publicKeyResult(string $keyId): Result
    {
        return new Result([
            'KeyId' => $keyId,
            'KeySpec' => 'RSA_2048',
            'KeyUsage' => $this->keys[$keyId]['usage'],
            'PublicKey' => $this->publicKeyDer($keyId),
            'SigningAlgorithms' => $this->keys[$keyId]['algorithms'],
        ]);
    }

    private function signResult(string $keyId, string $digest): Result
    {
        return new Result([
            'KeyId' => $keyId,
            'Signature' => $this->signDigest($keyId, $digest),
            'SigningAlgorithm' => self::SIGNING_ALGORITHM,
        ]);
    }

    private static function poolKey(string $keyId): OpenSSLAsymmetricKey
    {
        if (!isset(self::$keyPool[$keyId])) {
            $key = openssl_pkey_new([
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]);
            if (!$key instanceof OpenSSLAsymmetricKey) {
                throw new RuntimeException('Unable to generate a fake KMS key.');
            }
            self::$keyPool[$keyId] = $key;
        }

        return self::$keyPool[$keyId];
    }
}
