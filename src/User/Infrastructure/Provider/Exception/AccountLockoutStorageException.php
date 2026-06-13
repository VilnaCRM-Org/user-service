<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Provider\Exception;

use RuntimeException;

final class AccountLockoutStorageException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'Account lockout storage is unavailable; sign-in attempt refused.'
        );
    }
}
