<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class UserRegisterDto
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 255)]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $initials;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $password;

    public function __construct(string $email, string $initials, string $password)
    {
        $this->email = $email;
        $this->initials = $initials;
        $this->password = $password;
    }
}
