<?php

declare(strict_types=1);

namespace App\User\Application\Transformer;

use App\User\Application\Command\SignUpCommand;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserFactory;

class SignUpTransformer
{
    public function __construct(private UserFactory $userFactory)
    {
    }

    public function transformToUser(SignUpCommand $command): User
    {
        $email = $command->email;
        $initials = $command->initials;
        $password = $command->password;

        return $this->userFactory->create($email, $initials, $password);
    }
}
