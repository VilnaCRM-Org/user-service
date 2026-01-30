<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext\Input;

/**
 * @psalm-suppress UnusedClass
 * @psalm-suppress PossiblyUnusedProperty
 */
final class UpdateUserInput extends RequestInput
{
    public function __construct(
        private readonly ?string $email = null,
        private readonly ?string $initials = null,
        private readonly ?string $oldPassword = null,
        private readonly ?string $newPassword = null
    ) {
    }
}
