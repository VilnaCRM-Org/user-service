<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class UserNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct('User not found');
    }

    public function getTranslationTemplate(): string
    {
        return 'error.user-not-found';
    }
}
