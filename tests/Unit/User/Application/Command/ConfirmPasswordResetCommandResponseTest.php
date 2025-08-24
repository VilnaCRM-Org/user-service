<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\ConfirmPasswordResetCommandResponse;

final class ConfirmPasswordResetCommandResponseTest extends UnitTestCase
{
    public function testConstructor(): void
    {
        $message = $this->faker->sentence();

        $response = new ConfirmPasswordResetCommandResponse($message);

        $this->assertInstanceOf(ConfirmPasswordResetCommandResponse::class, $response);
        $this->assertSame($message, $response->message);
    }

    public function testMessage(): void
    {
        $message = $this->faker->sentence();

        $response = new ConfirmPasswordResetCommandResponse($message);

        $this->assertSame($message, $response->message);
    }
}