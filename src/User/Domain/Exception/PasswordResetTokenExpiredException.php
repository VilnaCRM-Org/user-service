<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class PasswordResetTokenExpiredException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Password reset token has expired');
    }

    #[\Override]
    public function getTranslationTemplate(): string
    {
        return 'error.password-reset-token-expired';
    }
}
