<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class TokenNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Token not found');
    }

    public function getTranslationTemplate(): string
    {
        return 'error.token-not-found';
    }
}
