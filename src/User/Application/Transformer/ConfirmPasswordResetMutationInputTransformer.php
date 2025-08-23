<?php

declare(strict_types=1);

namespace App\User\Application\Transformer;

use App\User\Application\MutationInput\ConfirmPasswordResetMutationInput;

final class ConfirmPasswordResetMutationInputTransformer
{
    /**
     * @param array<string, mixed> $args
     */
    public function transform(array $args): ConfirmPasswordResetMutationInput
    {
        return new ConfirmPasswordResetMutationInput(
            isset($args['token']) && is_string($args['token']) ? $args['token'] : null,
            isset($args['newPassword']) && is_string($args['newPassword']) ? $args['newPassword'] : null
        );
    }
}