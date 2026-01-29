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
        $this->assertInstanceOf(Grant::class, $result[0]);
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

    public function testClosureToMongoExecutesCorrectly(): void
    {
        $type = $this->getType();
        $closureString = $type->closureToMongo();

        // Test with null
        $value = null;
        $return = $this->executeClosure($closureString, $value);
        $this->assertNull($return);

        // Test with array of strings
        $value = ['authorization_code', 'client_credentials'];
        $return = $this->executeClosure($closureString, $value);
        $this->assertSame(['authorization_code', 'client_credentials'], $return);

        // Test with array of Grant objects
        $grant1 = new Grant('authorization_code');
        $grant2 = new Grant('refresh_token');
        $value = [$grant1, $grant2];
        $return = $this->executeClosure($closureString, $value);
        $this->assertSame(['authorization_code', 'refresh_token'], $return);

        // Test with invalid non-array value
        $value = 'not-an-array';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('OAuth2GrantType expects an array of stringable values.');
        $this->executeClosure($closureString, $value);
    }

    private function executeClosure(string $closureString, mixed $value): mixed
    {
        /** @psalm-suppress ForbiddenCode */
        eval($closureString);
        /** @psalm-suppress UndefinedVariable */
        return $return;
    }

    public function testConvertsMultipleGrantsToDatabaseArray(): void
    {
        $type = $this->getType();
        $grant1 = $this->faker->lexify('grant_????');
        $grant2 = $this->faker->lexify('grant_????');
        $grant3 = $this->faker->lexify('grant_????');

        $result = $type->convertToDatabaseValue([
            new Grant($grant1),
            new Grant($grant2),
            new Grant($grant3),
        ]);

        $this->assertSame([$grant1, $grant2, $grant3], $result);
    }

    public function testConvertsMultipleStringGrantsToDatabaseArray(): void
    {
        $type = $this->getType();
        $grant1 = $this->faker->lexify('grant_????');
        $grant2 = $this->faker->lexify('grant_????');

        $result = $type->convertToDatabaseValue([$grant1, $grant2]);

        $this->assertSame([$grant1, $grant2], $result);
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

    public function testConvertsMixedStringableTypesToDatabaseArray(): void
    {
        $type = $this->getType();
        $string1 = $this->faker->lexify('grant_????');
        $grant = new Grant($this->faker->lexify('grant_????'));
        $string2 = $this->faker->lexify('grant_????');

        $result = $type->convertToDatabaseValue([$string1, $grant, $string2]);

        $this->assertCount(3, $result);
        $this->assertSame($string1, $result[0]);
        $this->assertSame((string) $grant, $result[1]);
        $this->assertSame($string2, $result[2]);
    }

    private function getType(): OAuth2GrantType
    {
        if (!Type::hasType(OAuth2GrantType::NAME)) {
            Type::registerType(OAuth2GrantType::NAME, OAuth2GrantType::class);
        }

        return Type::getType(OAuth2GrantType::NAME);
    }
}
