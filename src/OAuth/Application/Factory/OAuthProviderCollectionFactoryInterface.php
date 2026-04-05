<?php

declare(strict_types=1);

namespace App\OAuth\Application\Factory;

use App\OAuth\Application\Collection\OAuthProviderCollection;
use App\OAuth\Application\Provider\OAuthProviderInterface;

interface OAuthProviderCollectionFactoryInterface
{
    /**
     * @param iterable<OAuthProviderInterface> $providers
     */
    public function create(iterable $providers): OAuthProviderCollection;
}
