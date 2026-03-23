<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext\Input;

/**
 * @psalm-suppress UnusedProperty - Properties used via reflection in RequestInput::toArray()
 */
final class CreateUserInput extends RequestInput
{
    public function __construct(
        private readonly ?string $email = null,
        private readonly ?string $initials = null,
        private readonly ?string $password = null
    ) {
    }

    #[\Override]
    public function getJson(): string
    {
        return json_encode([
            'email' => $this->email,
            'initials' => $this->initials,
            'password' => $this->password,
        ]);
    }
}
