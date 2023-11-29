<?php

namespace App\User\Infrastructure\Exceptions;

class EmptyIdError extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('User ID cannot be empty');
    }
}