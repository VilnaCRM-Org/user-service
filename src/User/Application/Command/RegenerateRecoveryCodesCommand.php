<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;

final class RegenerateRecoveryCodesCommand implements CommandInterface
{
    private RegenerateRecoveryCodesCommandResponse $response;

    public function __construct(
        public readonly string $userEmail,
        public readonly string $currentSessionId,
    ) {
    }

    public function getResponse(): RegenerateRecoveryCodesCommandResponse
    {
        return $this->response;
    }

    public function setResponse(
        RegenerateRecoveryCodesCommandResponse $response
    ): void {
        $this->response = $response;
    }
}
