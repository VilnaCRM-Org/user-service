<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext\Input;

final readonly class RequestPasswordResetGraphQLMutationInput implements
    GraphQLMutationInput
{
    public function __construct(private string $email)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function getInput(): array
    {
        return [
            'email' => $this->email,
        ];
    }
}