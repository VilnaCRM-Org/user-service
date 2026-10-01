<?php

declare(strict_types=1);

namespace App\Shared\Application\Provider;

interface KmsEndpointProviderInterface
{
    /**
     * The endpoint URL the application's KMS client calls.
     */
    public function endpoint(): string;
}
