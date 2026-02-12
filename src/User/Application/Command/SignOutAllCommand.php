<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;

/**
 * Sign out from all sessions (all devices).
 *
 * AC: FR-14 - Revoke all sessions and refresh tokens for user
 */
final readonly class SignOutAllCommand implements CommandInterface
{
    public function __construct(
        public string $userId,
    ) {
    }
}
