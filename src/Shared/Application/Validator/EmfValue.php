<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Validates AWS CloudWatch EMF dimension values.
 *
 * AWS EMF dimension value constraints:
 * - 1-1024 characters
 * - ASCII only (no Unicode)
 * - No ASCII control characters
 * - Must contain at least one non-whitespace character
 */
final class EmfValue extends Compound
{
    public const int MAX_LENGTH = 1024;

    public const string EMPTY_MESSAGE =
        'EMF dimension value must contain at least one non-whitespace character';
    public const string TOO_LONG_MESSAGE =
        'EMF dimension value must not exceed 1024 characters';
    public const string NON_ASCII_MESSAGE =
        'EMF dimension value must contain only ASCII characters';
    public const string CONTROL_CHARS_MESSAGE =
        'EMF dimension value must not contain ASCII control characters';

    /**
     * @param array<string, scalar|array|null> $options
     *
     * @return array<Constraint>
     */
    protected function getConstraints(array $options): array
    {
        return [
            new NotBlank(message: self::EMPTY_MESSAGE, normalizer: 'trim'),
            new Length(max: self::MAX_LENGTH, maxMessage: self::TOO_LONG_MESSAGE),
            new Regex(pattern: '/^[\x00-\x7F]+$/', message: self::NON_ASCII_MESSAGE),
            new Regex(
                pattern: '/[\x00-\x1F\x7F]/',
                match: false,
                message: self::CONTROL_CHARS_MESSAGE
            ),
        ];
    }
}
