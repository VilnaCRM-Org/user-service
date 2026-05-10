<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

/**
 * @psalm-api
 *
 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
 */
final class PasskeySignInOptionsDto
{
    /**
     * @psalm-api
     */
    public function __construct(public string $email = '', public bool $rememberMe = false)
    {
    }
}
