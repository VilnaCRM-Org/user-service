<?php

declare(strict_types=1);

namespace App\User\Application\Factory\Generator;

interface EventIdGeneratorInterface
{
    public function generate(): string;
}
