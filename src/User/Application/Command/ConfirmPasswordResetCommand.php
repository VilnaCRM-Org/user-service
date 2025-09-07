<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;

final class ConfirmPasswordResetCommand implements CommandInterface
{
    private ConfirmPasswordResetCommandResponse $response;

    public function __construct(
        public readonly string $token,
        public readonly string $newPassword,
        public readonly string $userId,
    ) {
    }

    public function getResponse(): ConfirmPasswordResetCommandResponse
    {
        return $this->response;
    }

    public function setResponse(
        ConfirmPasswordResetCommandResponse $response
    ): void {
        $this->response = $response;
    }
}
