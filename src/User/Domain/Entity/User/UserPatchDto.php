<?php

namespace App\User\Domain\Entity\User;

use Symfony\Component\Validator\Constraints as Assert;

class UserPatchDto
{
    #[Assert\Email]
    public string $email = '';

    public string $initials = '';

    #[Assert\NotBlank]
    public string $oldPassword = '';

    public string $newPassword = '';
}
