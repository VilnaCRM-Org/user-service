<?php

namespace App\User\Domain\Entity\User;

use Symfony\Component\Validator\Constraints as Assert;

class UserPutDto
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    public string $initials;

    #[Assert\NotBlank]
    public string $oldPassword;

    #[Assert\NotBlank]
    public string $newPassword;
}
