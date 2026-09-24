<?php

declare(strict_types=1);

namespace App\OAuth\Application\Factory;

use App\OAuth\Application\Collection\OAuthProviderCollection;

use function iterator_to_array;

final readonly class OAuthProviderCollectionFactory implements
    OAuthProviderCollectionFactoryInterface
{
    public function __construct(private bool $enabled)
    {
    }

    /**
     * @param iterable<\App\OAuth\Application\Provider\OAuthProviderInterface> $providers
     */
    #[\Override]
    public function create(iterable $providers): OAuthProviderCollection
    {
        if (!$this->enabled) {
            return new OAuthProviderCollection();
        }

        return new OAuthProviderCollection(...iterator_to_array($providers, false));
    }
}
