<?php

declare(strict_types=1);

namespace App\User\Application\Transformer;

use App\User\Application\MutationInput\UpdateUserMutationInput;

final class UpdateUserMutationInputTransformer
{
    public function transform(array $args): UpdateUserMutationInput
    {
        $validationGroups = [];

        if (isset($args['initials'])) {
            $validationGroups[] = UpdateUserMutationInput::INITIALS_NOT_NULL;
        }
        if (isset($args['email'])) {
            $validationGroups[] = UpdateUserMutationInput::EMAIL_NOT_NULL;
        }
        if (isset($args['newPassword'])) {
            $validationGroups[] = UpdateUserMutationInput::NEW_PASSWORD_NOT_NULL;
        }

        return new UpdateUserMutationInput(
            $validationGroups,
            $args['password'] ?? null,
            $args['initials'] ?? null,
            $args['email'] ?? null,
            $args['newPassword'] ?? null
        );
    }
}
