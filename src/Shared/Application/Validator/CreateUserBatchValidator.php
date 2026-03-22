<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use App\Shared\Application\Evaluator\CreateUserBatchConstraintEvaluator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CreateUserBatchValidator extends ConstraintValidator
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly CreateUserBatchConstraintEvaluator $constraintEvaluator
    ) {
    }

    /**
     * @psalm-param 'value' $value
     */
    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        $messages = $this->constraintEvaluator->evaluate($value);

        foreach ($messages as $message) {
            $this->addViolation($this->translator->trans($message));
        }
    }

    private function addViolation(string $message): void
    {
        $this->context->buildViolation($message)
            ->addViolation();
    }
}
