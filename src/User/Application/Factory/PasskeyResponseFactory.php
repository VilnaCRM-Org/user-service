<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

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
     * @return array<string, bool|string>
     *
     * @psalm-return array{2fa_enabled: bool, access_token?: string, refresh_token?: string, pending_session_id?: string}
     */
    public function createTokenResponse(PasskeyAuthenticationResult $result): array
    {
        $body = [
            '2fa_enabled' => $result->isTwoFactorEnabled(),
        ];

        if ($result->isTwoFactorEnabled()) {
            $pendingSessionId = $result->getPendingSessionId();
            if ($pendingSessionId !== null) {
                $body['pending_session_id'] = $pendingSessionId;
            }

            return $body;
        }

        $body['access_token'] = $result->getAccessToken();
        $body['refresh_token'] = $result->getRefreshToken();

        return $body;
    }
}
