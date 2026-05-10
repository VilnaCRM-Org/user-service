<?php

declare(strict_types=1);

namespace App\OAuth\Domain\Exception;

use RuntimeException;

final class OAuthProviderException extends RuntimeException
{
    public function __construct(
        string $provider,
        string $message,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf('OAuth provider %s error: %s', $provider, $message),
            0,
            $previous,
        );
    }
}
