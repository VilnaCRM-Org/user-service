<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use function array_shift;
use function count;
use Override;
use function sprintf;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class TagAwareCacheSpy implements TagAwareCacheInterface
{
    /**
     * @var list<callable(string, callable, ?float, ?array): array|bool|float|int|object|string|null>
     */
    private array $getExpectations = [];

    /**
     * @var list<callable(string): bool|null>
     */
    private array $deleteExpectations = [];

    /**
     * @var list<callable(array): bool|null>
     */
    private array $invalidateTagsExpectations = [];

    public function expectGet(callable $expectation): void
    {
        $this->getExpectations[] = $expectation;
    }

    public function expectDelete(callable $expectation): void
    {
        $this->deleteExpectations[] = $expectation;
    }

    public function expectInvalidateTags(callable $expectation): void
    {
        $this->invalidateTagsExpectations[] = $expectation;
    }

    #[Override]
    public function get(
        string $key,
        callable $callback,
        ?float $beta = null,
        ?array &$metadata = null
    ): mixed {
        $expectation = array_shift($this->getExpectations);

        if ($expectation === null) {
            throw new \LogicException(
                sprintf('Unexpected cache get call for key "%s".', $key)
            );
        }

        return $expectation($key, $callback, $beta, $metadata);
    }

    #[Override]
    public function delete(string $key): bool
    {
        $expectation = array_shift($this->deleteExpectations);

        if ($expectation === null) {
            throw new \LogicException(
                sprintf('Unexpected cache delete call for key "%s".', $key)
            );
        }

        return $expectation($key) ?? true;
    }

    /**
     * @param list<string> $tags
     */
    #[Override]
    public function invalidateTags(array $tags): bool
    {
        $expectation = array_shift($this->invalidateTagsExpectations);

        if ($expectation === null) {
            throw new \LogicException('Unexpected cache tag invalidation call.');
        }

        return $expectation($tags) ?? true;
    }

    public function assertExpectationsMet(): void
    {
        if ($this->getExpectations !== []) {
            throw new \LogicException(
                sprintf('Unmet cache get expectations: %d.', count($this->getExpectations))
            );
        }

        if ($this->deleteExpectations !== []) {
            throw new \LogicException(
                sprintf('Unmet cache delete expectations: %d.', count($this->deleteExpectations))
            );
        }

        if ($this->invalidateTagsExpectations !== []) {
            throw new \LogicException(
                sprintf(
                    'Unmet cache invalidation expectations: %d.',
                    count($this->invalidateTagsExpectations)
                )
            );
        }
    }
}
