<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\DTO\VerifiedPasskeyCredential;
use App\User\Domain\Entity\PasskeyCredential;
use DateTimeImmutable;

use function trim;

final readonly class PasskeyCredentialFactory
{
    private const DEFAULT_LABEL = 'Passkey';

    public function __construct(
        private IdFactoryInterface $idFactory
    ) {
    }

    public function create(
        string $userId,
        VerifiedPasskeyCredential $verifiedCredential,
        string $label,
        DateTimeImmutable $createdAt
    ): PasskeyCredential {
        return new PasskeyCredential(
            $this->idFactory->create(),
            $userId,
            $verifiedCredential->getCredentialId(),
            $verifiedCredential->getCredentialRecord(),
            $this->resolveLabel($label),
            $createdAt
        );
    }

    private function resolveLabel(string $label): string
    {
        $trimmedLabel = trim($label);

        return $trimmedLabel === '' ? self::DEFAULT_LABEL : $trimmedLabel;
    }
}
