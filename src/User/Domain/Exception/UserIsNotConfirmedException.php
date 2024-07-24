<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class UserIsNotConfirmedException extends DomainException
{
    public function __construct()
    {
        parent::__construct('User is not confirmed');
    }

    public function getTranslationTemplate(): string
    {
        return 'error.user-is-not-confirmed';
    }
}
