<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\DoctrineType;

use App\OAuth\Infrastructure\DoctrineType\OAuth2RedirectUriType;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ODM\MongoDB\Types\Type;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;

final class OAuth2RedirectUriTypePhpValueTest extends UnitTestCase
{
    public function testConvertsDatabaseArrayToRedirectUris(): void
    {
        $type = $this->getType();
        $uriValue = $this->faker->url();

        $result = $type->convertToPHPValue([$uriValue]);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(RedirectUri::class, $result[0]);
        $this->assertSame($uriValue, (string) $result[0]);
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

    public function testClosureToMongoContainsExpectedLogic(): void
    {
        $type = $this->getType();
        $expected = implode('', [
            'if ($value === null) { $return = null; } ',
            'elseif (is_array($value)) { $return = []; foreach ($value as $item) { ',
            'if (is_string($item) || (is_object($item) && method_exists($item, "__toString"))) { ',
            '$return[] = (string) $item; } ',
            'else { throw new \InvalidArgumentException(',
            '"OAuth2RedirectUriType expects an array of stringable values."); } } } ',
            'else { throw new \InvalidArgumentException(',
            '"OAuth2RedirectUriType expects an array of stringable values."); }',
        ]);

        $this->assertSame($expected, $type->closureToMongo());
    }

    public function testConvertsDatabaseArrayToMultipleRedirectUris(): void
    {
        $type = $this->getType();
        $uri1 = $this->faker->url();
        $uri2 = $this->faker->url();
        $uri3 = $this->faker->url();

        $result = $type->convertToPHPValue([$uri1, $uri2, $uri3]);

        $this->assertCount(3, $result);
        $this->assertInstanceOf(RedirectUri::class, $result[0]);
        $this->assertInstanceOf(RedirectUri::class, $result[1]);
        $this->assertInstanceOf(RedirectUri::class, $result[2]);
        $this->assertSame($uri1, (string) $result[0]);
        $this->assertSame($uri2, (string) $result[1]);
        $this->assertSame($uri3, (string) $result[2]);
    }

    private function getType(): OAuth2RedirectUriType
    {
        if (!Type::hasType(OAuth2RedirectUriType::NAME)) {
            Type::registerType(OAuth2RedirectUriType::NAME, OAuth2RedirectUriType::class);
        }

        return Type::getType(OAuth2RedirectUriType::NAME);
    }
}
