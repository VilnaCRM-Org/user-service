<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

final readonly class VerifiedPasskeyCredential
{
    public function __construct(
        private string $credentialId,
        private string $credentialRecord
    ) {
    }

    public function getCredentialId(): string
    {
        return $this->credentialId;
    }

    public function getCredentialRecord(): string
    {
        return $this->credentialRecord;
    }
}
