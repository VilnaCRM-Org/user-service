<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;

final class CompleteTwoFactorCommand implements CommandInterface
{
    public function __construct(
        public readonly string $pendingSessionId,
        public readonly string $twoFactorCode,
        public readonly string $ipAddress,
        public readonly string $userAgent,
    ) {
    }
}
