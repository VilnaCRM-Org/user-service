<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext\Input;

readonly class EmptyInput extends RequestInput
{
    public function __construct()
    {
    }
}
