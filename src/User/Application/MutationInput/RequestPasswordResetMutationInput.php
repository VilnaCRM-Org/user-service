<?php

declare(strict_types=1);

namespace App\User\Application\MutationInput;

final readonly class RequestPasswordResetMutationInput implements MutationInput
{
    public function __construct(
        public ?string $email = null
    ) {
    }
}