<?php

declare(strict_types=1);

namespace App\OAuth\Application\Provider;

use App\OAuth\Application\Collection\OAuthProviderCollection;
use App\OAuth\Domain\Exception\UnsupportedProviderException;

final class OAuthProviderRegistry
{
    public function __construct(
        private OAuthProviderCollection $providers,
    ) {
    }

    public function get(string $provider): OAuthProviderInterface
    {
        return $this->providers->get($provider)
            ?? throw new UnsupportedProviderException($provider);
    }

    /**
     * @return list<string>
     */
    public function supportedProviders(): array
    {
        return $this->providers->keys();
    }
}
