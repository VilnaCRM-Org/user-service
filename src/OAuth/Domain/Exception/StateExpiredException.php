<?php

declare(strict_types=1);

namespace App\OAuth\Domain\Exception;

final class StateExpiredException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('OAuth state has expired');
    }
}
