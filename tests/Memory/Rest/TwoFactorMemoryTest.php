<?php

declare(strict_types=1);

namespace App\Tests\Memory\Rest;

use Symfony\Component\HttpFoundation\Response;

final class TwoFactorMemoryTest extends RestMemoryWebTestCase
{
    public function testSetupTwoFactorScenarioReusesSameKernelAcrossRepeatedRequests(): void
    {
        $this->runRepeatedRestScenario('setupTwoFactor', function (): void {
            $password = $this->generatePassword();
            $user = $this->createConfirmedUser($password);
            $signIn = $this->signIn($user, $password);

            ['response' => $response, 'body' => $body] = $this->requestJson(
                'POST',
                '/api/2fa/setup',
                [],
                ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $signIn['access_token'])]
            );

            self::assertSame(Response::HTTP_OK, $response->getStatusCode());
            self::assertIsString($body['secret'] ?? null);
            self::assertIsString($body['otpauth_uri'] ?? null);
        });
    }

    public function testConfirmTwoFactorScenarioReusesSameKernelAcrossRepeatedRequests(): void
    {
        $this->runRepeatedRestScenario('confirmTwoFactor', function (): void {
            $password = $this->generatePassword();
            $user = $this->createConfirmedUser($password);
            $signIn = $this->signIn($user, $password);
            $secret = $this->setupTwoFactor($signIn['access_token']);
            $recoveryCodes = $this->confirmTwoFactor($signIn['access_token'], $secret);

            self::assertCount(8, $recoveryCodes);
            self::assertIsString($recoveryCodes[0]);
        });
    }

    public function testRegenerateRecoveryCodesScenarioReusesKernelAcrossRepeatedRequests(): void
    {
        $this->runRepeatedRestScenario('regenerateRecoveryCodes', function (): void {
            $password = $this->generatePassword();
            $user = $this->createConfirmedUser($password);
            $signIn = $this->signIn($user, $password);
            $secret = $this->setupTwoFactor($signIn['access_token']);
            $this->confirmTwoFactor($signIn['access_token'], $secret);

            ['response' => $response, 'body' => $body] = $this->requestJson(
                'POST',
                '/api/2fa/recovery-codes',
                [],
                ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $signIn['access_token'])]
            );

            self::assertSame(Response::HTTP_OK, $response->getStatusCode());
            self::assertIsArray($body['recovery_codes'] ?? null);
            self::assertCount(8, $body['recovery_codes']);
        });
    }

    public function testSigninTwoFactorScenarioReusesSameKernelAcrossRepeatedRequests(): void
    {
        $this->runRepeatedRestScenario('signinTwoFactor', function (): void {
            $password = $this->generatePassword();
            $user = $this->createConfirmedUser($password);
            $primarySignIn = $this->signIn($user, $password);
            $secret = $this->setupTwoFactor($primarySignIn['access_token']);
            $recoveryCodes = $this->confirmTwoFactor($primarySignIn['access_token'], $secret);
            $pendingTwoFactor = $this->signInExpectingTwoFactor($user, $password);

            ['response' => $response, 'body' => $body] = $this->requestJson(
                'POST',
                '/api/signin/2fa',
                [
                    'pendingSessionId' => $pendingTwoFactor['pending_session_id'],
                    'twoFactorCode' => $recoveryCodes[0],
                ]
            );

            self::assertSame(Response::HTTP_OK, $response->getStatusCode());
            self::assertIsString($body['access_token'] ?? null);
            self::assertIsString($body['refresh_token'] ?? null);
        });
    }

    public function testDisableTwoFactorScenarioReusesSameKernelAcrossRepeatedRequests(): void
    {
        $this->runRepeatedRestScenario('disableTwoFactor', function (): void {
            $password = $this->generatePassword();
            $user = $this->createConfirmedUser($password);
            $signIn = $this->signIn($user, $password);
            $secret = $this->setupTwoFactor($signIn['access_token']);
            $recoveryCodes = $this->confirmTwoFactor($signIn['access_token'], $secret);

            ['response' => $response] = $this->requestJson(
                'POST',
                '/api/2fa/disable',
                ['twoFactorCode' => $recoveryCodes[0]],
                ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $signIn['access_token'])]
            );

            self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        });
    }
}
