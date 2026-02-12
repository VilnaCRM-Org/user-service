<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator\Constraint;

use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Type;

#[\Attribute]
final class Initials extends Compound
{
    /**
     * @param array<string, string> $options
     *
     * @return (Length|Regex|Type)[]
     *
     * @psalm-return list{Type, Length, Regex, Regex}
     */
    #[\Override]
    protected function getConstraints(array $options): array
    {
        return [
            new Type(
                type: 'string',
                message: 'initials.invalid.type'
            ),
            new Length(
                max: 255,
                maxMessage: 'initials.invalid.length'
            ),
            new Regex(
                pattern: '/^(?!\d)/',
                message: 'initials.starts_with_number'
            ),
            new Regex(
                pattern: '/\S/',
                message: 'initials.spaces'
            ),
        ];
    }
}
