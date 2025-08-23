<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class PasswordResetRateLimitExceededException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Password reset rate limit exceeded. Please try again later.');
    }

    public function getTranslationTemplate(): string
    {
        return 'error.password-reset-rate-limit-exceeded';
    }
}
