<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class DuplicateEmailException extends \RuntimeException
{
    public function __construct(string $email)
    {
        parent::__construct(
            "{$email} address is already registered"
        );
    }
}
