<?php

declare(strict_types=1);

namespace App\Shared\Application\Normalizer;

use function mb_strtolower;
use function trim;

final class EmailNormalizer
{
    public function normalize(string $email): string
    {
        return mb_strtolower(trim($email), 'UTF-8');
    }
}
