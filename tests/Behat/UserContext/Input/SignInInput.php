<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext\Input;

/** @psalm-suppress PossiblyUnusedProperty Accessed via reflection in RequestInput::toArray() */
final class SignInInput extends RequestInput
{
    /** @SuppressWarnings(PHPMD.BooleanArgumentFlag) */
    public function __construct(
        public string $email,
        public string $password,
        public bool $rememberMe = false,
    ) {
    }
}
