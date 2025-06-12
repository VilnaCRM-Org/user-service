<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class BaseValidationHelper
{
    public function __construct(
        private TranslatorInterface $translator,
        private ExecutionContextInterface $context
    ) {
    }

    public function shouldSkipValidation(
        mixed $value,
        Constraint $constraint
    ): bool {
        return $this->isNullValue($value)
            || $this->isEmptyStringAndAllowed($value, $constraint);
    }

    public function isOnlyWhitespace(string $value): bool
    {
        return trim($value) === '';
    }

    public function isOptionalAndEmpty(
        mixed $value,
        Constraint $constraint
    ): bool {
        return $this->isEmptyString($value)
            && $this->shouldSkipValidation($value, $constraint);
    }

    /**
     * @param array<string, string> $parameters
     */
    public function addViolation(
        string $messageKey,
        array $parameters = []
    ): void {
        $message = $this->translator->trans(
            $messageKey,
            $parameters,
            'validators'
        );
        $this->context->buildViolation($message)->addViolation();
    }

    public function isLengthInvalid(
        string $value,
        int $minLength,
        int $maxLength
    ): bool {
        $length = $this->getStringLength($value);
        return $this->isTooShort($length, $minLength)
            || $this->isTooLong($length, $maxLength);
    }

    public function getStringLength(string $value): int
    {
        return mb_strlen($value, 'UTF-8');
    }

    public function hasNoNumbers(string $value): bool
    {
        return !$this->containsPattern($value, '/\p{N}/u');
    }

    public function hasNoUppercase(string $value): bool
    {
        return !$this->containsPattern($value, '/\p{Lu}/u');
    }

    private function isNullValue(mixed $value): bool
    {
        return $value === null;
    }

    private function isEmptyString(mixed $value): bool
    {
        return $value === '';
    }

    private function isTooShort(int $length, int $minLength): bool
    {
        return $length < $minLength;
    }

    private function isTooLong(int $length, int $maxLength): bool
    {
        return $length > $maxLength;
    }

    private function containsPattern(string $value, string $pattern): bool
    {
        return (bool) preg_match($pattern, $value);
    }

    private function isEmptyStringAndAllowed(
        mixed $value,
        Constraint $constraint
    ): bool {
        return $this->isEmptyString($value)
            && $this->isConstraintAllowingEmpty($constraint);
    }

    private function isConstraintAllowingEmpty(Constraint $constraint): bool
    {
        return $this->isOptional($constraint)
            || $this->allowsBlank($constraint);
    }

    private function isOptional(Constraint $constraint): bool
    {
        return $this->hasMethodAndReturnsTrue($constraint, 'isOptional');
    }

    private function allowsBlank(Constraint $constraint): bool
    {
        return $this->hasPropertyAndIsTrue($constraint, 'allowBlank');
    }

    private function hasMethodAndReturnsTrue(
        object $object,
        string $methodName
    ): bool {
        return $this->hasMethod($object, $methodName)
            && $this->callMethod($object, $methodName);
    }

    private function hasPropertyAndIsTrue(
        object $object,
        string $propertyName
    ): bool {
        return $this->hasProperty($object, $propertyName)
            && $this->getProperty($object, $propertyName);
    }

    private function hasMethod(object $object, string $methodName): bool
    {
        return method_exists($object, $methodName);
    }

    private function callMethod(object $object, string $methodName): bool
    {
        return $object->$methodName();
    }

    private function hasProperty(object $object, string $propertyName): bool
    {
        return property_exists($object, $propertyName);
    }

    private function getProperty(object $object, string $propertyName): bool
    {
        return $object->$propertyName;
    }
}
