<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext\Input;

final class RequestPasswordResetInput extends RequestInput
{
    public function __construct(
        private readonly string $email
    ) {
    }
}
