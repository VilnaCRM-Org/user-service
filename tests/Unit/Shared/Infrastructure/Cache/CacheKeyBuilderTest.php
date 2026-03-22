<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Cache;

use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use PHPUnit\Framework\TestCase;

final class CacheKeyBuilderTest extends TestCase
{
    private CacheKeyBuilder $builder;

    #[\Override]
    protected function setUp(): void
    {
        $this->builder = new CacheKeyBuilder();
    }

    public function testBuildUserKey(): void
    {
        self::assertSame('user.abc123', $this->builder->buildUserKey('abc123'));
    }

    public function testBuildUserEmailKeyHashesLowercasedEmail(): void
    {
        $key = $this->builder->buildUserEmailKey('John@Example.COM');
        $expectedHash = hash('sha256', 'john@example.com');

        self::assertSame('user.email.' . $expectedHash, $key);
    }

    public function testBuildUserCollectionKeyNormalizesAndHashesFilters(): void
    {
        $key = $this->builder->buildUserCollectionKey(['b' => 2, 'a' => 1]);
        $expected = 'user.collection.' . hash(
            'sha256',
            json_encode(['a' => 1, 'b' => 2], JSON_THROW_ON_ERROR)
        );

        self::assertSame($expected, $key);
    }

    public function testBuildSupportsCustomNamespaces(): void
    {
        self::assertSame('foo.bar.baz', $this->builder->build('foo', 'bar', 'baz'));
    }

    public function testHashEmailIsPublic(): void
    {
        $hash = $this->builder->hashEmail('UPPER@example.COM');

        self::assertSame(hash('sha256', 'upper@example.com'), $hash);
    }

    public function testHashEmailIsCaseInsensitive(): void
    {
        $hash1 = $this->builder->hashEmail('test@example.com');
        $hash2 = $this->builder->hashEmail('TEST@EXAMPLE.COM');
        $hash3 = $this->builder->hashEmail('TeSt@ExAmPlE.cOm');

        self::assertSame($hash1, $hash2);
        self::assertSame($hash2, $hash3);
    }
}
