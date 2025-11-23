<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

#[\Attribute]
final class Password extends Compound
{
    /**
     * @param array<string, string> $options
     *
     * @return array<int, \Symfony\Component\Validator\Constraint>
     */
    #[\Override]
    protected function getConstraints(array $options): array
    {
        return [
            new Length(
                min: 8,
                max: 64,
                minMessage: 'password.invalid.length',
                maxMessage: 'password.invalid.length'
            ),
            new Regex(
                pattern: '/[0-9]/',
                message: 'password.missing.number'
            ),
            new Regex(
                pattern: '/[A-Z]/',
                message: 'password.missing.uppercase'
            ),
        ];
    }
}
