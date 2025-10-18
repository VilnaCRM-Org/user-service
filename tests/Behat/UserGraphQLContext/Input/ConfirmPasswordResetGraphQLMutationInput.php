<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext\Input;

final readonly class ConfirmPasswordResetGraphQLMutationInput extends
    GraphQLMutationInput
{
    public function __construct(
        public string $token,
        public string $newPassword
    ) {
    }
}
