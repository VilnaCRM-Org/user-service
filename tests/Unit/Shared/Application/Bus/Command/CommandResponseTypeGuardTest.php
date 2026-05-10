<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Bus\Command;

use App\Shared\Application\Bus\Command\CommandResponseTypeGuard;
use App\Shared\Domain\Bus\Command\CommandResponseInterface;
use App\Tests\Unit\UnitTestCase;

final class CommandResponseTypeGuardTest extends UnitTestCase
{
    public function testExpectReturnsResponseWhenTypeMatches(): void
    {
        $response = new MatchingCommandResponse();

        $this->assertSame(
            $response,
            CommandResponseTypeGuard::expect(
                $response,
                MatchingCommandResponse::class
            )
        );
    }

    public function testExpectThrowsWhenResponseIsMissing(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Expected command bus to return App\Tests\Unit\Shared\Application\Bus\Command\MatchingCommandResponse, got null.'
        );

        CommandResponseTypeGuard::expect(null, MatchingCommandResponse::class);
    }

    public function testExpectThrowsWhenTypeDoesNotMatch(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Expected command bus to return App\Tests\Unit\Shared\Application\Bus\Command\MatchingCommandResponse, got App\Tests\Unit\Shared\Application\Bus\Command\OtherCommandResponse.'
        );

        CommandResponseTypeGuard::expect(
            new OtherCommandResponse(),
            MatchingCommandResponse::class
        );
    }
}

final class MatchingCommandResponse implements CommandResponseInterface
{
}

final class OtherCommandResponse implements CommandResponseInterface
{
}
