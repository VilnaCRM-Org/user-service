<?php

namespace App\Tests\Behat\UserGraphQLContext\Input;

readonly class DeleteUserGraphQLMutationInput extends GraphQLMutationInput
{
    public function __construct(public string $id)
    {
    }
}
