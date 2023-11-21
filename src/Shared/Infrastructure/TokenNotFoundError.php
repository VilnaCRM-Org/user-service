<?php

namespace App\Shared\Infrastructure;

class TokenNotFoundError extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Confirmation token not found');
    }
}
