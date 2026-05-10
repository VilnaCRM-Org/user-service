<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\DoctrineType;

use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\OAuth\Infrastructure\DoctrineType\OAuthProviderType;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ODM\MongoDB\Types\Type;

final class OAuthProviderTypeTest extends UnitTestCase
{
    public function testConvertsOAuthProviderToDatabaseString(): void
    {
        $type = $this->getType();
        $value = $this->faker->word();
        $provider = new OAuthProvider($value);

        $result = $type->convertToDatabaseValue($provider);

        $this->assertSame($value, $result);
    }

    public function testConvertsStringToDatabaseString(): void
    {
        $type = $this->getType();
        $value = $this->faker->word();

        $result = $type->convertToDatabaseValue($value);

        $this->assertSame($value, $result);
    }

    public function testConvertsNullToDatabaseNull(): void
    {
        $type = $this->getType();

        $this->assertNull($type->convertToDatabaseValue(null));
    }

    public function testConvertToDatabaseValueThrowsForInvalidType(): void
    {
        $type = $this->getType();

        $this->expectExceptionMessage(
            'OAuthProviderType expects an OAuthProvider or string.'
        );

        $type->convertToDatabaseValue($this->faker->randomNumber());
    }

    public function testConvertsDatabaseStringToOAuthProvider(): void
    {
        $type = $this->getType();
        $value = $this->faker->word();

        $result = $type->convertToPHPValue($value);

        $this->assertInstanceOf(OAuthProvider::class, $result);
        $this->assertSame($value, $result->value);
    }

    public function testConvertsNullToPhpNull(): void
    {
        $type = $this->getType();

        $this->assertNull($type->convertToPHPValue(null));
    }

    public function testReturnsExistingOAuthProviderInstance(): void
    {
        $type = $this->getType();
        $value = $this->faker->word();
        $provider = new OAuthProvider($value);

        $result = $type->convertToPHPValue($provider);

        $this->assertSame($provider, $result);
    }

    public function testConvertToPhpValueThrowsForInvalidType(): void
    {
        $type = $this->getType();

        $this->expectExceptionMessage(
            'OAuthProviderType expects an OAuthProvider or string.'
        );

        $type->convertToPHPValue($this->faker->randomNumber());
    }

    public function testClosureToMongoReturnsNonEmptyString(): void
    {
        $type = $this->getType();

        $this->assertNotEmpty($type->closureToMongo());
    }

    public function testClosureToPhpReturnsNonEmptyString(): void
    {
        $type = $this->getType();

        $this->assertNotEmpty($type->closureToPHP());
    }

    private function getType(): OAuthProviderType
    {
        if (!Type::hasType(OAuthProviderType::NAME)) {
            Type::registerType(
                OAuthProviderType::NAME,
                OAuthProviderType::class
            );
        }

        return Type::getType(OAuthProviderType::NAME);
    }
}
