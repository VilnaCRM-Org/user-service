<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;
use App\User\Application\DTO\PasskeyOptionsResult;

final class StartPasskeyRegistrationCommand implements CommandInterface
{
    private PasskeyOptionsResult $response;

    public function __construct(
        public readonly string $userId
    ) {
    }

    public function getResponse(): PasskeyOptionsResult
    {
        return $this->response;
    }

    public function setResponse(PasskeyOptionsResult $response): void
    {
        $this->response = $response;
    }
}
