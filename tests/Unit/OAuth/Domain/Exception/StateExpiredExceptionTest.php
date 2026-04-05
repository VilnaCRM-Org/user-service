<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Domain\Exception;

use App\OAuth\Domain\Exception\StateExpiredException;
use App\Tests\Unit\UnitTestCase;

final class StateExpiredExceptionTest extends UnitTestCase
{
    public function testMessageIsSet(): void
    {
        $exception = new StateExpiredException();

        $this->assertSame('OAuth state has expired', $exception->getMessage());
    }
}
