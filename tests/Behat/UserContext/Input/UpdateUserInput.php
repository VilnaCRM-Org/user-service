<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext\Input;

readonly class UpdateUserInput extends RequestInput
{
    public function __construct(
        public ?string $email = null,
        public ?string $initials = null,
        public ?string $oldPassword = null,
        public ?string $newPassword = null
    ) {
    }
}
