<?php

declare(strict_types=1);

namespace App\OAuth\Application\Collection;

use App\OAuth\Application\Provider\OAuthProviderInterface;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<string, OAuthProviderInterface>
 */
final readonly class OAuthProviderCollection implements
    IteratorAggregate,
    Countable
{
    /** @var array<string, OAuthProviderInterface> */
    private array $providers;

    public function __construct(OAuthProviderInterface ...$providers)
    {
        $indexed = [];
        foreach ($providers as $provider) {
            $indexed[(string) $provider->getProvider()] = $provider;
        }
        $this->providers = $indexed;
    }

    public function get(string $key): ?OAuthProviderInterface
    {
        return $this->providers[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->providers[$key]);
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->providers);
    }

    /**
     * @psalm-return ArrayIterator<string, OAuthProviderInterface>
     */
    #[\Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->providers);
    }

    /**
     * @psalm-return int<0, max>
     */
    #[\Override]
    public function count(): int
    {
        return count($this->providers);
    }
}
