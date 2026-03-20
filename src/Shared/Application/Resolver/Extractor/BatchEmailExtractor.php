<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver\Extractor;

interface BatchEmailExtractor
{
    public function extract(mixed $entry): ?string;
}
