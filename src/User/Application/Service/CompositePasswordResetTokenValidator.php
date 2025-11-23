<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Service\PasswordResetTokenValidatorInterface;

final readonly class CompositePasswordResetTokenValidator implements
    PasswordResetTokenValidatorInterface
{
    /**
     * @param array<PasswordResetTokenValidatorInterface> $validators
     */
    public function __construct(
        private array $validators = []
    ) {
    }

    #[\Override]
    public function validate(?PasswordResetTokenInterface $token): void
    {
        foreach ($this->validators as $validator) {
            $validator->validate($token);
        }
    }
}
