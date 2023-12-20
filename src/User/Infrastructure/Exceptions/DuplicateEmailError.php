<?php

namespace App\User\Infrastructure\Exceptions;

class DuplicateEmailError extends \RuntimeException
{
    public function __construct(string $email)
    {
        parent::__construct($email.' address is already registered. Please use a different email address or try logging in.');
    }
}
