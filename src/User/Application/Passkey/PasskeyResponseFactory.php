<?php

declare(strict_types=1);

namespace App\User\Application\Passkey;

use App\User\Application\DTO\PasskeyAuthenticationResult;
use App\User\Application\DTO\PasskeyOptionsResult;

final readonly class PasskeyResponseFactory
{
    /**
     * @return array<string, scalar|array|null>
     */
    public function createOptionsResponse(PasskeyOptionsResult $result): array
    {
        return [
            'challenge_id' => $result->getChallenge()->getId(),
            'public_key' => $result->getPublicKeyOptions(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function createTokenResponse(PasskeyAuthenticationResult $result): array
    {
        return [
            'access_token' => $result->getAccessToken(),
            'refresh_token' => $result->getRefreshToken(),
        ];
    }
}
