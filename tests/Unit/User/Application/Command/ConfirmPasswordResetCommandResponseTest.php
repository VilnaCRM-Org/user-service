<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\ConfirmPasswordResetCommandResponse;

final class ConfirmPasswordResetCommandResponseTest extends UnitTestCase
{
    public function testConstructor(): void
    {
        $response = new ConfirmPasswordResetCommandResponse();

        $this->assertInstanceOf(ConfirmPasswordResetCommandResponse::class, $response);
    }
}
