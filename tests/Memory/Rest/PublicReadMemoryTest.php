<?php

declare(strict_types=1);

namespace App\Tests\Memory\Rest;

use Symfony\Component\HttpFoundation\Response;

final class PublicReadMemoryTest extends RestMemoryWebTestCase
{
    public function testHealthScenarioAcceptsCookieBackedRequests(): void
    {
        ['response' => $response] = $this->requestJson(
            'GET',
            '/api/health',
            [],
            [],
            ['memory-suite' => '1'],
        );

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testServiceAuthorizationHeaderCanBeGeneratedForMemorySuite(): void
    {
        $headers = $this->createUserAuthorizationHeader(
            $this->faker->uuid(),
            ['ROLE_SERVICE'],
        );

        self::assertArrayHasKey('HTTP_AUTHORIZATION', $headers);
        self::assertStringStartsWith('Bearer ', $headers['HTTP_AUTHORIZATION']);
    }

    public function testHealthScenarioReusesSameKernelAcrossRepeatedRequests(): void
    {
        $this->runRepeatedRestScenario('health', function (): void {
            ['response' => $response] = $this->requestJson('GET', '/api/health');

            self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        }, 5);
    }

    public function testGetUserScenarioReusesSameKernelAcrossRepeatedRequests(): void
    {
        $password = $this->generatePassword();
        $user = $this->createConfirmedUser($password);
        $headers = $this->createUserAuthorizationHeader($user->getId());

        $this->runRepeatedRestScenario('getUser', function () use ($user, $headers): void {
            ['response' => $response, 'body' => $body] = $this->requestJson(
                'GET',
                '/api/users/' . $user->getId(),
                [],
                $headers
            );

            self::assertSame(Response::HTTP_OK, $response->getStatusCode());
            self::assertSame($user->getId(), $body['id'] ?? null);
        }, 5);
    }

    public function testCachePerformanceScenarioReusesSameKernelAcrossRepeatedRequests(): void
    {
        $password = $this->generatePassword();
        $user = $this->createConfirmedUser($password);
        $headers = $this->createUserAuthorizationHeader($user->getId());

        if ($this->container->has('cache.user')) {
            $this->container->get('cache.user')->clear();
        }

        $this->runRepeatedRestScenario('cachePerformance', function () use ($user, $headers): void {
            ['response' => $response, 'body' => $body] = $this->requestJson(
                'GET',
                '/api/users/' . $user->getId(),
                [],
                $headers
            );

            self::assertSame(Response::HTTP_OK, $response->getStatusCode());
            self::assertSame($user->getEmail(), $body['email'] ?? null);
        }, 5);
    }

    public function testUnauthorizedGetUsersReusesSameKernelAcrossRepeatedRequests(): void
    {
        $this->runRepeatedRestScenario('getUsers', function (): void {
            ['response' => $response] = $this->requestJson('GET', '/api/users');

            self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        });
    }
}
