<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Faker\Factory;
use Faker\Generator;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class UserHttpCacheTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    public function testGetUserIncludesCacheHeadersAndEtagChangesAfterUpdate(): void
    {
        $faker = Factory::create();
        $client = self::createClient();

        [$userId, $password] = $this->createUser($client, $faker);
        $etag = $this->assertCacheHeaders(
            $this->fetchUserResponse($client, $userId)->getHeaders()
        );

        $newInitials = $faker->name();
        $newPassword = 'B' . $faker->lexify('????????') . '2';
        $this->updateUser($client, $userId, $password, $newInitials, $newPassword);

        $updatedHeaders = $this->fetchUserResponse($client, $userId)->getHeaders();
        $updatedEtag = $this->getEtag($updatedHeaders);

        self::assertNotSame($etag, $updatedEtag);
    }

    /**
     * @return array{string, string}
     */
    private function createUser(Client $client, Generator $faker): array
    {
        $password = 'A' . $faker->lexify('????????') . '1';
        $createResponse = $client->request('POST', '/api/users', [
            'json' => [
                'email' => $faker->unique()->safeEmail(),
                'initials' => $faker->name(),
                'password' => $password,
            ],
            'headers' => [
                'accept' => 'application/json',
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
        $createdUser = $createResponse->toArray();

        return [$createdUser['id'], $password];
    }

    private function fetchUserResponse(Client $client, string $userId): ResponseInterface
    {
        $response = $client->request('GET', '/api/users/' . $userId, [
            'headers' => [
                'accept' => 'application/json',
            ],
        ]);

        self::assertResponseStatusCodeSame(200);

        return $response;
    }

    private function updateUser(
        Client $client,
        string $userId,
        string $password,
        string $newInitials,
        string $newPassword
    ): void {
        $client->request('PATCH', '/api/users/' . $userId, [
            'json' => [
                'initials' => $newInitials,
                'oldPassword' => $password,
                'newPassword' => $newPassword,
            ],
            'headers' => [
                'accept' => 'application/json',
                'content-type' => 'application/merge-patch+json',
            ],
        ]);
        self::assertResponseStatusCodeSame(200);
    }

    /**
     * @param array<string, array<int, string>> $headers
     */
    private function assertCacheHeaders(array $headers): string
    {
        $cacheControl = $headers['cache-control'][0] ?? '';
        $etag = $headers['etag'][0] ?? '';
        $varyValues = $headers['vary'] ?? [];
        $vary = strtolower(implode(', ', $varyValues));

        self::assertNotSame('', $etag);
        self::assertStringContainsString('max-age=600', $cacheControl);
        self::assertStringContainsString('private', $cacheControl);
        self::assertStringContainsString('accept', $vary);
        self::assertStringContainsString('accept-language', $vary);
        self::assertStringContainsString('authorization', $vary);

        return $etag;
    }

    /**
     * @param array<string, array<int, string>> $headers
     */
    private function getEtag(array $headers): string
    {
        return $headers['etag'][0] ?? '';
    }
}
