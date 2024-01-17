<?php

declare(strict_types=1);

namespace App\User\Application\MutationInput;

interface MutationInput
{
    /**
     * @return array<string>
     */
    public function getValidationGroups(): array;
}