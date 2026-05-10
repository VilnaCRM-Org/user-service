<?php

declare(strict_types=1);

namespace App\Shared\Application\Provider;

use App\Shared\Domain\Collection\OAuthProviderNameCollection;

final readonly class OAuthSupportedProvidersProvider
{
    /** @var list<string> */
    private array $supportedProviders;

    public function __construct(OAuthProviderNameCollection $providers)
    {
        $this->supportedProviders = $providers->names();
    }

    /**
     * @return list<string>
     */
    public function supportedProviders(): array
    {
        return $this->supportedProviders;
    }
}
