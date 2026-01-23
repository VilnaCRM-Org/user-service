<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\DoctrineType;

use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Domain\ValueObject\UuidInterface;
use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
use Doctrine\ODM\MongoDB\Types\Type;

final class DomainUuidType extends Type
{
    use ClosureToPHP;

    public const NAME = 'domain_uuid';

    public function convertToDatabaseValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof UuidInterface) {
            return (string) $value;
        }

        if (is_string($value)) {
            return $value;
        }

        return (string) new Uuid($value);
    }

    public function convertToPHPValue(mixed $value): ?Uuid
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Uuid) {
            return $value;
        }

        return new Uuid((string) $value);
    }

    public function closureToMongo(): string
    {
        return 'if ($value === null) { $return = null; } else { $return = (string) $value; }';
    }

    public function closureToPHP(): string
    {
        return 'if ($value === null) { $return = null; } '
            . 'elseif ($value instanceof \App\Shared\Domain\ValueObject\Uuid) { $return = $value; } '
            . 'else { $return = new \App\Shared\Domain\ValueObject\Uuid((string) $value); }';
    }
}
