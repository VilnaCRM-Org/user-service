<?php

namespace App\Tests\Behat\UserGraphQLContext\Input;

readonly class UpdateUserGraphQLMutationInput extends GraphQLMutationInput
{
    public function __construct(public string $id, public string $email, public string $password)
    {
    }
}
