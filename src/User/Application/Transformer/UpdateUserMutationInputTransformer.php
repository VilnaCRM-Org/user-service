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
            $this->value($args, 'password'),
            $this->value($args, 'initials'),
            $this->value($args, 'email'),
            $this->value($args, 'newPassword')
        );
    }

    /**
     * @param array<string, string> $args
     */
    private function value(array $args, string $key): ?string
    {
        return array_key_exists($key, $args) ? $args[$key] : null;
    }
}
