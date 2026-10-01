<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Factory;

use App\Shared\Application\Provider\JwtVerificationKeyProviderInterface;
use App\Shared\Domain\ValueObject\JwtVerificationKey;
use App\Shared\Infrastructure\Converter\Base64UrlConverter;
use App\Shared\Infrastructure\Converter\KmsPublicKeyConverter;
use App\Shared\Infrastructure\Factory\KmsJwtFactory;
use App\Tests\Shared\Kms\FakeKms;
use App\Tests\Unit\UnitTestCase;
use Aws\Kms\Exception\KmsException;
use Aws\Kms\KmsClient;
use Aws\MockHandler;
use Aws\Result;
use RuntimeException;

final class KmsJwtFactoryTest extends UnitTestCase
{
    private const KEY_ARN = 'arn:aws:kms:eu-central-1:123456789012:key/current';

    private FakeKms $kms;
    private JwtVerificationKey $key;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->kms = new FakeKms();
        $this->kms->addKey(self::KEY_ARN);
        $this->key = (new KmsPublicKeyConverter(new Base64UrlConverter()))
            ->convert(self::KEY_ARN, $this->kms->publicKeyDer(self::KEY_ARN));
    }

    public function testCreatesRs256TokenWithTheCurrentKid(): void
    {
        $token = $this->factory($this->kms->client())->create(['sub' => 'user-1']);

        self::assertSame(
            ['alg' => 'RS256', 'typ' => 'JWT', 'kid' => $this->key->kid()],
            $this->segment($token, 0)
        );
        self::assertSame(['sub' => 'user-1'], $this->segment($token, 1));
    }

    public function testSignatureVerifiesWithTheKmsPublicKey(): void
    {
        $token = $this->factory($this->kms->client())->create(['sub' => 'user-1']);
        [$header, $payload, $signature] = explode('.', $token);

        self::assertSame(1, openssl_verify(
            $header . '.' . $payload,
            (string) (new Base64UrlConverter())->decode($signature),
            $this->key->pem(),
            OPENSSL_ALGO_SHA256
        ));
    }

    public function testSignsTheDigestWithTheResolvedKeyAndRs256Algorithm(): void
    {
        $token = $this->factory($this->kms->client())->create(['sub' => 'user-1']);
        $signingInput = substr($token, 0, (int) strrpos($token, '.'));

        self::assertSame(
            [
                'KeyId' => self::KEY_ARN,
                'Message' => hash('sha256', $signingInput, true),
                'MessageType' => 'DIGEST',
                'SigningAlgorithm' => 'RSASSA_PKCS1_V1_5_SHA_256',
            ],
            array_intersect_key(
                $this->kms->lastCallArguments('Sign'),
                array_flip(['KeyId', 'Message', 'MessageType', 'SigningAlgorithm'])
            )
        );
    }

    public function testCustomHeadersCannotOverrideAlgorithmOrKid(): void
    {
        $token = $this->factory($this->kms->client())->create(
            ['sub' => 'user-1'],
            ['alg' => 'none', 'kid' => 'attacker', 'cty' => 'custom']
        );

        self::assertSame(
            ['alg' => 'RS256', 'kid' => $this->key->kid(), 'cty' => 'custom', 'typ' => 'JWT'],
            $this->segment($token, 0)
        );
    }

    public function testKmsErrorsFailClosed(): void
    {
        $this->kms->removeKey(self::KEY_ARN);

        $this->expectException(KmsException::class);

        $this->factory($this->kms->client())->create(['sub' => 'user-1']);
    }

    /**
     * @dataProvider missingSignatures
     */
    public function testRejectsKmsResponseWithoutSignature(Result $result): void
    {
        $client = new KmsClient([
            'region' => 'eu-central-1',
            'version' => 'latest',
            'credentials' => false,
            'handler' => new MockHandler([$result]),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('AWS KMS returned no JWT signature.');

        $this->factory($client)->create(['sub' => 'user-1']);
    }

    /**
     * @return iterable<string, array{Result}>
     */
    public static function missingSignatures(): iterable
    {
        yield 'missing' => [new Result([])];
        yield 'empty' => [new Result(['Signature' => ''])];
    }

    private function factory(KmsClient $client): KmsJwtFactory
    {
        $keyProvider = $this->createMock(JwtVerificationKeyProviderInterface::class);
        $keyProvider->method('current')->willReturn($this->key);

        return new KmsJwtFactory(
            $client,
            $keyProvider,
            $this->createJsonSerializer(),
            new Base64UrlConverter()
        );
    }

    /**
     * @return array<string, array<array-key, bool|float|int|string|null>|bool|float|int|string|null>
     */
    private function segment(string $token, int $index): array
    {
        $json = (new Base64UrlConverter())->decode(explode('.', $token)[$index]);

        return json_decode((string) $json, true, 512, JSON_THROW_ON_ERROR);
    }
}
