<?php

declare(strict_types=1);

namespace App\User\Application\Transformer;

use App\User\Application\Command\SignUpCommand;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactory;

class SignUpTransformer
{
    public function __construct(private UserFactory $userFactory)
    {
    }

    public function transformToUser(SignUpCommand $command): User
    {
        return $this->userFactory->create($command->email, $command->initials, $command->password);
    }
}
