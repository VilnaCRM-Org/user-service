<?php

declare(strict_types=1);

namespace App\User\Application\Validator;

use App\User\Domain\Entity\User;

interface UserCredentialValidatorInterface
{
    public function validate(
        string $email,
        string $password,
        string $ipAddress,
        string $userAgent
    ): User;
}
