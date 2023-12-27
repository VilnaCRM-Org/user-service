<?php

namespace App\User\Infrastructure\Exception;

class TokenNotFoundException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Token not found');
    }
}
