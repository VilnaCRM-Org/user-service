<?php

declare(strict_types=1);

namespace App\Tests\Memory\Rest;

use Symfony\Component\HttpFoundation\Response;

final class AuthTokenMemoryTest extends RestMemoryWebTestCase
{
    public function testSigninScenarioReusesSameKernelAcrossRepeatedRequests(): void
    {
        $this->runRepeatedRestScenario('signin', function (): void {
            $password = $this->generatePassword();
            $user = $this->createConfirmedUser($password);
            ['response' => $response, 'body' => $body] = $this->requestJson(
                'POST',
                '/api/signin',
                [
                    'email' => $user->getEmail(),
                    'password' => $password,
                    'rememberMe' => false,
                ]
            );

            self::assertSame(Response::HTTP_OK, $response->getStatusCode());
            self::assertIsString($body['access_token'] ?? null);
            self::assertIsString($body['refresh_token'] ?? null);
        });
    }

    public function testRefreshTokenScenarioReusesSameKernelAcrossRepeatedRequests(): void
    {
        $this->runRepeatedRestScenario('refreshToken', function (): void {
            $password = $this->generatePassword();
            $user = $this->createConfirmedUser($password);
            $signIn = $this->signIn($user, $password);
            ['response' => $response, 'body' => $body] = $this->requestJson(
                'POST',
                '/api/token',
                ['refreshToken' => $signIn['refresh_token']]
            );

            self::assertSame(Response::HTTP_OK, $response->getStatusCode());
            self::assertIsString($body['access_token'] ?? null);
            self::assertIsString($body['refresh_token'] ?? null);
        });
    }

    public function testSignoutScenarioReusesSameKernelAcrossRepeatedRequests(): void
    {
        $this->runRepeatedRestScenario('signout', function (): void {
            $password = $this->generatePassword();
            $user = $this->createConfirmedUser($password);
            $signIn = $this->signIn($user, $password);
            ['response' => $response] = $this->requestJson(
                'POST',
                '/api/signout',
                [],
                ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $signIn['access_token'])]
            );

            self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        });
    }

    public function testSignoutAllScenarioReusesSameKernelAcrossRepeatedRequests(): void
    {
        $this->runRepeatedRestScenario('signoutAll', function (): void {
            $password = $this->generatePassword();
            $user = $this->createConfirmedUser($password);
            $signIn = $this->signIn($user, $password);
            $this->signIn($user, $password);

            ['response' => $response] = $this->requestJson(
                'POST',
                '/api/signout/all',
                [],
                ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $signIn['access_token'])]
            );

            self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        });
    }
}
