<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext\Input;

final class ConfirmPasswordResetInput extends RequestInput
{
    public function __construct(
        private readonly string $token,
        private readonly string $newPassword
    ) {
    }
}
