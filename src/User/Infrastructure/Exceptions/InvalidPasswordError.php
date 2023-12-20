<?php

namespace App\User\Infrastructure\Exceptions;

class InvalidPasswordError extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Old password is invalid');
    }
}
