<?php

declare(strict_types=1);

namespace App\OAuth\Application\Provider;

use App\OAuth\Domain\Exception\UnsupportedProviderException;

final class OAuthProviderRegistry
{
    /** @var array<string, OAuthProviderInterface> */
    private array $providers;

    /**
     * @param iterable<OAuthProviderInterface> $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = [];
        foreach ($providers as $provider) {
            $key = (string) $provider->getProvider();
            if (isset($this->providers[$key])) {
                throw new \LogicException(sprintf(
                    'Duplicate OAuth provider registration: %s',
                    $key
                ));
            }
            $this->providers[$key] = $provider;
        }
    }

    public function get(string $provider): OAuthProviderInterface
    {
        return $this->providers[$provider]
            ?? throw new UnsupportedProviderException($provider);
    }

    /**
     * @return array<string>
     */
    public function supportedProviders(): array
    {
        return array_keys($this->providers);
    }
}
