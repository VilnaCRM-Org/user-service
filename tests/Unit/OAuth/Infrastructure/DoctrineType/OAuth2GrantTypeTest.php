<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\DoctrineType;

use App\OAuth\Infrastructure\DoctrineType\OAuth2GrantType;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ODM\MongoDB\Types\Type;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use stdClass;

final class OAuth2GrantTypeTest extends UnitTestCase
{
    public function testConvertsGrantsToDatabaseArray(): void
    {
        $type = $this->getType();
        $grantValue = $this->faker->lexify('grant_????');

        $result = $type->convertToDatabaseValue([new Grant($grantValue)]);

        $this->assertSame([$grantValue], $result);
    }

    public function testConvertsStringGrantsToDatabaseArray(): void
    {
        $type = $this->getType();
        $grantValue = $this->faker->lexify('grant_????');

        $result = $type->convertToDatabaseValue([$grantValue]);

        $this->assertSame([$grantValue], $result);
    }

    public function testConvertsDatabaseArrayToGrants(): void
    {
        $type = $this->getType();
        $grantValue = $this->faker->lexify('grant_????');

        $result = $type->convertToPHPValue([$grantValue]);

        $this->assertCount(1, $result);
        $this->assertSame($grantValue, (string) $result[0]);
    }

    public function testConvertsNullToDatabaseNull(): void
    {
        $type = $this->getType();

        $this->assertNull($type->convertToDatabaseValue(null));
    }

    public function testConvertToDatabaseValueThrowsForNonArray(): void
    {
        $type = $this->getType();

        $this->expectExceptionMessage('OAuth2GrantType expects an array of stringable values.');

        $type->convertToDatabaseValue($this->faker->word());
    }

    public function testConvertsStringableObjectsToDatabaseArray(): void
    {
        $type = $this->getType();
        $value = $this->faker->lexify('grant_????');
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

        $this->expectExceptionMessage('OAuth2GrantType expects an array of stringable values.');

        $type->convertToDatabaseValue([new stdClass()]);
    }

    public function testConvertsNullToGrantsArray(): void
    {
        $type = $this->getType();

        $this->assertSame([], $type->convertToPHPValue(null));
    }

    public function testConvertToPHPValueThrowsForNonArray(): void
    {
        $type = $this->getType();

        $this->expectExceptionMessage('OAuth2GrantType expects an array of strings.');

        $type->convertToPHPValue($this->faker->word());
    }

    public function testClosureToMongoReturnsClosureString(): void
    {
        $type = $this->getType();

        $this->assertNotEmpty($type->closureToMongo());
    }

    private function getType(): OAuth2GrantType
    {
        if (!Type::hasType(OAuth2GrantType::NAME)) {
            Type::registerType(OAuth2GrantType::NAME, OAuth2GrantType::class);
        }

        return Type::getType(OAuth2GrantType::NAME);
    }
}
