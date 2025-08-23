<?php

declare(strict_types=1);

namespace App\User\Application\Transformer;

use App\User\Application\MutationInput\RequestPasswordResetMutationInput;

final class RequestPasswordResetMutationInputTransformer
{
    /**
     * @param array<string, string> $args
     */
    public function transform(array $args): RequestPasswordResetMutationInput
    {
        return new RequestPasswordResetMutationInput($args['email'] ?? null);
    }
}