<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Domain\Exception;

use App\OAuth\Domain\Exception\MissingOAuthParametersException;
use App\Tests\Unit\UnitTestCase;

final class MissingOAuthParametersExceptionTest extends UnitTestCase
{
    public function testMessageDescribesAllRequiredParameters(): void
    {
        $exception = new MissingOAuthParametersException();

        $this->assertSame(
            'Missing required OAuth parameters: code, state, or flow-binding cookie',
            $exception->getMessage(),
        );
    }
}
