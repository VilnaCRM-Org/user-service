<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\DoctrineType;

use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Domain\ValueObject\UuidInterface;
use InvalidArgumentException;

final class UuidNormalizer
{
    public function toDatabaseValue(
        array|bool|float|int|object|string $value
    ): string {
        if ($value instanceof UuidInterface) {
            return (string) $value;
        }

        $uuid = new Uuid((string) $value);
        if ($uuid->toBinary() === null) {
            throw new InvalidArgumentException('DomainUuidType expects a valid UUID string.');
        }

        return (string) $uuid;
    }

    public function toUuid(array|bool|float|int|object|string $value): Uuid
    {
        if ($value instanceof Uuid) {
            return $value;
        }

        return new Uuid((string) $value);
    }
}
