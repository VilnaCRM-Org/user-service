<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UniqueEmailConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EmailUniquenessValidator $emailUniquenessValidator,
        private readonly TranslatorInterface $translator
    ) {
    }

    /**
     * @param string|null $value
     */
    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!is_string($value)) {
            return;
        }

        $candidate = $this->normalizedCandidate($value);

        if ($this->shouldSkipUniquenessCheck($candidate)) {
            return;
        }

        $this->context->buildViolation(
            $this->translator->trans('email.not.unique')
        )->addViolation();
    }

    private function normalizedCandidate(string $value): ?string
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        return $trimmed;
    }

    private function shouldSkipUniquenessCheck(?string $candidate): bool
    {
        if ($candidate === null) {
            return true;
        }

        return $this->emailUniquenessValidator->isUnique($candidate);
    }
}
