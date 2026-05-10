<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

final readonly class UserRegisterBatchDto
{
    /**
     * @param array<int, array{email: string, initials: string, password: string}> $users
     */
    public function __construct(
        public array $users = []
    ) {
    }
}
