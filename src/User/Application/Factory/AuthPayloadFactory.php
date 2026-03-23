<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\DTO\AuthPayload;
use App\User\Application\DTO\CompleteTwoFactorCommandResponse;
use App\User\Application\DTO\ConfirmTwoFactorCommandResponse;
use App\User\Application\DTO\RefreshTokenCommandResponse;
use App\User\Application\DTO\RegenerateRecoveryCodesCommandResponse;
use App\User\Application\DTO\SetupTwoFactorCommandResponse;
use App\User\Application\DTO\SignInCommandResponse;

final class AuthPayloadFactory
{
    public function createSuccessPayload(): AuthPayload
    {
        return (new AuthPayload())->setId('auth-success');
    }

    public function createFromSignInResponse(
        SignInCommandResponse $response
    ): AuthPayload {
        return (new AuthPayload())
            ->setId('auth-sign-in')
            ->setTwoFactorEnabled($response->isTwoFactorEnabled())
            ->setAccessToken($response->getAccessToken())
            ->setRefreshToken($response->getRefreshToken())
            ->setPendingSessionId($response->getPendingSessionId());
    }

    public function createFromCompleteTwoFactorResponse(
        CompleteTwoFactorCommandResponse $response
    ): AuthPayload {
        return (new AuthPayload())
            ->setId('auth-complete-two-factor')
            ->setTwoFactorEnabled(true)
            ->setAccessToken($response->getAccessToken())
            ->setRefreshToken($response->getRefreshToken())
            ->setRecoveryCodesRemaining($response->getRecoveryCodesRemaining())
            ->setWarning($response->getWarningMessage());
    }

    public function createFromRefreshTokenResponse(
        RefreshTokenCommandResponse $response
    ): AuthPayload {
        return (new AuthPayload())
            ->setId('auth-refresh-token')
            ->setAccessToken($response->getAccessToken())
            ->setRefreshToken($response->getRefreshToken());
    }

    public function createFromSetupTwoFactorResponse(
        SetupTwoFactorCommandResponse $response
    ): AuthPayload {
        return (new AuthPayload())
            ->setId('auth-setup-two-factor')
            ->setOtpauthUri($response->getOtpauthUri())
            ->setSecret($response->getSecret());
    }

    public function createFromConfirmTwoFactorResponse(
        ConfirmTwoFactorCommandResponse $response
    ): AuthPayload {
        return (new AuthPayload())
            ->setId('auth-confirm-two-factor')
            ->setRecoveryCodes($response->getRecoveryCodes());
    }

    public function createFromRegenerateRecoveryCodesResponse(
        RegenerateRecoveryCodesCommandResponse $response
    ): AuthPayload {
        return (new AuthPayload())
            ->setId('auth-regenerate-recovery-codes')
            ->setRecoveryCodes($response->getRecoveryCodes());
    }
}
