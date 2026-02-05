<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext\Input;

/**
 * @psalm-suppress UnusedClass
 * @psalm-suppress UnusedProperty - Properties used via reflection in GraphQLMutationInput::toArray()
 */
final readonly class ConfirmPasswordResetGraphQLMutationInput extends
    GraphQLMutationInput
{
    public function __construct(
        private string $token,
        private string $newPassword
    ) {
    }
}
