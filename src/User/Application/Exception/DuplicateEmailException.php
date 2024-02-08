<?php

declare(strict_types=1);

namespace App\User\Application\Exception;

final class DuplicateEmailException extends \LogicException
{
    public function __construct(string $email)
    {
        parent::__construct(
            $email.' address is already registered. '.
            'Please use a different email address or try logging in.'
        );
    }
}
