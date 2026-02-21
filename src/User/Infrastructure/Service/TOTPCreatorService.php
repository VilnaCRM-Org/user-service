<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Service;

use OTPHP\TOTP;
use OTPHP\TOTPInterface;

final class TOTPCreatorService implements TOTPCreatorInterface
{
    #[\Override]
    public function create(?string $secret = null): TOTPInterface
    {
        return TOTP::create($secret);
    }
}
