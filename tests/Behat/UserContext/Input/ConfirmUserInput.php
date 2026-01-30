<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext\Input;

/**
 * @psalm-suppress UnusedProperty - Properties used via reflection in RequestInput::toArray()
 */
final class ConfirmUserInput extends RequestInput
{
    public function __construct(private readonly string $token)
    {
    }
}
