<?php

declare(strict_types=1);

namespace App\Shared\Application\Collection;

use App\OAuth\Domain\ValueObject\OAuthProvider;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, OAuthProvider>
 */
final readonly class OAuthProviderNameCollection implements
    IteratorAggregate,
    Countable
{
    /** @var list<OAuthProvider> */
    private array $providers;

    /**
     * @param iterable<OAuthProvider> $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = iterator_to_array($providers, false);
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_map(
            static fn (OAuthProvider $provider): string => (string) $provider,
            $this->providers,
        );
    }

    /**
     * @psalm-return ArrayIterator<int, OAuthProvider>
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
