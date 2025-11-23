<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext\Input;

/**
 * @psalm-suppress UnusedClass
 * @psalm-suppress PossiblyUnusedProperty
 */
final readonly class ConfirmPasswordResetGraphQLMutationInput extends
    GraphQLMutationInput
{
    public function __construct(
        public string $token,
        public string $newPassword
    ) {
    }
}
