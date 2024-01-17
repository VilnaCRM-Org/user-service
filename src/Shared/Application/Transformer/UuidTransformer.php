<?php

declare(strict_types=1);

namespace App\Shared\Application\Transformer;

use App\Shared\Domain\ValueObject\Uuid;
use Symfony\Component\Uid\AbstractUid as SymfonyUuid;

class UuidTransformer
{
    public function transformFromSymfonyUuid(SymfonyUuid $symfonyUuid): Uuid
    {
        return new Uuid((string) $symfonyUuid);
    }

    public function transformFromString(string $stringUuid): Uuid
    {
        return new Uuid($stringUuid);
    }
}
