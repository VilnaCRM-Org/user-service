<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

interface UuidInterface
{
    public function toBinary(): string;

    public function __toString(): string;
}
