<?php

declare(strict_types=1);

namespace App\User\Application\Transformer;

use App\User\Application\MutationInput\ConfirmUserMutationInput;

class ConfirmUserMutationInputTransformer
{
    public function transform(array $args): ConfirmUserMutationInput
    {
        return new ConfirmUserMutationInput($args['token'] ?? null);
    }
}
