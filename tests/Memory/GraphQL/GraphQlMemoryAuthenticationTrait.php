<?php

declare(strict_types=1);

namespace App\Tests\Memory\GraphQL;

use OTPHP\TOTP;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait GraphQlMemoryAuthenticationTrait
{
    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    protected function signInGraphQl(
        KernelBrowser $client,
        string $email,
        string $password,
    ): array {
        return $this->executeUserMutation(
            $client,
            'signInUser',
            sprintf('email: "%s", password: "%s"', $email, $password),
            'success twoFactorEnabled accessToken refreshToken pendingSessionId',
        );
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    protected function refreshTokenGraphQl(
        KernelBrowser $client,
        string $refreshToken,
    ): array {
        return $this->executeUserMutation(
            $client,
            'refreshTokenUser',
            sprintf('refreshToken: "%s"', $refreshToken),
            'success accessToken refreshToken',
        );
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    protected function setupTwoFactorGraphQl(
        KernelBrowser $client,
        string $accessToken,
    ): array {
        return $this->executeUserMutation(
            $client,
            'setupTwoFactorUser',
            '',
            'success otpauthUri secret',
            $accessToken,
        );
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    protected function confirmTwoFactorGraphQl(
        KernelBrowser $client,
        string $accessToken,
        string $code,
    ): array {
        $result = $this->executeGraphQlAllowingErrors(
            $client,
            $this->buildRawUserMutation(
                'confirmTwoFactorUser',
                sprintf('twoFactorCode: "%s"', $code),
                'success recoveryCodes',
            ),
            $accessToken,
        );

        if ($this->isInvalidTwoFactorCodeResponse($result['body'])) {
            return [];
        }

        return $this->extractGraphQlUserPayload($result, 'confirmTwoFactorUser');
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    protected function disableTwoFactorGraphQl(
        KernelBrowser $client,
        string $accessToken,
        string $code,
    ): array {
        return $this->executeUserMutation(
            $client,
            'disableTwoFactorUser',
            sprintf('twoFactorCode: "%s"', $code),
            'success',
            $accessToken,
        );
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    protected function regenerateRecoveryCodesGraphQl(
        KernelBrowser $client,
        string $accessToken,
    ): array {
        return $this->executeUserMutation(
            $client,
            'regenerateRecoveryCodesUser',
            '',
            'success recoveryCodes',
            $accessToken,
        );
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    protected function signOutGraphQl(
        KernelBrowser $client,
        string $accessToken,
    ): array {
        return $this->executeUserMutation(
            $client,
            'signOutUser',
            '',
            'success',
            $accessToken,
        );
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    protected function signOutAllGraphQl(
        KernelBrowser $client,
        string $accessToken,
    ): array {
        return $this->executeUserMutation(
            $client,
            'signOutAllUser',
            '',
            'success',
            $accessToken,
        );
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    protected function completeTwoFactorGraphQl(
        KernelBrowser $client,
        string $pendingSessionId,
        string $code,
    ): array {
        return $this->executeUserMutation(
            $client,
            'completeTwoFactorUser',
            sprintf(
                'pendingSessionId: "%s", twoFactorCode: "%s"',
                $pendingSessionId,
                $code,
            ),
            'success twoFactorEnabled accessToken refreshToken recoveryCodesRemaining warning',
        );
    }

    /**
     * @return array{secret: string, recoveryCodes: list<string>}
     */
    protected function enableTwoFactorGraphQl(
        KernelBrowser $client,
        string $accessToken,
    ): array {
        $setup = $this->setupTwoFactorGraphQl($client, $accessToken);
        $secret = $setup['secret'] ?? null;

        $this->assertIsString($secret);
        $this->assertNotSame('', $secret);

        return [
            'secret' => $secret,
            'recoveryCodes' => $this->successfulTwoFactorRecoveryCodes(
                $client,
                $accessToken,
                $secret,
            ),
        ];
    }

    /**
     * @return list<string>
     */
    private function successfulTwoFactorRecoveryCodes(
        KernelBrowser $client,
        string $accessToken,
        string $secret,
    ): array {
        $confirm = null;

        foreach ($this->buildTwoFactorCodesWithinStepWindow($secret) as $code) {
            $confirm = $this->confirmTwoFactorGraphQl($client, $accessToken, $code);
            if (($confirm['success'] ?? false) === true) {
                break;
            }
        }

        $this->assertIsArray($confirm);
        $this->assertSame(true, $confirm['success'] ?? null);
        $recoveryCodes = $confirm['recoveryCodes'] ?? null;

        $this->assertIsArray($recoveryCodes);
        $this->assertNotSame([], $recoveryCodes);

        return array_values(
            array_map(
                static fn ($value): string => (string) $value,
                $recoveryCodes,
            ),
        );
    }

    /**
     * @return list<string>
     */
    private function buildTwoFactorCodesWithinStepWindow(string $secret): array
    {
        $totp = TOTP::create($secret);
        $timestamp = time();
        $period = $totp->getPeriod();

        return array_values(array_unique(array_map(
            static fn (int $offset): string => $totp->at(max(0, $timestamp + ($period * $offset))),
            [-2, -1, 0, 1, 2],
        )));
    }
}
