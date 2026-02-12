<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;

final class ConfirmTwoFactorCommand implements CommandInterface
{
    private ConfirmTwoFactorCommandResponse $response;

    public function __construct(
        public readonly string $userEmail,
        public readonly string $twoFactorCode,
        public readonly string $currentSessionId,
    ) {
    }

    public function getResponse(): ConfirmTwoFactorCommandResponse
    {
        return $this->response;
    }

    public function setResponse(
        ConfirmTwoFactorCommandResponse $response
    ): void {
        $this->response = $response;
    }
}
