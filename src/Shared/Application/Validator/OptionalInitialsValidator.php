<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class OptionalInitialsValidator extends ConstraintValidator
{
    public function __construct(private InitialsValidator $initialsValidator)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($value !== null && $value !== '') {
            $this->initialsValidator->validate($value, $constraint);
        }
    }
}
