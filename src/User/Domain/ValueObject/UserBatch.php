<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

final readonly class UserBatch
{
    /**
     * @param array<array<string>> $users
     */
    public function __construct(
        public array $users
    ) {
    }
}
