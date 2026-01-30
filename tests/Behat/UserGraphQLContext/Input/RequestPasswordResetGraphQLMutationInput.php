<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext\Input;

/**
 * @psalm-suppress UnusedClass
 * @psalm-suppress PossiblyUnusedProperty
 */
final readonly class RequestPasswordResetGraphQLMutationInput extends
    GraphQLMutationInput
{
    public function __construct(private string $email)
    {
    }
}
