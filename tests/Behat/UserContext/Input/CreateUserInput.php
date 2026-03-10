<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext\Input;

/**
 */
final class CreateUserInput extends RequestInput
{
    public function __construct(
        private readonly ?string $email = null,
        private readonly ?string $initials = null,
        private readonly ?string $password = null
    ) {
    }

    /**
     * @return array<string, string|null>
     */
    #[\Override]
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'initials' => $this->initials,
            'password' => $this->password,
        ];
    }
}
