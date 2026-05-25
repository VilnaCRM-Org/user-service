<?php

declare(strict_types=1);

namespace App\User\Domain\Collection;

use App\User\Domain\Entity\PasskeyCredential;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, PasskeyCredential>
 */
final readonly class PasskeyCredentialCollection implements
    IteratorAggregate,
    Countable
{
    /** @var list<PasskeyCredential> */
    private array $credentials;

    public function __construct(PasskeyCredential ...$credentials)
    {
        $this->credentials = array_values($credentials);
    }

    /**
     * @psalm-return ArrayIterator<int, PasskeyCredential>
     */
    #[\Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->credentials);
    }

    /**
     * @psalm-return int<0, max>
     */
    #[\Override]
    public function count(): int
    {
        return count($this->credentials);
    }
}
