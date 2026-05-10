<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;

/**
 * Sign out (logout) from current session.
 *
 * AC: FR-13 - Revoke current session and all associated refresh tokens
 */
final readonly class SignOutCommand implements CommandInterface
{
    public function __construct(
        public string $sessionId,
        public string $userId,
    ) {
    }
}
