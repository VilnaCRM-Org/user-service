<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use App\Shared\Domain\Collection\DomainEventCollection;
use App\User\Domain\Collection\UserCollection;

final readonly class BatchUserRegistrationResult
{
    public function __construct(
        public UserCollection $returnedUsers,
        public UserCollection $usersToPersist,
        public DomainEventCollection $events
    ) {
    }
}
