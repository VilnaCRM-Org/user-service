<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class PasswordResetTokenNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Password reset token not found');
    }

    public function getTranslationTemplate(): string
    {
        return 'error.password-reset-token-not-found';
    }
}
