<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

final class CacheOperationFailedException extends \RuntimeException
{
    public const UNAVAILABLE_MESSAGE = 'Cache unavailable';
    private const INVALIDATION_FAILED_MESSAGE = 'Cache error';

    public static function unavailable(): self
    {
        return new self(self::UNAVAILABLE_MESSAGE);
    }

    public static function invalidationFailed(): self
    {
        return new self(self::INVALIDATION_FAILED_MESSAGE);
    }
}
