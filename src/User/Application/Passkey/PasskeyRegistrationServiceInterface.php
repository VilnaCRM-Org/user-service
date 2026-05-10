<?php

declare(strict_types=1);

namespace App\User\Application\Passkey;

use App\User\Application\DTO\PasskeyAuthenticationResult;
use App\User\Application\DTO\PasskeyOptionsResult;
use App\User\Domain\Entity\PasskeyCredential;

interface PasskeyRegistrationServiceInterface
{
    public function startSignup(
        string $email,
        string $initials,
        string $displayName
    ): PasskeyOptionsResult;

    /**
     * @param array<string, scalar|array|null> $credential
     */
    public function completeSignup(
        string $challengeId,
        array $credential,
        string $label,
        bool $rememberMe,
        string $ipAddress,
        string $userAgent
    ): PasskeyAuthenticationResult;

    public function startRegistration(
        string $userId,
        string $email
    ): PasskeyOptionsResult;

    /**
     * @param array<string, scalar|array|null> $credential
     */
    public function completeRegistration(
        string $challengeId,
        array $credential,
        string $label,
        string $currentUserId
    ): PasskeyCredential;
}
