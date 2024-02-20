<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext\Input;

readonly class ConfirmUserInput extends RequestInput
{
    public function __construct(public string $token)
    {
    }
}
