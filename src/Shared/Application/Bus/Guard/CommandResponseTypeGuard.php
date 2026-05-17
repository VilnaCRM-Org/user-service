<?php

declare(strict_types=1);

namespace App\Shared\Application\Bus\Guard;

use App\Shared\Domain\Bus\Command\CommandResponseInterface;
use LogicException;

final class CommandResponseTypeGuard
{
    /**
     * @template TResponse of CommandResponseInterface
     *
     * @param class-string<TResponse> $expectedType
     *
     * @return TResponse
     */
    public function expect(
        ?CommandResponseInterface $response,
        string $expectedType,
    ): CommandResponseInterface {
        if (!$response instanceof $expectedType) {
            $actualType = $response === null ? 'null' : $response::class;

            throw new LogicException(sprintf(
                'Expected command bus to return %s, got %s.',
                $expectedType,
                $actualType
            ));
        }

        return $response;
    }
}
