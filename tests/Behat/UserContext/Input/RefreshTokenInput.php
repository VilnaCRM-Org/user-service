<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext\Input;

/** @psalm-suppress PossiblyUnusedProperty Accessed via reflection in RequestInput::toArray() */
final class RefreshTokenInput extends RequestInput
{
    public function __construct(
        public string $refreshToken
    ) {
    }
}
