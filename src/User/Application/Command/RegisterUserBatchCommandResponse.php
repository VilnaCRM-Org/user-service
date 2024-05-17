<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandResponseInterface;
use Doctrine\Common\Collections\ArrayCollection;

final readonly class RegisterUserBatchCommandResponse implements
    CommandResponseInterface
{
    public function __construct(public ArrayCollection $users)
    {
    }
}
