<?php

declare(strict_types=1);

namespace App\Tests\Shared\Auth\Support;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\Bus\Command\CommandInterface;
use App\Shared\Domain\Bus\Command\CommandResponseInterface;
use Closure;
use Symfony\Contracts\Service\ResetInterface;

final class ControllableCommandBus implements CommandBusInterface, ResetInterface
{
    /**
     * @var Closure(CommandInterface): ?CommandResponseInterface|null
     */
    private ?Closure $handler = null;

    public function __construct(private readonly CommandBusInterface $inner)
    {
    }

    /**
     * @param callable(CommandInterface): ?CommandResponseInterface $handler
     */
    public function interceptWith(callable $handler): void
    {
        $this->handler = Closure::fromCallable($handler);
    }

    #[\Override]
    public function reset(): void
    {
        $this->handler = null;
    }

    #[\Override]
    public function dispatch(CommandInterface $command): ?CommandResponseInterface
    {
        $handler = $this->handler;
        if (is_callable($handler)) {
            return $handler($command);
        }

        return $this->inner->dispatch($command);
    }
}
