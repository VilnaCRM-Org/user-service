<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;

final class SignInCommand implements CommandInterface
{
    public function __construct(
        public readonly string $email,
        #[\SensitiveParameter]
        public readonly string $password,
        public readonly bool $rememberMe,
        public readonly string $ipAddress,
        public readonly string $userAgent,
    ) {
    }
}
