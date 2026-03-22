<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RequestPasswordResetCommandResponse;

final class RequestPasswordResetCommandResponseTest extends UnitTestCase
{
    public function testConstructor(): void
    {
        $response = new RequestPasswordResetCommandResponse();

        $this->assertInstanceOf(RequestPasswordResetCommandResponse::class, $response);
    }
}
