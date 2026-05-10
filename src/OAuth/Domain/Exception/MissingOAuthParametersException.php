<?php

declare(strict_types=1);

namespace App\OAuth\Domain\Exception;

use RuntimeException;

final class MissingOAuthParametersException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'Missing required OAuth parameters: code, state, or flow-binding cookie'
        );
    }
}
