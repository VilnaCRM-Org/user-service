<?php

declare(strict_types=1);

namespace App\Tests\Memory\Rest;

use Symfony\Component\HttpFoundation\Response;

final class PasswordResetMemoryTest extends RestMemoryWebTestCase
{
    public function testResetPasswordScenarioReusesSameKernelAcrossRepeatedRequests(): void
    {
        $this->runRepeatedRestScenario('resetPassword', function (): void {
            $user = $this->createConfirmedUser();

            ['response' => $response] = $this->requestJson(
                'POST',
                '/api/reset-password',
                ['email' => $user->getEmail()]
            );

            self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        });
    }

    public function testResetPasswordConfirmScenarioReusesSameKernelAcrossRepeatedRequests(): void
    {
        $this->runRepeatedRestScenario('resetPasswordConfirm', function (): void {
            $user = $this->createConfirmedUser();
            $token = $this->savePasswordResetToken($user);
            $password = $this->generatePassword();

            ['response' => $response] = $this->requestJson(
                'POST',
                '/api/reset-password/confirm',
                [
                    'token' => $token->getTokenValue(),
                    'newPassword' => $password,
                ]
            );

            self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        });
    }
}
