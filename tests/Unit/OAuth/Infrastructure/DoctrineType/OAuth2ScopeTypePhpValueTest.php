<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\DoctrineType;

use App\OAuth\Infrastructure\DoctrineType\OAuth2ScopeType;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ODM\MongoDB\Types\Type;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;

final class OAuth2ScopeTypePhpValueTest extends UnitTestCase
{
    public function testConvertsDatabaseArrayToScopes(): void
    {
        $type = $this->getType();
        $scopeValue = $this->faker->lexify('scope_????');

        $result = $type->convertToPHPValue([$scopeValue]);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(Scope::class, $result[0]);
        $this->assertSame($scopeValue, (string) $result[0]);
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

    public function testClosureToMongoContainsExpectedLogic(): void
    {
        $type = $this->getType();
        $expected = implode('', [
            'if ($value === null) { $return = null; } ',
            'elseif (is_array($value)) { $return = []; foreach ($value as $item) { ',
            'if (is_string($item) || (is_object($item) && method_exists($item, "__toString"))) { ',
            '$return[] = (string) $item; } ',
            'else { throw new \InvalidArgumentException(',
            '"OAuth2ScopeType expects an array of stringable values."); } } } ',
            'else { throw new \InvalidArgumentException(',
            '"OAuth2ScopeType expects an array of stringable values."); }',
        ]);

        $this->assertSame($expected, $type->closureToMongo());
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

    private function getType(): OAuth2ScopeType
    {
        if (!Type::hasType(OAuth2ScopeType::NAME)) {
            Type::registerType(OAuth2ScopeType::NAME, OAuth2ScopeType::class);
        }

        return Type::getType(OAuth2ScopeType::NAME);
    }
}
