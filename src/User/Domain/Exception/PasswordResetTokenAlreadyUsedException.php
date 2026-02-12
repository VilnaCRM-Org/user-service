<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class PasswordResetTokenAlreadyUsedException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Password reset token has already been used');
    }

    /**
     * @return string
     *
     * @psalm-return 'error.password-reset-token-already-used'
     */
    #[\Override]
    public function getTranslationTemplate(): string
    {
        return 'error.password-reset-token-already-used';
    }
}
