<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;
use App\User\Domain\Entity\ConfirmationToken;

readonly class ConfirmUserCommand implements CommandInterface
{
    public function __construct(public ConfirmationToken $token)
    {
    }
}
