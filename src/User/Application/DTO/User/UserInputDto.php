<?php

namespace App\User\Application\DTO\User;

use Symfony\Component\Validator\Constraints as Assert;

class UserInputDto
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    public string $initials;

    #[Assert\NotBlank]
    public string $password;
}
