<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;

final class RequestPasswordResetCommand implements CommandInterface
{
    private RequestPasswordResetCommandResponse $response;

    public function __construct(
        public readonly string $email,
    ) {
    }

    public function getResponse(): RequestPasswordResetCommandResponse
    {
        return $this->response;
    }

    public function setResponse(
        RequestPasswordResetCommandResponse $response
    ): void {
        $this->response = $response;
    }
}
