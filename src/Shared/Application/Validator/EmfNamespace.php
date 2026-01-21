<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Validates AWS CloudWatch EMF namespace.
 *
 * AWS CloudWatch namespace constraints:
 * - 1-256 characters
 * - Only ASCII alphanumeric and these characters: . - _ / # :
 * - Must contain at least one non-whitespace character
 */
final class EmfNamespace extends Compound
{
    public const int MAX_LENGTH = 256;

    public const string EMPTY_MESSAGE =
        'EMF namespace must contain at least one non-whitespace character';
    public const string TOO_LONG_MESSAGE =
        'EMF namespace must not exceed 256 characters';
    public const string INVALID_CHARACTERS_MESSAGE =
        'EMF namespace must contain only alphanumeric characters and . - _ / # :';

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
            new Regex(
                pattern: '/^[a-zA-Z0-9.\-_\/#:]+$/',
                message: self::INVALID_CHARACTERS_MESSAGE
            ),
        ];
    }
}
