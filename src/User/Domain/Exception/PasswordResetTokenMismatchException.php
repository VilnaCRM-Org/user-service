<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class PasswordResetTokenMismatchException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            'Password reset token does not belong to the specified user'
        );
    }

    #[\Override]
    public function getTranslationTemplate(): string
    {
        return 'error.password-reset-token-mismatch';
    }
}
