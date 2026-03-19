<?php

declare(strict_types=1);

namespace App\OAuth\Domain\Exception;

final class ProviderMismatchException extends \RuntimeException
{
    public function __construct(string $expected, string $actual)
    {
        parent::__construct(
            sprintf(
                'Provider mismatch: expected %s, got %s',
                $expected,
                $actual,
            )
        );
    }
}
