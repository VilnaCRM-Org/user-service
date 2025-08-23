<?php

declare(strict_types=1);

namespace App\User\Application\Transformer;

use App\User\Application\MutationInput\RequestPasswordResetMutationInput;

final class RequestPasswordResetMutationInputTransformer
{
    /**
     * @param array<string, mixed> $args
     */
    public function transform(array $args): RequestPasswordResetMutationInput
    {
        return new RequestPasswordResetMutationInput(
            isset($args['email']) && is_string($args['email']) ? $args['email'] : null
        );
    }
}