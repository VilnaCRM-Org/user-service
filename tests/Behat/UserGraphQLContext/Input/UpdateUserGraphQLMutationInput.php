<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext\Input;

/**
 */
final readonly class UpdateUserGraphQLMutationInput extends GraphQLMutationInput
{
    public function __construct(
        private string $id,
        private string $email,
        private string $password
    ) {
    }
}
