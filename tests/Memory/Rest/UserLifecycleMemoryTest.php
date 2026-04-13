<?php

declare(strict_types=1);

namespace App\Tests\Memory\Rest;

use Symfony\Component\HttpFoundation\Response;

final class UserLifecycleMemoryTest extends RestMemoryWebTestCase
{
    public function testCreateUserScenarioReusesSameKernelAcrossRepeatedRequests(): void
    {
        $this->runRepeatedRestScenario('createUser', function (): void {
            ['response' => $response, 'body' => $body] = $this->requestJson(
                'POST',
                '/api/users',
                [
                    'email' => strtolower($this->faker->unique()->safeEmail()),
                    'initials' => strtoupper($this->faker->lexify('??')),
                    'password' => 'Aa1!' . strtolower($this->faker->regexify('[A-Za-z0-9]{12}')),
                ]
            );

            self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
            self::assertArrayHasKey('id', $body);
        });
    }

    public function testConfirmUserScenarioReusesSameKernelAcrossRepeatedRequests(): void
    {
        $this->runRepeatedRestScenario('confirmUser', function (): void {
            $user = $this->createUnconfirmedUser();
            $token = $this->saveConfirmationToken($user);

            ['response' => $response, 'body' => $body] = $this->requestJson(
                'PATCH',
                '/api/users/confirm',
                ['token' => $token->getTokenValue()],
                [],
                [],
                'application/merge-patch+json'
            );

            self::assertSame(Response::HTTP_OK, $response->getStatusCode());
            $reloadedUser = $this->userRepository()->findById($user->getId());
            self::assertInstanceOf(\App\User\Domain\Entity\UserInterface::class, $reloadedUser);
            self::assertTrue($reloadedUser->isConfirmed());
        });
    }

    public function testCreateUserBatchScenarioReusesSameKernelAcrossRepeatedRequests(): void
    {
        $headers = $this->createServiceAuthorizationHeader();

        $this->runRepeatedRestScenario('createUserBatch', function () use ($headers): void {
            ['response' => $response] = $this->requestJson(
                'POST',
                '/api/users/batch',
                ['users' => $this->buildBatchUsersPayload()],
                $headers
            );

            self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        });
    }

    public function testGetUsersScenarioReusesSameKernelAcrossRepeatedRequests(): void
    {
        $password = 'Aa1!' . strtolower($this->faker->regexify('[A-Za-z0-9]{12}'));
        $user = $this->createConfirmedUser($password);
        $headers = $this->createUserAuthorizationHeader($user->getId());

        $this->runRepeatedRestScenario('getUsers', function () use ($headers): void {
            ['response' => $response] = $this->requestJson(
                'GET',
                '/api/users?page=1&itemsPerPage=50',
                [],
                $headers
            );

            self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        }, 5);
    }

    public function testUpdateUserScenarioReusesSameKernelAcrossRepeatedRequests(): void
    {
        $this->runRepeatedRestScenario('updateUser', function (): void {
            $password = 'Aa1!' . strtolower($this->faker->regexify('[A-Za-z0-9]{12}'));
            $user = $this->createConfirmedUser($password);
            $headers = $this->createUserAuthorizationHeader($user->getId());

            ['response' => $response, 'body' => $body] = $this->requestJson(
                'PATCH',
                '/api/users/' . $user->getId(),
                [
                    'email' => strtolower($this->faker->unique()->safeEmail()),
                    'initials' => strtoupper($this->faker->lexify('??')),
                    'newPassword' => $password,
                    'oldPassword' => $password,
                ],
                $headers,
                [],
                'application/merge-patch+json'
            );

            self::assertSame(Response::HTTP_OK, $response->getStatusCode());
            self::assertArrayHasKey('email', $body);
        });
    }

    public function testReplaceUserScenarioReusesSameKernelAcrossRepeatedRequests(): void
    {
        $this->runRepeatedRestScenario('replaceUser', function (): void {
            $password = 'Aa1!' . strtolower($this->faker->regexify('[A-Za-z0-9]{12}'));
            $user = $this->createConfirmedUser($password);
            $headers = $this->createUserAuthorizationHeader($user->getId());

            ['response' => $response, 'body' => $body] = $this->requestJson(
                'PUT',
                '/api/users/' . $user->getId(),
                [
                    'email' => strtolower($this->faker->unique()->safeEmail()),
                    'initials' => strtoupper($this->faker->lexify('??')),
                    'newPassword' => $password,
                    'oldPassword' => $password,
                ],
                $headers
            );

            self::assertSame(Response::HTTP_OK, $response->getStatusCode());
            self::assertArrayHasKey('email', $body);
        });
    }

    public function testDeleteUserScenarioReusesSameKernelAcrossRepeatedRequests(): void
    {
        $this->runRepeatedRestScenario('deleteUser', function (): void {
            $password = 'Aa1!' . strtolower($this->faker->regexify('[A-Za-z0-9]{12}'));
            $user = $this->createConfirmedUser($password);
            $headers = $this->createUserAuthorizationHeader($user->getId());

            ['response' => $response] = $this->requestJson(
                'DELETE',
                '/api/users/' . $user->getId(),
                [],
                $headers
            );

            self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        });
    }

    public function testResendEmailToUserScenarioReusesSameKernelAcrossRepeatedRequests(): void
    {
        $this->runRepeatedRestScenario('resendEmailToUser', function (): void {
            $password = 'Aa1!' . strtolower($this->faker->regexify('[A-Za-z0-9]{12}'));
            $user = $this->createUnconfirmedUser($password);
            $headers = $this->createUserAuthorizationHeader($user->getId());

            ['response' => $response] = $this->requestJson(
                'POST',
                '/api/users/' . $user->getId() . '/resend-confirmation-email',
                [],
                $headers
            );

            self::assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_TOO_MANY_REQUESTS]);
        });
    }
}
