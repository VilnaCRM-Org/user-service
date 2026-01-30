<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\DoctrineType;

use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\DoctrineType\DomainUuidType;
use App\Shared\Infrastructure\Factory\UuidFactory as UuidFactoryInterface;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Factory\UuidFactory;

final class MongoDBDomainUuidTypePhpValueTest extends UnitTestCase
{
    private DomainUuidType $domainUuidType;
    private UuidFactory $symfonyUuidFactory;
    private UuidTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $reflection = new \ReflectionClass(DomainUuidType::class);
        $this->domainUuidType = $reflection->newInstanceWithoutConstructor();
        $this->symfonyUuidFactory = new UuidFactory();
        $this->transformer = new UuidTransformer(new UuidFactoryInterface());
    }

    public function testConvertToPHPValueWithString(): void
    {
        $uuidString = $this->faker->uuid();

        $phpValue = $this->domainUuidType->convertToPHPValue($uuidString);

        $this->assertInstanceOf(Uuid::class, $phpValue);
        $this->assertSame($uuidString, (string) $phpValue);
    }

    public function testConvertToPHPValueWithNull(): void
    {
        $phpValue = $this->domainUuidType->convertToPHPValue(null);

        $this->assertNull($phpValue);
    }

    public function testConvertToPHPValueWithUuidInstance(): void
    {
        $uuid = $this->transformer->transformFromSymfonyUuid(
            $this->symfonyUuidFactory->create()
        );

        $phpValue = $this->domainUuidType->convertToPHPValue($uuid);

        $this->assertSame($uuid, $phpValue);
    }

    public function testClosureToMongoContainsExpectedLogic(): void
    {
        $expected = implode('', [
            'if ($value === null) { $return = null; } ',
            'elseif ($value instanceof \App\Shared\Domain\ValueObject\Uuid) { ',
            '$return = (string) $value; } ',
            'else { $return = (string) new \App\Shared\Domain\ValueObject\Uuid(',
            '(string) $value); }',
        ]);

        $this->assertSame($expected, $this->domainUuidType->closureToMongo());
    }

    public function testClosureToPHPContainsExpectedLogic(): void
    {
        $expected = implode('', [
            'if ($value === null) { $return = null; } ',
            'elseif ($value instanceof \App\Shared\Domain\ValueObject\Uuid) { ',
            '$return = $value; } ',
            'else { $return = new \App\Shared\Domain\ValueObject\Uuid((string) $value); }',
        ]);

        $this->assertSame($expected, $this->domainUuidType->closureToPHP());
    }
}
