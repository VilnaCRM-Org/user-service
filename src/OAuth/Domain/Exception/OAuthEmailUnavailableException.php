<?php

declare(strict_types=1);

namespace App\OAuth\Domain\Exception;

use RuntimeException;

final class OAuthEmailUnavailableException extends RuntimeException
{
    public function __construct(string $provider)
    {
        parent::__construct(
            sprintf(
                'OAuth provider %s did not return an email address',
                $provider,
            )
        );
    }
}
