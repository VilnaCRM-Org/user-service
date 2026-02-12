<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;

final class SetupTwoFactorCommand implements CommandInterface
{
    private SetupTwoFactorCommandResponse $response;

    public function __construct(public readonly string $userEmail)
    {
    }

    public function getResponse(): SetupTwoFactorCommandResponse
    {
        return $this->response;
    }

    public function setResponse(SetupTwoFactorCommandResponse $response): void
    {
        $this->response = $response;
    }
}
