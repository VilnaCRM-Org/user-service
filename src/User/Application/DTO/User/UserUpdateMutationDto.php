<?php

declare(strict_types=1);

namespace App\User\Application\DTO\User;

use Symfony\Component\Validator\Constraints as Assert;

class UserUpdateMutationDto
{
    #[Assert\NotBlank]
    public string $userId = '';

    #[Assert\Email]
    public string $email = '';

    public string $initials = '';

    #[Assert\NotBlank]
    public string $oldPassword = '';

    public string $newPassword = '';
}
