<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Tests\Behat\UserContext\Input\TwoFactorCodeInput;
use Behat\Behat\Context\Context;
use OTPHP\TOTP;

final class TwoFactorManagementRequestContext implements Context
{
    public function __construct(
        private UserOperationsState $state,
    ) {
    }

    /**
     * @Given confirming 2FA with code :code
     */
    public function confirmingTwoFactorWithCode(string $code): void
    {
        $this->state->requestBody = new TwoFactorCodeInput($code);
    }

    /**
     * @Given confirming 2FA with a valid TOTP code
     */
    public function confirmingTwoFactorWithAValidTotpCode(): void
    {
        $secret = $this->state->twoFactorSecret;

        if (!is_string($secret) || $secret === '') {
            $responseData = json_decode(
                (string) $this->state->response?->getContent(),
                true
            );
            $secret = is_array($responseData)
                ? ($responseData['secret'] ?? '')
                : '';
        }

        if (!is_string($secret) || $secret === '') {
            throw new \RuntimeException('2FA secret is missing from scenario state.');
        }

        $this->state->twoFactorSecret = $secret;
        $this->confirmingTwoFactorWithCode(
            TOTP::create($secret)->now()
        );
    }

    /**
     * @Given disabling 2FA with code :code
     */
    public function disablingTwoFactorWithCode(string $code): void
    {
        $this->state->requestBody = new TwoFactorCodeInput($code);
    }
}
