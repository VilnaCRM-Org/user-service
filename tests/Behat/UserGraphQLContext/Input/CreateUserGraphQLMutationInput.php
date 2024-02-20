<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext\Input;

readonly class CreateUserGraphQLMutationInput extends GraphQLMutationInput
{
    public function __construct(public string $email, public string $initials, public string $password)
    {
    }
}
