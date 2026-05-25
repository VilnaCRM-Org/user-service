<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;
use App\User\Domain\Entity\PasskeyCredential;

final class CompletePasskeyRegistrationCommand implements CommandInterface
{
    private PasskeyCredential $response;

    /**
     * @param array<string, scalar|array|null> $credential
     */
    public function __construct(
        public readonly string $challengeId,
        public readonly array $credential,
        public readonly string $label,
        public readonly string $currentUserId
    ) {
    }

    public function getResponse(): PasskeyCredential
    {
        return $this->response;
    }

    public function setResponse(PasskeyCredential $response): void
    {
        $this->response = $response;
    }
}
