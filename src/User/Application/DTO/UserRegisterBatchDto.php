<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

final readonly class UserRegisterBatchDto
{
    public function __construct(
        public ?array $users = []
    ) {
    }
}
