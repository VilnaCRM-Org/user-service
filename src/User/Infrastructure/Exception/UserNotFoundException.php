<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Exception;

final class UserNotFoundException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('User not found');
    }
}
