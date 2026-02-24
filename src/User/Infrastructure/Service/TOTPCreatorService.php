<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Service;

use OTPHP\TOTP;
use OTPHP\TOTPInterface;

final class TOTPCreatorService implements TOTPCreatorInterface
{
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const SECRET_LENGTH = 32;

    #[\Override]
    public function create(?string $secret = null): TOTPInterface
    {
        return new TOTP($secret ?? $this->generateSecret());
    }

    private function generateSecret(): string
    {
        $secret = '';
        for ($i = 0; $i < self::SECRET_LENGTH; $i++) {
            $secret .= self::BASE32_CHARS[random_int(0, 31)];
        }

        return $secret;
    }
}
