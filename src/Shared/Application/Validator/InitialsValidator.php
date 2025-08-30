<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use App\Shared\Application\Validator\Strategy\InitialsValidationChecks;
use App\Shared\Application\Validator\Strategy\ValidationSkipChecker;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class InitialsValidator extends ConstraintValidator
{
    private const MAX_INITIALS_LENGTH = 255;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly ValidationSkipChecker $skipChecker =
            new ValidationSkipChecker(),
        private readonly InitialsValidationChecks $validationChecks =
            new InitialsValidationChecks(),
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($this->skipChecker->shouldSkip($value, $constraint)) {
            return;
        }

        $this->validateSpaces($value);
    }

    private function validateSpaces(mixed $value): void
    {
        if ($this->validationChecks->isOnlySpaces($value)) {
            $this->addViolation(
                $this->translator->trans('initials.spaces')
            );
        }
    }

    private function addViolation(string $message): void
    {
        $this->context->buildViolation($message)->addViolation();
    }
}
