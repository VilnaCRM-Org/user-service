<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Stub;

/**
 * Test stub intentionally missing parameter type hint.
 * Used to test InvokeParameterExtractor behavior with untyped parameters.
 *
 * @phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint
 * @phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint
 */
final class HandlerWithoutTypeHint
{
    /**
     * @param object $event
     * @psalm-suppress UnusedParam
     */
    public function __invoke($event): void
    {
        // Intentionally untyped for testing purposes
    }
}
