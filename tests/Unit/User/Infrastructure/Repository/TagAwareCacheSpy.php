<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use function array_shift;
use function count;
use Override;
use PHPUnit\Framework\Assert;
use function sprintf;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class TagAwareCacheSpy implements TagAwareCacheInterface
{
    /**
     * @var list<
     *     callable(string, callable, ?float, ?array): array|bool|float|int|object|string|null
     * >
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
    ): array|bool|float|int|object|string|null {
        $expectation = $this->takeExpectation(
            $this->getExpectations,
            sprintf('Unexpected cache get call for key "%s".', $key)
        );

        return $expectation($key, $callback, $beta, $metadata);
    }

    #[Override]
    public function delete(string $key): bool
    {
        $expectation = $this->takeExpectation(
            $this->deleteExpectations,
            sprintf('Unexpected cache delete call for key "%s".', $key)
        );

        return $expectation($key) ?? true;
    }

    /**
     * @param list<string> $tags
     */
    #[Override]
    public function invalidateTags(array $tags): bool
    {
        $expectation = $this->takeExpectation(
            $this->invalidateTagsExpectations,
            'Unexpected cache tag invalidation call.'
        );

        return $expectation($tags) ?? true;
    }

    public function assertExpectationsMet(): void
    {
        $this->assertQueueEmpty($this->getExpectations, 'Unmet cache get expectations: %d.');
        $this->assertQueueEmpty(
            $this->deleteExpectations,
            'Unmet cache delete expectations: %d.'
        );
        $this->assertQueueEmpty(
            $this->invalidateTagsExpectations,
            'Unmet cache invalidation expectations: %d.'
        );
    }

    /**
     * @param array<int, callable> $expectations
     */
    private function takeExpectation(array &$expectations, string $message): callable
    {
        $expectation = array_shift($expectations);

        if ($expectation === null) {
            Assert::fail($message);
        }

        return $expectation;
    }

    /**
     * @param array<int, callable> $expectations
     */
    private function assertQueueEmpty(array $expectations, string $message): void
    {
        if ($expectations !== []) {
            Assert::fail(sprintf($message, count($expectations)));
        }
    }
}
