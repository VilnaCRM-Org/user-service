<?php

declare(strict_types=1);

namespace App\User\Application\Transformer;

use App\User\Application\MutationInput\UpdateUserMutationInput;

class UpdateUserMutationInputTransformer
{
    public function transform(array $args): UpdateUserMutationInput
    {
        $validationGroups = [];

        if(isset($args['initials'])){
            $validationGroups[] = 'initials_not_null';
        }
        if(isset($args['email'])){
            $validationGroups[] = 'email_not_null';
        }
        if(isset($args['newPassword'])){
            $validationGroups[] = 'new_password_not_null';
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