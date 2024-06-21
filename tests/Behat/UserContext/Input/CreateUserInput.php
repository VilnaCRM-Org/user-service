<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext\Input;

final class CreateUserInput extends RequestInput
{
    public function __construct(
        public readonly ?string $email = null,
        public readonly ?string $initials = null,
        public readonly ?string $password = null
    ) {
    }
}
