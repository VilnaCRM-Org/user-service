<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Exception\PasswordResetTokenNotFoundException;
use App\User\Domain\Service\PasswordResetTokenValidatorInterface;

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
