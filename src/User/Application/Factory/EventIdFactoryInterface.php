<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

interface EventIdFactoryInterface
{
    public function generate(): string;
}
