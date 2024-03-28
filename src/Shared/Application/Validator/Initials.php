<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
final class Initials extends Constraint
{
    public function __construct(
        ?array $groups = null,
        mixed $payload = null,
        private bool $optional = false,
    ) {
        parent::__construct([], $groups, $payload);
    }

    public function isOptional(): bool
    {
        return $this->optional;
    }
}
