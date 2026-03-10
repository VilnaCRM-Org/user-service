<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext\Input;

/**
 */
final readonly class ConfirmPasswordResetGraphQLMutationInput extends
    GraphQLMutationInput
{
    public function __construct(
        private string $token,
        private string $newPassword
    ) {
    }

    /**
     * @return array{token: string, newPassword: string}
     */
    #[\Override]
    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'newPassword' => $this->newPassword,
        ];
    }
}
