<?php

declare(strict_types=1);

namespace App\Shared\Application\Factory;

use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\Shared\Domain\Collection\OAuthProviderNameCollection;

final readonly class OAuthProviderNameCollectionFactory
{
    public function __construct(private bool $enabled)
    {
    }

    /**
     * @param iterable<OAuthProvider> $providers
     */
    public function create(iterable $providers): OAuthProviderNameCollection
    {
        return new OAuthProviderNameCollection($this->enabled ? $providers : []);
    }
}
