<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class TokenNotFoundException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Token not found');
    }
}
