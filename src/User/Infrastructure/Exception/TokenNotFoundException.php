<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Exception;

final class TokenNotFoundException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Token not found');
    }
}