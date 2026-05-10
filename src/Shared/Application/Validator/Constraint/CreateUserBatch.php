<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator\Constraint;

use App\Shared\Application\Validator\CreateUserBatchConstraintValidator;
use Symfony\Component\Validator\Constraint;

#[\Attribute]
final class CreateUserBatch extends Constraint
{
    /**
     * @param array<string>|null $groups
     */
    public function __construct(
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct([], $groups, $payload);
    }

    /**
     * @psalm-return CreateUserBatchConstraintValidator::class
     */
    #[\Override]
    public function validatedBy(): string
    {
        return CreateUserBatchConstraintValidator::class;
    }
}
