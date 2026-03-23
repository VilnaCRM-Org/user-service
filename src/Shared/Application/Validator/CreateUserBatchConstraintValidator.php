<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CreateUserBatchConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly CreateUserBatchPayloadValidator $payloadValidator
    ) {
    }

    /**
     * @psalm-param 'value' $value
     */
    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        $messages = $this->payloadValidator->validate($value);

        foreach ($messages as $message) {
            $this->context->buildViolation($this->translator->trans($message))
                ->addViolation();
        }
    }
}
