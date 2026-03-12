<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Generator;

use App\User\Application\Factory\Generator\IdGeneratorInterface;
use Symfony\Component\Uid\Factory\UlidFactory;

/**
 * @psalm-api
 */
final readonly class UlidIdGenerator implements IdGeneratorInterface
{
    public function __construct(private UlidFactory $ulidFactory)
    {
    }

    #[\Override]
    public function generate(): string
    {
        return (string) $this->ulidFactory->create();
    }
}
