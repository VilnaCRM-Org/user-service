<?php

declare(strict_types=1);

namespace App\User\Application\Validator;

use App\User\Domain\Entity\PasswordResetTokenInterface;

interface PasswordResetTokenValidatorInterface
{
    public function validate(?PasswordResetTokenInterface $token): void;
}
