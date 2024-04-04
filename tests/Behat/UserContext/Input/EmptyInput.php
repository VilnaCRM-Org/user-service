<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext\Input;

final readonly class EmptyInput extends RequestInput
{
    public function __construct()
    {
    }
}
