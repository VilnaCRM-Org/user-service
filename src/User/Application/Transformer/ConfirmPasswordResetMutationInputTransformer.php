<?php

declare(strict_types=1);

namespace App\User\Application\Transformer;

use App\User\Application\MutationInput\ConfirmPasswordResetMutationInput;

final class ConfirmPasswordResetMutationInputTransformer
{
    /**
     * @param array<string, string> $args
     */
    public function transform(array $args): ConfirmPasswordResetMutationInput
    {
        return new ConfirmPasswordResetMutationInput(
            $args['token'] ?? null,
            $args['newPassword'] ?? null
        );
    }
}