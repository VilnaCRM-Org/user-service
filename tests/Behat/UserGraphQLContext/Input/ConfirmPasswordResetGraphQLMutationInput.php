<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext\Input;

final readonly class ConfirmPasswordResetGraphQLMutationInput implements
    GraphQLMutationInput
{
    public function __construct(
        private string $token,
        private string $newPassword
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getInput(): array
    {
        return [
            'token' => $this->token,
            'newPassword' => $this->newPassword,
        ];
    }
}