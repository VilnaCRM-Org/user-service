<?php

declare(strict_types=1);

namespace App\User\Application\MutationInput;

final readonly class ConfirmUserMutationInput implements MutationInput
{
    public function __construct(
        public ?string $token = null
    ) {
    }
}
