<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CreateUserBatchValidator extends ConstraintValidator
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (count($value) < 1) {
            $this->addViolation($this->translator->trans(
                'batch.empty'
            ));
        }
        $emails = [];

        foreach ($value as $user) {
            $email = $user['email'];

            if (in_array($email, $emails)) {
                $this->addViolation(
                    $this->translator->trans(
                        'batch.email.duplicate'
                    )
                );
            } else {
                $emails[] = $email;
            }
        }
    }

    private function addViolation(string $message): void
    {
        $this->context->buildViolation($message)
            ->addViolation();
    }
}
