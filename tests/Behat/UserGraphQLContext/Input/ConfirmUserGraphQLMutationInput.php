<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext\Input;

final readonly class ConfirmUserGraphQLMutationInput extends GraphQLMutationInput
{
    public function __construct(public string $token)
    {
    }
}
