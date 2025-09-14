<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

#[\Attribute]
final class Initials extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new NotBlank(message: 'initials.invalid.blank'),
            new Length(
                max: 255,
                maxMessage: 'initials.invalid.length'
            ),
            new Regex(
                pattern: '/^\S+$/',
                message: 'initials.spaces'
            ),
        ];
    }
}
