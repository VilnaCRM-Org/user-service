<?php

declare(strict_types=1);

namespace App\User\Application\Validator;

use App\User\Domain\Contract\PasswordResetTokenValidatorInterface;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Exception\PasswordResetTokenExpiredException;

final readonly class TokenExpirationValidator implements
    PasswordResetTokenValidatorInterface
{
    #[\Override]
    public function validate(?PasswordResetTokenInterface $token): void
    {
        if ($token?->isExpired()) {
            throw new PasswordResetTokenExpiredException();
        }
    }
}
