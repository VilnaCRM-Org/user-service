<?php

declare(strict_types=1);

namespace App\User\Domain\Factory;

use App\Shared\Domain\ValueObject\Uuid;
use Symfony\Component\Uid\Factory\UuidFactory as SymfonyUuidFactory;

class UuidFactory
{
    public function __construct(private SymfonyUuidFactory $symfonyUuidFactory)
    {
    }

    public function create(): Uuid
    {
        return new Uuid((string) $this->symfonyUuidFactory->create());
    }

    public function createFromString(string $uuid): Uuid
    {
        return new Uuid($uuid);
    }
}
