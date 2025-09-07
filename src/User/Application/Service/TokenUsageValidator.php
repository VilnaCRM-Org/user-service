<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Exception\PasswordResetTokenAlreadyUsedException;
use App\User\Domain\Service\PasswordResetTokenValidatorInterface;

final readonly class TokenUsageValidator implements
    PasswordResetTokenValidatorInterface
{
    public function validate(?PasswordResetTokenInterface $token): void
    {
        if ($token && $token->isUsed()) {
            throw new PasswordResetTokenAlreadyUsedException();
        }
    }
}
