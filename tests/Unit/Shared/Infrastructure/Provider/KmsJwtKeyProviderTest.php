<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Provider;

use App\Shared\Application\Provider\CurrentTimestampProviderInterface;
use App\Shared\Infrastructure\Converter\Base64UrlConverter;
use App\Shared\Infrastructure\Converter\KmsPublicKeyConverter;
use App\Shared\Infrastructure\Provider\KmsJwtKeyProvider;
use App\Tests\Shared\Kms\FakeKms;
use App\Tests\Unit\UnitTestCase;
use Aws\Kms\Exception\KmsException;
use InvalidArgumentException;
use RuntimeException;

final class KmsJwtKeyProviderTest extends UnitTestCase
{
    private const CURRENT = 'arn:aws:kms:eu-central-1:123456789012:key/current';
    private const PREVIOUS = 'arn:aws:kms:eu-central-1:123456789012:key/previous';
    private const TTL = 300;
    private const NOW = 1_700_000_000;

    private FakeKms $kms;
    private int $now = self::NOW;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->kms = new FakeKms();
        $this->kms->addKey(self::CURRENT);
        $this->kms->addKey(self::PREVIOUS);
    }

    public function testCurrentKeyComesFromGetPublicKeyOfTheConfiguredKey(): void
    {
        $key = $this->provider()->current();

        self::assertSame(self::CURRENT, $key->keyId());
        self::assertSame($this->kms->publicKeyPem(self::CURRENT), $key->pem());
        self::assertSame(
            ['KeyId' => self::CURRENT],
            array_intersect_key($this->kms->lastCallArguments('GetPublicKey'), ['KeyId' => true])
        );
    }

    public function testCachesPublicKeyUntilTheTtlEnds(): void
    {
        $provider = $this->provider();

        $provider->current();
        $this->now = self::NOW + self::TTL - 1;
        $provider->current();
        self::assertSame(1, $this->kms->countCalls('GetPublicKey'));

        $this->now = self::NOW + self::TTL;
        $provider->current();
        self::assertSame(2, $this->kms->countCalls('GetPublicKey'));
    }

    public function testListsOnlyTheCurrentKeyWithoutAPreviousKey(): void
    {
        $keys = $this->provider()->verificationKeys();

        self::assertSame([self::CURRENT], array_map(static fn ($key) => $key->keyId(), $keys));
        self::assertSame(1, $this->kms->countCalls('GetPublicKey'));
    }

    public function testListsCurrentThenPreviousKeyDuringTheWindow(): void
    {
        $keys = $this->provider(self::PREVIOUS)->verificationKeys();

        self::assertSame(
            [self::CURRENT, self::PREVIOUS],
            array_map(static fn ($key) => $key->keyId(), $keys)
        );
    }

    public function testFindsCurrentAndPreviousKeysByKid(): void
    {
        $provider = $this->provider(self::PREVIOUS);
        [$current, $previous] = $provider->verificationKeys();

        self::assertSame($current, $provider->findByKid($current->kid()));
        self::assertSame($previous, $provider->findByKid($previous->kid()));
        self::assertNotSame($current->kid(), $previous->kid());
    }

    public function testCurrentKeyTokensVerifyEvenIfThePreviousKeyIsUnavailable(): void
    {
        $currentKid = $this->provider()->current()->kid();
        $this->kms->removeKey(self::PREVIOUS);

        $found = $this->provider(self::PREVIOUS)->findByKid($currentKid);

        self::assertSame(self::CURRENT, $found?->keyId());
    }

    public function testUnknownKidIsNotFound(): void
    {
        self::assertNull($this->provider(self::PREVIOUS)->findByKid('unknown-kid'));
    }

    public function testRemovedPreviousKeyIsNoLongerFound(): void
    {
        $previousKid = $this->provider(self::PREVIOUS)->verificationKeys()[1]->kid();

        self::assertNull($this->provider()->findByKid($previousKid));
    }

    public function testKmsErrorsFailClosed(): void
    {
        $this->kms->removeKey(self::CURRENT);

        $this->expectException(KmsException::class);

        $this->provider()->current();
    }

    public function testRejectsKeyThatIsNotASigningKey(): void
    {
        $this->kms->addKey(self::CURRENT, 'ENCRYPT_DECRYPT');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'The JWT KMS key must be a SIGN_VERIFY key that supports RSASSA_PKCS1_V1_5_SHA_256.'
        );

        $this->provider()->current();
    }

    public function testRejectsKeyWithoutRs256SigningAlgorithm(): void
    {
        $this->kms->addKey(self::CURRENT, 'SIGN_VERIFY', ['RSASSA_PSS_SHA_256']);

        $this->expectException(RuntimeException::class);

        $this->provider()->current();
    }

    /**
     * @dataProvider outOfRangeCacheTtls
     */
    public function testRejectsACacheTtlOutsideOneSecondToOneHour(int $ttl): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'JWT_KMS_PUBLIC_KEY_CACHE_TTL must be between 1 and 3600 seconds.'
        );

        $this->provider(cacheTtl: $ttl);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function outOfRangeCacheTtls(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
        yield 'over an hour' => [3601];
    }

    /**
     * @dataProvider boundaryCacheTtls
     */
    public function testAcceptsTheCacheTtlBoundaries(int $ttl): void
    {
        $provider = $this->provider(cacheTtl: $ttl);
        $provider->current();
        $this->now = self::NOW + $ttl;
        $provider->current();

        self::assertSame(2, $this->kms->countCalls('GetPublicKey'));
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function boundaryCacheTtls(): iterable
    {
        yield 'one second' => [1];
        yield 'one hour' => [3600];
    }

    private function provider(string $previous = '', int $cacheTtl = self::TTL): KmsJwtKeyProvider
    {
        $clock = $this->createMock(CurrentTimestampProviderInterface::class);
        $clock->method('currentTimestamp')->willReturnCallback(fn (): int => $this->now);

        return new KmsJwtKeyProvider(
            $this->kms->client(),
            new KmsPublicKeyConverter(new Base64UrlConverter()),
            $clock,
            self::CURRENT,
            $previous,
            $cacheTtl
        );
    }
}
