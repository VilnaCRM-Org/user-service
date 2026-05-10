<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Bus\Guard;

use App\Shared\Application\Bus\Guard\CommandResponseTypeGuard;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\ConfirmPasswordResetCommandResponse;
use App\User\Application\DTO\RequestPasswordResetCommandResponse;

final class CommandResponseTypeGuardTest extends UnitTestCase
{
    public function testExpectReturnsResponseWhenTypeMatches(): void
    {
        $response = new RequestPasswordResetCommandResponse();

        $this->assertSame(
            $response,
            (new CommandResponseTypeGuard())->expect(
                $response,
                RequestPasswordResetCommandResponse::class
            )
        );
    }

    public function testExpectThrowsWhenResponseIsMissing(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'Expected command bus to return %s, got null.',
            RequestPasswordResetCommandResponse::class
        ));

        (new CommandResponseTypeGuard())->expect(
            null,
            RequestPasswordResetCommandResponse::class
        );
    }

    public function testExpectThrowsWhenTypeDoesNotMatch(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'Expected command bus to return %s, got %s.',
            RequestPasswordResetCommandResponse::class,
            ConfirmPasswordResetCommandResponse::class
        ));

        (new CommandResponseTypeGuard())->expect(
            new ConfirmPasswordResetCommandResponse(),
            RequestPasswordResetCommandResponse::class
        );
    }
}
