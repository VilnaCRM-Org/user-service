<?php

namespace App\Tests\Behat\UserGraphQLContext\Input;

readonly class ResendEmailToUserGraphQLMutationInput extends GraphQLMutationInput
{
    public function __construct(public string $userId)
    {
    }
}
