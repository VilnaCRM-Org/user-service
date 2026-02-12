<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator\Constraint;

use App\Shared\Application\Validator\UniqueEmailValidator;
use Symfony\Component\Validator\Constraint;

#[\Attribute]
final class UniqueEmail extends Constraint
{
    public function __construct(
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct([], $groups, $payload);
    }

    /**
     * @return string
     *
     * @psalm-return UniqueEmailValidator::class
     */
    #[\Override]
    public function validatedBy(): string
    {
        return UniqueEmailValidator::class;
    }
}
