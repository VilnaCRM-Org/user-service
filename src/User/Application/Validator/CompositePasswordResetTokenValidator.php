<?php

declare(strict_types=1);

namespace App\User\Application\Validator;

use App\User\Domain\Contract\PasswordResetTokenValidatorInterface;
use App\User\Domain\Entity\PasswordResetTokenInterface;

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
