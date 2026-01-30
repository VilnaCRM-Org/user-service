<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext\Input;

/**
 * @psalm-suppress UnusedClass
 * @psalm-suppress PossiblyUnusedProperty
 */
final readonly class CreateUserGraphQLMutationInput extends GraphQLMutationInput
{
    public function __construct(
        private string $email,
        private string $initials,
        private string $password
    ) {
    }
}
