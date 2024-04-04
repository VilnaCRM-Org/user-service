<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext\Input;

final readonly class UpdateUserGraphQLMutationInput extends GraphQLMutationInput
{
    public function __construct(
        public string $id,
        public string $email,
        public string $password
    ) {
    }
}
