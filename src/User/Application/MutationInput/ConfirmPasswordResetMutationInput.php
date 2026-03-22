<?php

declare(strict_types=1);

namespace App\User\Application\MutationInput;

final readonly class ConfirmPasswordResetMutationInput implements MutationInput
{
    public function __construct(
        #[\SensitiveParameter]
        public ?string $token = null,
        #[\SensitiveParameter]
        public ?string $newPassword = null,
    ) {
    }
}
