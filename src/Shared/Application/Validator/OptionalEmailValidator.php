<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\EmailValidator;
use Symfony\Component\Validator\ConstraintValidator;

final class OptionalEmailValidator extends ConstraintValidator
{
    public function __construct(private EmailValidator $emailValidator)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($value !== null && $value !== '') {
            $this->emailValidator->initialize($this->context);
            $this->emailValidator->validate($value, new Email());
        }
    }
}
