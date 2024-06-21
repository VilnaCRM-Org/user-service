<?php

declare(strict_types=1);

namespace App\User\Application\Transformer;

use App\User\Application\MutationInput\UpdateUserMutationInput;

final class UpdateUserMutationInputTransformer
{
    /**
     * @param array<string, string> $args
     */
    public function transform(array $args): UpdateUserMutationInput
    {
        return new UpdateUserMutationInput(
            $args['password'] ?? null,
            $args['initials'] ?? null,
            $args['email'] ?? null,
            $args['newPassword'] ?? null
        );
    }
}
