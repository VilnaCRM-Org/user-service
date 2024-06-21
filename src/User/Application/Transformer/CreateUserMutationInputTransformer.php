<?php

declare(strict_types=1);

namespace App\User\Application\Transformer;

use App\User\Application\MutationInput\CreateUserMutationInput;

final class CreateUserMutationInputTransformer
{
    /**
     * @param array<string, string> $args
     */
    public function transform(array $args): CreateUserMutationInput
    {
        return new CreateUserMutationInput(
            $args['email'] ?? null,
            $args['initials'] ?? null,
            $args['password'] ?? null
        );
    }
}
