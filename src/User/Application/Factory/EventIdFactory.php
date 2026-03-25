<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class EventIdFactory implements EventIdFactoryInterface
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
