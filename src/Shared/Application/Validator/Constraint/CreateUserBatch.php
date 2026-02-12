<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator\Constraint;

use App\Shared\Application\Validator\CreateUserBatchValidator;
use Symfony\Component\Validator\Constraint;

#[\Attribute]
final class CreateUserBatch extends Constraint
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
     * @psalm-return CreateUserBatchValidator::class
     */
    #[\Override]
    public function validatedBy(): string
    {
        return CreateUserBatchValidator::class;
    }
}
