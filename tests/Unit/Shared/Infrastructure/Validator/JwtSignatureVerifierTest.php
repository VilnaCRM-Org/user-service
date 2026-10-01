<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Validator;

use App\Shared\Application\Provider\JwtVerificationKeyProviderInterface;
use App\Shared\Domain\ValueObject\JwtVerificationKey;
use App\Shared\Infrastructure\Converter\Base64UrlConverter;
use App\Shared\Infrastructure\Converter\KmsPublicKeyConverter;
use App\Shared\Infrastructure\Validator\JwtSignatureVerifier;
use App\Tests\Shared\Kms\FakeKms;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

final class JwtSignatureVerifierTest extends UnitTestCase
{
    private const CURRENT = 'arn:aws:kms:eu-central-1:123456789012:key/current';
    private const PREVIOUS = 'arn:aws:kms:eu-central-1:123456789012:key/previous';
    private const PAYLOAD = ['sub' => 'user-1', 'exp' => 1_700_000_900];

    private FakeKms $kms;
    private Base64UrlConverter $base64Url;
    /** @var array<string, JwtVerificationKey> */
    private array $keys = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->kms = new FakeKms();
        $this->base64Url = new Base64UrlConverter();
        $converter = new KmsPublicKeyConverter($this->base64Url);
        foreach ([self::CURRENT, self::PREVIOUS] as $keyId) {
            $this->kms->addKey($keyId);
            $this->keys[$keyId] = $converter->convert($keyId, $this->kms->publicKeyDer($keyId));
        }
    }

    public function testReturnsHeaderAndPayloadOfAValidToken(): void
    {
        $header = $this->header(self::CURRENT);

        self::assertSame(
            ['header' => $header, 'payload' => self::PAYLOAD],
            $this->verifier()->verify($this->token($header, self::PAYLOAD, self::CURRENT))
        );
    }

    public function testAcceptsTokenOfThePreviousKeyWhileItIsConfigured(): void
    {
        $token = $this->token($this->header(self::PREVIOUS), self::PAYLOAD, self::PREVIOUS);

        self::assertSame(self::PAYLOAD, $this->verifier()->verify($token)['payload'] ?? null);
    }

    public function testRejectsTamperedPayload(): void
    {
        [$header, , $signature] = explode(
            '.',
            $this->token($this->header(self::CURRENT), self::PAYLOAD, self::CURRENT)
        );
        $forged = $this->encode(['sub' => 'admin', 'exp' => 1_700_000_900]);

        self::assertNull($this->verifier()->verify("{$header}.{$forged}.{$signature}"));
    }

    public function testRejectsSignatureOfAnotherKeyUnderTheSameKid(): void
    {
        $token = $this->token($this->header(self::CURRENT), self::PAYLOAD, self::PREVIOUS);

        self::assertNull($this->verifier()->verify($token));
    }

    public function testRejectsUnknownKid(): void
    {
        $header = ['alg' => 'RS256', 'typ' => 'JWT', 'kid' => 'unknown'];

        self::assertNull(
            $this->verifier()->verify($this->token($header, self::PAYLOAD, self::CURRENT))
        );
    }

    /**
     * @dataProvider rejectedHeaders
     *
     * @param array<string, array<array-key, bool|float|int|string|null>|bool|float|int|string|null> $header
     */
    public function testRejectsHeaderWithoutRs256AndKidBeforeLookingUpKeys(array $header): void
    {
        $keyProvider = $this->keyProvider();
        $keyProvider->expects($this->never())->method('findByKid');
        $verifier = new JwtSignatureVerifier(
            $keyProvider,
            $this->createJsonSerializer(),
            $this->base64Url
        );

        self::assertNull($verifier->verify($this->token($header, self::PAYLOAD, self::CURRENT)));
    }

    /**
     * @return iterable<string, array{array<string, array<array-key, bool|float|int|string|null>|bool|float|int|string|null>}>
     */
    public static function rejectedHeaders(): iterable
    {
        yield 'HS256' => [['alg' => 'HS256', 'kid' => 'kid']];
        yield 'none' => [['alg' => 'none', 'kid' => 'kid']];
        yield 'missing alg' => [['kid' => 'kid']];
        yield 'missing kid' => [['alg' => 'RS256']];
        yield 'non-string kid' => [['alg' => 'RS256', 'kid' => 7]];
    }

    public function testRejectsRs384HeaderEvenWithAValidKidAndSignature(): void
    {
        $header = ['alg' => 'RS384', 'kid' => $this->keys[self::CURRENT]->kid()];

        self::assertNull(
            $this->verifier()->verify($this->token($header, self::PAYLOAD, self::CURRENT))
        );
    }

    /**
     * @dataProvider malformedTokens
     */
    public function testRejectsMalformedTokens(string $token): void
    {
        self::assertNull($this->verifier()->verify($token));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformedTokens(): iterable
    {
        $json = rtrim(strtr(base64_encode('{"alg":"RS256"}'), '+/', '-_'), '=');

        yield 'two segments' => ["{$json}.{$json}"];
        yield 'four segments' => ["{$json}.{$json}.c2ln.c2ln"];
        yield 'header not base64url' => ["***.{$json}.c2ln"];
        yield 'payload not base64url' => ["{$json}.***.c2ln"];
        yield 'signature not base64url' => ["{$json}.{$json}.***"];
        yield 'header not json' => ['bm90LWpzb24.' . $json . '.c2ln'];
        yield 'payload not an object' => ["{$json}.MTIz.c2ln"];
    }

    public function testMalformedPartsAreRejectedEvenWhenOtherPartsAreValid(): void
    {
        $token = $this->token($this->header(self::CURRENT), self::PAYLOAD, self::CURRENT);
        [$header, $payload, $signature] = explode('.', $token);

        self::assertNull($this->verifier()->verify("***.{$payload}.{$signature}"));
        self::assertNull($this->verifier()->verify("{$header}.***.{$signature}"));
        self::assertNull($this->verifier()->verify("{$header}.{$payload}.***"));
    }

    public function testKeyLookupErrorsFailClosed(): void
    {
        $keyProvider = $this->createMock(JwtVerificationKeyProviderInterface::class);
        $keyProvider->method('findByKid')->willThrowException(new RuntimeException('KMS down'));
        $verifier = new JwtSignatureVerifier(
            $keyProvider,
            $this->createJsonSerializer(),
            $this->base64Url
        );

        $this->expectException(RuntimeException::class);

        $verifier->verify(
            $this->token($this->header(self::CURRENT), self::PAYLOAD, self::CURRENT)
        );
    }

    private function verifier(): JwtSignatureVerifier
    {
        return new JwtSignatureVerifier(
            $this->keyProvider(),
            $this->createJsonSerializer(),
            $this->base64Url
        );
    }

    private function keyProvider(): JwtVerificationKeyProviderInterface&MockObject
    {
        $keysByKid = [];
        foreach ($this->keys as $key) {
            $keysByKid[$key->kid()] = $key;
        }

        $keyProvider = $this->createMock(JwtVerificationKeyProviderInterface::class);
        $keyProvider->method('findByKid')->willReturnCallback(
            static fn (string $kid): ?JwtVerificationKey => $keysByKid[$kid] ?? null
        );

        return $keyProvider;
    }

    /**
     * @return array<string, string>
     */
    private function header(string $keyId): array
    {
        return ['alg' => 'RS256', 'typ' => 'JWT', 'kid' => $this->keys[$keyId]->kid()];
    }

    /**
     * @param array<string, array<array-key, bool|float|int|string|null>|bool|float|int|string|null> $header
     * @param array<string, array<array-key, bool|float|int|string|null>|bool|float|int|string|null> $payload
     */
    private function token(array $header, array $payload, string $signingKeyId): string
    {
        $signingInput = $this->encode($header) . '.' . $this->encode($payload);
        $signature = $this->kms->signDigest($signingKeyId, hash('sha256', $signingInput, true));

        return $signingInput . '.' . $this->base64Url->encode($signature);
    }

    /**
     * @param array<string, array<array-key, bool|float|int|string|null>|bool|float|int|string|null> $data
     */
    private function encode(array $data): string
    {
        return $this->base64Url->encode(json_encode($data, JSON_THROW_ON_ERROR));
    }
}
