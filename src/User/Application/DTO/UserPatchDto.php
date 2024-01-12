<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class UserPatchDto
{
    #[Assert\Email]
    #[Assert\Length(max: 255)]
    public string $email;

    #[Assert\Length(max: 255)]
    public string $initials;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $oldPassword;

    #[Assert\Length(max: 255)]
    public string $newPassword;

    public function __construct(string $email, string $initials, string $oldPassword, string $newPassword)
    {
        $this->email = $email;
        $this->initials = $initials;
        $this->oldPassword = $oldPassword;
        $this->newPassword = $newPassword;
    }
}
