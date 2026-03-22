<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\DoctrineType;

use App\OAuth\Infrastructure\DoctrineType\OAuth2GrantType;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ODM\MongoDB\Types\Type;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;

final class OAuth2GrantTypePhpValueTest extends UnitTestCase
{
    public function testConvertsDatabaseArrayToGrants(): void
    {
        $type = $this->getType();
        $grantValue = $this->faker->lexify('grant_????');

        $result = $type->convertToPHPValue([$grantValue]);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(Grant::class, $result[0]);
        $this->assertSame($grantValue, (string) $result[0]);
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

    public function testClosureToMongoContainsExpectedLogic(): void
    {
        $type = $this->getType();
        $expected = implode('', [
            'if ($value === null) { $return = null; } ',
            'elseif (is_array($value)) { $return = []; foreach ($value as $item) { ',
            'if (is_string($item) || (is_object($item) && method_exists($item, "__toString"))) { ',
            '$return[] = (string) $item; } ',
            'else { throw new \InvalidArgumentException(',
            '"OAuth2GrantType expects an array of stringable values."); } } } ',
            'else { throw new \InvalidArgumentException(',
            '"OAuth2GrantType expects an array of stringable values."); }',
        ]);

        $this->assertSame($expected, $type->closureToMongo());
    }

    public function testConvertsDatabaseArrayToMultipleGrants(): void
    {
        $type = $this->getType();
        $grant1 = $this->faker->lexify('grant_????');
        $grant2 = $this->faker->lexify('grant_????');
        $grant3 = $this->faker->lexify('grant_????');

        $result = $type->convertToPHPValue([$grant1, $grant2, $grant3]);

        $this->assertCount(3, $result);
        $this->assertInstanceOf(Grant::class, $result[0]);
        $this->assertInstanceOf(Grant::class, $result[1]);
        $this->assertInstanceOf(Grant::class, $result[2]);
        $this->assertSame($grant1, (string) $result[0]);
        $this->assertSame($grant2, (string) $result[1]);
        $this->assertSame($grant3, (string) $result[2]);
    }

    private function getType(): OAuth2GrantType
    {
        if (!Type::hasType(OAuth2GrantType::NAME)) {
            Type::registerType(OAuth2GrantType::NAME, OAuth2GrantType::class);
        }

        return Type::getType(OAuth2GrantType::NAME);
    }
}
