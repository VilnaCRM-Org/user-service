<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class InvalidPasswordException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Old password is invalid');
    }

    #[\Override]
    public function getTranslationTemplate(): string
    {
        return 'error.invalid-password';
    }
}
