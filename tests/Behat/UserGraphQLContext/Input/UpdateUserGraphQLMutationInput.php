<?php

namespace App\Tests\Behat\UserGraphQLContext\Input;

readonly class UpdateUserGraphQLMutationInput extends GraphQLMutationInput
{
    public function __construct(public string $userId, public string $email, public string $oldPassword)
    {
    }
}
