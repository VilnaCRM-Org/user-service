<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\DoctrineType;

use App\OAuth\Infrastructure\DoctrineType\OAuth2ScopeType;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ODM\MongoDB\Types\Type;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use stdClass;

final class OAuth2ScopeTypeTest extends UnitTestCase
{
    public function testConvertsScopesToDatabaseArray(): void
    {
        $type = $this->getType();
        $scopeValue = $this->faker->lexify('scope_????');

        $result = $type->convertToDatabaseValue([new Scope($scopeValue)]);

        $this->assertSame([$scopeValue], $result);
    }

    public function testConvertsStringScopesToDatabaseArray(): void
    {
        $type = $this->getType();
        $scopeValue = $this->faker->lexify('scope_????');

        $result = $type->convertToDatabaseValue([$scopeValue]);

        $this->assertSame([$scopeValue], $result);
    }

    public function testConvertsDatabaseArrayToScopes(): void
    {
        $type = $this->getType();
        $scopeValue = $this->faker->lexify('scope_????');

        $result = $type->convertToPHPValue([$scopeValue]);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(Scope::class, $result[0]);
        $this->assertSame($scopeValue, (string) $result[0]);
    }

    public function testConvertsNullToDatabaseNull(): void
    {
        $type = $this->getType();

        $this->assertNull($type->convertToDatabaseValue(null));
    }

    public function testConvertToDatabaseValueThrowsForNonArray(): void
    {
        $type = $this->getType();

        $this->expectExceptionMessage('OAuth2ScopeType expects an array of stringable values.');

        $type->convertToDatabaseValue($this->faker->word());
    }

    public function testConvertsStringableObjectsToDatabaseArray(): void
    {
        $type = $this->getType();
        $value = $this->faker->lexify('scope_????');
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

        $this->expectExceptionMessage('OAuth2ScopeType expects an array of stringable values.');

        $type->convertToDatabaseValue([new stdClass()]);
    }

    public function testConvertsNullToScopesArray(): void
    {
        $type = $this->getType();

        $this->assertSame([], $type->convertToPHPValue(null));
    }

    public function testConvertToPHPValueThrowsForNonArray(): void
    {
        $type = $this->getType();

        $this->expectExceptionMessage('OAuth2ScopeType expects an array of strings.');

        $type->convertToPHPValue($this->faker->word());
    }

    public function testClosureToMongoReturnsClosureString(): void
    {
        $type = $this->getType();

        $this->assertNotEmpty($type->closureToMongo());
    }

    public function testConvertsMultipleScopesToDatabaseArray(): void
    {
        $type = $this->getType();
        $scope1 = $this->faker->lexify('scope_????');
        $scope2 = $this->faker->lexify('scope_????');
        $scope3 = $this->faker->lexify('scope_????');

        $result = $type->convertToDatabaseValue([
            new Scope($scope1),
            new Scope($scope2),
            new Scope($scope3),
        ]);

        $this->assertSame([$scope1, $scope2, $scope3], $result);
    }

    public function testConvertsMultipleStringScopesToDatabaseArray(): void
    {
        $type = $this->getType();
        $scope1 = $this->faker->lexify('scope_????');
        $scope2 = $this->faker->lexify('scope_????');

        $result = $type->convertToDatabaseValue([$scope1, $scope2]);

        $this->assertSame([$scope1, $scope2], $result);
    }

    public function testConvertsDatabaseArrayToMultipleScopes(): void
    {
        $type = $this->getType();
        $scope1 = $this->faker->lexify('scope_????');
        $scope2 = $this->faker->lexify('scope_????');
        $scope3 = $this->faker->lexify('scope_????');

        $result = $type->convertToPHPValue([$scope1, $scope2, $scope3]);

        $this->assertCount(3, $result);
        $this->assertInstanceOf(Scope::class, $result[0]);
        $this->assertInstanceOf(Scope::class, $result[1]);
        $this->assertInstanceOf(Scope::class, $result[2]);
        $this->assertSame($scope1, (string) $result[0]);
        $this->assertSame($scope2, (string) $result[1]);
        $this->assertSame($scope3, (string) $result[2]);
    }

    public function testConvertsMixedStringableTypesToDatabaseArray(): void
    {
        $type = $this->getType();
        $string1 = $this->faker->lexify('scope_????');
        $scope = new Scope($this->faker->lexify('scope_????'));
        $string2 = $this->faker->lexify('scope_????');

        $result = $type->convertToDatabaseValue([$string1, $scope, $string2]);

        $this->assertCount(3, $result);
        $this->assertSame($string1, $result[0]);
        $this->assertSame((string) $scope, $result[1]);
        $this->assertSame($string2, $result[2]);
    }

    private function getType(): OAuth2ScopeType
    {
        if (!Type::hasType(OAuth2ScopeType::NAME)) {
            Type::registerType(OAuth2ScopeType::NAME, OAuth2ScopeType::class);
        }

        return Type::getType(OAuth2ScopeType::NAME);
    }
}
