<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class TokenExpiredException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Token has expired');
    }

    public function getTranslationTemplate(): string
    {
        return 'error.token-expired';
    }
}