<?php

declare(strict_types=1);

namespace App\User\Application\Transformer;

use App\User\Application\MutationInput\CreateUserMutationInput;

final class CreateUserMutationInputTransformer
{
    public function transform(array $args): CreateUserMutationInput
    {
        return new CreateUserMutationInput(
            $args['initials'] ?? null,
            $args['email'] ?? null,
            $args['password'] ?? null
        );
    }
}
