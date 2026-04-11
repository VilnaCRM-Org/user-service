<?php

declare(strict_types=1);

namespace App\Shared\Application\Provider;

use App\OAuth\Domain\ValueObject\OAuthProvider;

final readonly class OAuthSupportedProvidersProvider
{
    /** @var list<string> */
    private array $supportedProviders;

    /**
     * @param iterable<OAuthProvider> $providers
     */
    public function __construct(iterable $providers)
    {
        $this->supportedProviders = array_map(
            static fn (OAuthProvider $provider): string => (string) $provider,
            iterator_to_array($providers, false),
        );
    }

    /**
     * @return list<string>
     */
    public function supportedProviders(): array
    {
        return $this->supportedProviders;
    }
}
