<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext\Input;

abstract class RequestInput
{
    abstract public function getJson(): string;
}
