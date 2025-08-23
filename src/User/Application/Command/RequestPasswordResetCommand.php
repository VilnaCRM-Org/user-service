<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;

final readonly class RequestPasswordResetCommand implements CommandInterface
{
    public function __construct(public string $email)
    {
    }
}