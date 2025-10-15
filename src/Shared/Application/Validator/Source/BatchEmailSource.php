<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator\Source;

interface BatchEmailSource
{
    public function extract(mixed $entry): ?string;
}
