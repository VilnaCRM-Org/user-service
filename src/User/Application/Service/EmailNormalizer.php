<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use function mb_strtolower;
use function trim;

final class EmailNormalizer
{
    public function normalize(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
