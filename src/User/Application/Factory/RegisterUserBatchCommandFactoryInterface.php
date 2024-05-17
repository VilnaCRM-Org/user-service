<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\RegisterUserBatchCommand;
use Doctrine\Common\Collections\ArrayCollection;

interface RegisterUserBatchCommandFactoryInterface
{
    public function create(
        ArrayCollection $users
    ): RegisterUserBatchCommand;
}
