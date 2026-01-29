<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\DoctrineType;

use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Domain\ValueObject\UuidInterface;
use App\Shared\Infrastructure\DoctrineType\DomainUuidType;
use App\Shared\Infrastructure\Factory\UuidFactory as UuidFactoryInterface;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Factory\UuidFactory;

final class MongoDBDomainUuidTypeTest extends UnitTestCase
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

    public function testConvertToDatabaseValueWithUuidInterface(): void
    {
        $uuid = $this->transformer->transformFromSymfonyUuid(
            $this->symfonyUuidFactory->create()
        );
        $expectedValue = (string) $uuid;

        $dbValue = $this->domainUuidType->convertToDatabaseValue($uuid);

        $this->assertSame($expectedValue, $dbValue);
    }

    public function testConvertToDatabaseValueWithString(): void
    {
        $uuidString = $this->faker->uuid();

        $dbValue = $this->domainUuidType->convertToDatabaseValue($uuidString);

        $this->assertSame($uuidString, $dbValue);
    }

    public function testConvertToDatabaseValueRejectsInvalidString(): void
    {
        $invalidUuidString = $this->faker->uuid() . '-invalid';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DomainUuidType expects a valid UUID string.');

        $this->domainUuidType->convertToDatabaseValue($invalidUuidString);
    }

    public function testConvertToDatabaseValueWithNull(): void
    {
        $dbValue = $this->domainUuidType->convertToDatabaseValue(null);

        $this->assertNull($dbValue);
    }

    public function testConvertToDatabaseValueWithOtherType(): void
    {
        $uuidString = $this->faker->uuid();
        $objectWithToString = new class($uuidString) {
            public function __construct(private string $uuid)
            {
            }

            public function __toString(): string
            {
                return $this->uuid;
            }
        };

        $dbValue = $this->domainUuidType->convertToDatabaseValue($objectWithToString);

        $this->assertIsString($dbValue);
        $this->assertSame($uuidString, $dbValue);
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

    public function testConvertToDatabaseValueWithCustomUuidInterface(): void
    {
        $invalidUuidString = $this->faker->uuid() . '-invalid';
        $customUuid = new class($invalidUuidString) implements UuidInterface {
            public function __construct(private readonly string $value)
            {
            }

            #[\Override]
            public function __toString(): string
            {
                return $this->value;
            }

            #[\Override]
            public function toBinary(): ?string
            {
                return null;
            }
        };

        $result = $this->domainUuidType->convertToDatabaseValue($customUuid);

        $this->assertSame($invalidUuidString, $result);
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
