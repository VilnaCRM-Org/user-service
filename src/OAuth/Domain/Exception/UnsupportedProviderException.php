<?php

declare(strict_types=1);

namespace App\OAuth\Domain\Exception;

use RuntimeException;

final class UnsupportedProviderException extends RuntimeException
{
    public function __construct(string $provider)
    {
        parent::__construct(
            sprintf('Unsupported OAuth provider: %s', $provider)
        );
    }
}
