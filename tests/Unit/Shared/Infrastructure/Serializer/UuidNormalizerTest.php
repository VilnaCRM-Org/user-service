<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Serializer;

use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Domain\ValueObject\UuidInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as UuidFactoryInterface;
use App\Shared\Infrastructure\Serializer\UuidNormalizer;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use InvalidArgumentException;
use Symfony\Component\Uid\Factory\UuidFactory;

final class UuidNormalizerTest extends UnitTestCase
{
    private UuidNormalizer $normalizer;
    private UuidFactory $symfonyUuidFactory;
    private UuidTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = new UuidNormalizer();
        $this->symfonyUuidFactory = new UuidFactory();
        $this->transformer = new UuidTransformer(new UuidFactoryInterface());
    }

    public function testToDatabaseValueWithUuidInterface(): void
    {
        $uuid = $this->transformer->transformFromSymfonyUuid(
            $this->symfonyUuidFactory->create()
        );
        $expectedValue = (string) $uuid;

        $result = $this->normalizer->toDatabaseValue($uuid);

        $this->assertSame($expectedValue, $result);
    }

    public function testToDatabaseValueWithString(): void
    {
        $uuidString = $this->faker->uuid();

        $result = $this->normalizer->toDatabaseValue($uuidString);

        $this->assertSame($uuidString, $result);
    }

    public function testToDatabaseValueRejectsInvalidString(): void
    {
        $invalidUuidString = $this->faker->uuid() . '-invalid';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('DomainUuidType expects a valid UUID string.');

        $this->normalizer->toDatabaseValue($invalidUuidString);
    }

    public function testToDatabaseValueWithCustomUuidInterfaceReturnsEarly(): void
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

            /**
             * @return null
             */
            #[\Override]
            public function toBinary(): ?string
            {
                return null;
            }
        };

        $result = $this->normalizer->toDatabaseValue($customUuid);

        $this->assertSame($invalidUuidString, $result);
    }

    public function testToUuidWithString(): void
    {
        $uuidString = $this->faker->uuid();

        $result = $this->normalizer->toUuid($uuidString);

        $this->assertInstanceOf(Uuid::class, $result);
        $this->assertSame($uuidString, (string) $result);
    }

    public function testToUuidWithUuidInstance(): void
    {
        $uuid = $this->transformer->transformFromSymfonyUuid(
            $this->symfonyUuidFactory->create()
        );

        $result = $this->normalizer->toUuid($uuid);

        $this->assertSame($uuid, $result);
    }
}
