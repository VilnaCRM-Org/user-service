<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;
use App\User\Application\DTO\PasskeyOptionsResult;

final class StartPasskeySignUpCommand implements CommandInterface
{
    private PasskeyOptionsResult $response;

    public function __construct(
        public readonly string $email,
        public readonly string $initials,
        public readonly string $displayName
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
