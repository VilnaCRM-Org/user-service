<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandResponseInterface;
use App\User\Domain\ValueObject\UserBatch;

final readonly class RegisterUserBatchCommandResponse implements
    CommandResponseInterface
{
    public function __construct(public UserBatch $userBatch)
    {
    }
}
