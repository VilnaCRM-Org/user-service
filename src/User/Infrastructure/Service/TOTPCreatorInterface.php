<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Service;

use OTPHP\TOTPInterface;

interface TOTPCreatorInterface
{
    public function create(?string $secret = null): TOTPInterface;
}
