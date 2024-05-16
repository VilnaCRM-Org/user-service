<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use Doctrine\Common\Collections\Collection;

final readonly class UserBatch
{
    public function __construct(
        public Collection $users
    ) {
    }
}
