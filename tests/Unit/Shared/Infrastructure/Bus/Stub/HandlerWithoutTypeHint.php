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
     * @param object $_event
     */
    public function __invoke($_event): void
    {
        // Intentionally untyped for testing purposes
    }
}
