<?php

declare(strict_types=1);

namespace App\Shared\Application;

abstract readonly class AbstractErrorHandler
{
    /**
     * @param array<string> $error
     */
    protected function addInternalCategoryIfMissing(array &$error): void
    {
        if (!isset($error['extensions']['category'])) {
            $error['extensions']['category'] = 'internal';
        }
    }
}
