<?php

namespace App\User\Infrastructure\Exceptions;

class TokenNotFoundError extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Token not found');
    }
}
