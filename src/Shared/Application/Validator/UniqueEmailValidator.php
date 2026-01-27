<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use App\Shared\Application\Checker\EmailUniquenessChecker;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UniqueEmailValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EmailUniquenessChecker $emailUniquenessChecker,
        private readonly TranslatorInterface $translator
    ) {
    }

    /**
     * @param null|string $value
     */
    #[\Override]
    public function validate(?string $value, Constraint $constraint): void
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
        return match (true) {
            $candidate === null => true,
            $this->emailUniquenessChecker->isUnique($candidate) => true,
            default => false,
        };
    }
}
