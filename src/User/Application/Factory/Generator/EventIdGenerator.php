<?php

declare(strict_types=1);

namespace App\User\Application\Factory\Generator;

use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class EventIdGenerator implements EventIdGeneratorInterface
{
    public function __construct(private UuidFactory $uuidFactory)
    {
    }

    #[\Override]
    public function generate(): string
    {
        return (string) $this->uuidFactory->create();
    }
}
