<?php

declare(strict_types=1);

namespace App\User\Application\Exception;

final class InvalidPasswordException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Old password is invalid');
    }
}
