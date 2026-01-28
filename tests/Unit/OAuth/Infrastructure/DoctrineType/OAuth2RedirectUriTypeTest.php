<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\DoctrineType;

use App\OAuth\Infrastructure\DoctrineType\OAuth2RedirectUriType;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ODM\MongoDB\Types\Type;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;
use stdClass;

final class OAuth2RedirectUriTypeTest extends UnitTestCase
{
    public function testConvertsRedirectUrisToDatabaseArray(): void
    {
        $type = $this->getType();
        $uriValue = $this->faker->url();

        $result = $type->convertToDatabaseValue([new RedirectUri($uriValue)]);

        $this->assertSame([$uriValue], $result);
    }

    public function testConvertsStringRedirectUrisToDatabaseArray(): void
    {
        $type = $this->getType();
        $uriValue = $this->faker->url();

        $result = $type->convertToDatabaseValue([$uriValue]);

        $this->assertSame([$uriValue], $result);
    }

    public function testConvertsDatabaseArrayToRedirectUris(): void
    {
        $type = $this->getType();
        $uriValue = $this->faker->url();

        $result = $type->convertToPHPValue([$uriValue]);

        $this->assertCount(1, $result);
        $this->assertSame($uriValue, (string) $result[0]);
    }

    public function testConvertsNullToDatabaseNull(): void
    {
        $type = $this->getType();

        $this->assertNull($type->convertToDatabaseValue(null));
    }

    public function testConvertToDatabaseValueThrowsForNonArray(): void
    {
        $type = $this->getType();

        $this->expectExceptionMessage(
            'OAuth2RedirectUriType expects an array of stringable values.'
        );

        $type->convertToDatabaseValue($this->faker->word());
    }

    public function testConvertsStringableObjectsToDatabaseArray(): void
    {
        $type = $this->getType();
        $value = $this->faker->url();
        $object = new class($value) {
            public function __construct(private readonly string $value)
            {
            }

            public function __toString(): string
            {
                return $this->value;
            }
        };

        $result = $type->convertToDatabaseValue([$object]);

        $this->assertSame([$value], $result);
    }

    public function testConvertToDatabaseValueThrowsForNonStringableItems(): void
    {
        $type = $this->getType();

        $this->expectExceptionMessage(
            'OAuth2RedirectUriType expects an array of stringable values.'
        );

        $type->convertToDatabaseValue([new stdClass()]);
    }

    public function testConvertsNullToRedirectUriArray(): void
    {
        $type = $this->getType();

        $this->assertSame([], $type->convertToPHPValue(null));
    }

    public function testConvertToPHPValueThrowsForNonArray(): void
    {
        $type = $this->getType();

        $this->expectExceptionMessage('OAuth2RedirectUriType expects an array of strings.');

        $type->convertToPHPValue($this->faker->word());
    }

    public function testClosureToMongoReturnsClosureString(): void
    {
        $type = $this->getType();

        $this->assertNotEmpty($type->closureToMongo());
    }

    private function getType(): OAuth2RedirectUriType
    {
        if (!Type::hasType(OAuth2RedirectUriType::NAME)) {
            Type::registerType(OAuth2RedirectUriType::NAME, OAuth2RedirectUriType::class);
        }

        return Type::getType(OAuth2RedirectUriType::NAME);
    }
}
