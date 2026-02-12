<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;

final class DisableTwoFactorCommand implements CommandInterface
{
    public function __construct(
        public readonly string $userEmail,
        public readonly string $twoFactorCode,
    ) {
    }
}
