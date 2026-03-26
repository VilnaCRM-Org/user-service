<?php

declare(strict_types=1);

namespace App\OAuth\Application\Provider;

use App\OAuth\Application\Collection\OAuthProviderCollection;
use App\OAuth\Domain\Exception\UnsupportedProviderException;
use LogicException;

final class OAuthProviderRegistry
{
    private OAuthProviderCollection $providers;

    /**
     * @param iterable<OAuthProviderInterface> $providers
     */
    public function __construct(iterable $providers)
    {
        $registered = [];
        foreach ($providers as $provider) {
            $key = (string) $provider->getProvider();
            if (isset($registered[$key])) {
                throw new LogicException(sprintf(
                    'Duplicate OAuth provider registration: %s',
                    $key
                ));
            }
            $registered[$key] = $provider;
        }
        $this->providers = new OAuthProviderCollection(...$registered);
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
