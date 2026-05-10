<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

final class DocumentManagerResetterException extends \RuntimeException
{
    public static function documentManagerUnavailable(): self
    {
        return new self('Document manager is not available');
    }

    public static function testContainerUnavailable(): self
    {
        return new self('Test container is not available');
    }

    public static function userCachePoolUnavailable(): self
    {
        return new self('User cache pool is not available');
    }
}
