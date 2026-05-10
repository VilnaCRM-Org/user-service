<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext\Input;

/**
 */
final readonly class CreateUserGraphQLMutationInput extends GraphQLMutationInput
{
    public function __construct(
        private string $email,
        private string $initials,
        private string $password
    ) {
    }

    /**
     * @return array{email: string, initials: string, password: string}
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
