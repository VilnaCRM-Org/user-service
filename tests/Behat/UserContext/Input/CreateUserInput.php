<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext\Input;

readonly class CreateUserInput extends RequestInput
{
    public function __construct(public ?string $email = null, public ?string $initials = null, public ?string $password = null)
    {
    }
}
