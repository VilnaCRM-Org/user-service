<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;

final class CompleteTwoFactorCommand implements CommandInterface
{
    private CompleteTwoFactorCommandResponse $response;

    public function __construct(
        public readonly string $pendingSessionId,
        public readonly string $twoFactorCode,
        public readonly string $ipAddress,
        public readonly string $userAgent,
    ) {
    }

    public function getResponse(): CompleteTwoFactorCommandResponse
    {
        return $this->response;
    }

    public function setResponse(CompleteTwoFactorCommandResponse $response): void
    {
        $this->response = $response;
    }
}
