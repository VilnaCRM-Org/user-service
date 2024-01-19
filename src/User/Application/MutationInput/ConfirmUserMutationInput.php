<?php

declare(strict_types=1);

namespace App\User\Application\MutationInput;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ConfirmUserMutationInput implements MutationInput
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\NotBlank]
        public ?string $token = null
    ) {
    }

    /**
     * @return array<string>
     */
    public function getValidationGroups(): array
    {
        return [];
    }
}
