<?php

declare(strict_types=1);

namespace App\User\Application\Validator;

use App\User\Domain\Contract\PasswordResetTokenValidatorInterface;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Exception\PasswordResetTokenNotFoundException;

final readonly class TokenExistenceValidator implements
    PasswordResetTokenValidatorInterface
{
    #[\Override]
    public function validate(?PasswordResetTokenInterface $token): void
    {
        if (!$token) {
            throw new PasswordResetTokenNotFoundException();
        }
    }
}
