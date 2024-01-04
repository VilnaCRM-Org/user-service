<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Exception;

class InvalidPasswordException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Old password is invalid');
    }
}
