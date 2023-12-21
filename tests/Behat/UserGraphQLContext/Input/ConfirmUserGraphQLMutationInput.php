<?php

namespace App\Tests\Behat\UserGraphQLContext\Input;

readonly class ConfirmUserGraphQLMutationInput extends GraphQLMutationInput
{
    public function __construct(public string $token)
    {
    }
}
