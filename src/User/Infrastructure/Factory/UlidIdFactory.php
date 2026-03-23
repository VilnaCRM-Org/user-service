<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Factory;

use App\User\Application\Factory\IdFactoryInterface;
use Symfony\Component\Uid\Factory\UlidFactory;

/**
 * @psalm-api
 */
final readonly class UlidIdFactory implements IdFactoryInterface
{
    public function __construct(private UlidFactory $ulidFactory)
    {
    }

    #[\Override]
    public function create(): string
    {
        return (string) $this->ulidFactory->create();
    }
}
