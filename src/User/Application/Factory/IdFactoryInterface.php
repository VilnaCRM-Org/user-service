<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

interface IdFactoryInterface
{
    public function create(): string;
}
