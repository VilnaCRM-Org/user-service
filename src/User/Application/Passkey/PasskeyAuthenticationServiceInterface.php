<?php

declare(strict_types=1);

namespace App\User\Application\Passkey;

use App\User\Application\DTO\PasskeyAuthenticationResult;
use App\User\Application\DTO\PasskeyOptionsResult;

interface PasskeyAuthenticationServiceInterface
{
    public function start(string $email, bool $rememberMe): PasskeyOptionsResult;

    /**
     * @param array<string, scalar|array|null> $credential
     */
    public function complete(
        string $challengeId,
        array $credential,
        string $ipAddress,
        string $userAgent
    ): PasskeyAuthenticationResult;
}
