<?php

declare(strict_types=1);

namespace App\OAuth\Application\DTO;

/**
 * @psalm-api
 */
final readonly class InitiateOAuthResponse
{
    public function __construct(
        public string $authorizationUrl,
        public string $state,
        public string $flowBindingToken,
    ) {
    }
}
