<?php

namespace App\User\Infrastructure\User;

class InvalidPasswordError extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Old password is invalid, try again');
    }
}
