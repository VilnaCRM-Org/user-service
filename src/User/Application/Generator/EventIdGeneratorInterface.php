<?php

declare(strict_types=1);

namespace App\User\Application\Generator;

interface EventIdGeneratorInterface
{
    public function generate(): string;
}
