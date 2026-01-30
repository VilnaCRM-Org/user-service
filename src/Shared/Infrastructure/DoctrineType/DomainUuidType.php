<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\DoctrineType;

use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Domain\ValueObject\UuidInterface;
use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
use Doctrine\ODM\MongoDB\Types\Type;
use InvalidArgumentException;

final class DomainUuidType extends Type
{
    use ClosureToPHP;

    public const NAME = 'domain_uuid';

    #[\Override]
    public function convertToDatabaseValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof UuidInterface) {
            return (string) $value;
        }

        $uuid = new Uuid((string) $value);
        if ($uuid->toBinary() === null) {
            throw new InvalidArgumentException(
                'DomainUuidType expects a valid UUID string.'
            );
        }

        return (string) $uuid;
    }

    #[\Override]
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

    #[\Override]
    public function closureToMongo(): string
    {
        return 'if ($value === null) { $return = null; } '
            . 'elseif ($value instanceof \App\Shared\Domain\ValueObject\UuidInterface) { '
            . '$return = (string) $value; '
            . '} else { '
            . '$return = (string) new \App\Shared\Domain\ValueObject\Uuid((string) $value); '
            . '}';
    }

    #[\Override]
    public function closureToPHP(): string
    {
        return 'if ($value === null) { $return = null; } '
            . 'elseif ($value instanceof \App\Shared\Domain\ValueObject\Uuid) { '
            . '$return = $value; '
            . '} else { '
            . '$return = new \App\Shared\Domain\ValueObject\Uuid((string) $value); '
            . '}';
    }
}
