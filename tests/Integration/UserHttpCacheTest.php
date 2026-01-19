<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Faker\Factory;

final class UserHttpCacheTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    public function testGetUserIncludesCacheHeadersAndEtagChangesAfterUpdate(): void
    {
        $faker = Factory::create();
        $client = static::createClient();

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
        $userId = $createdUser['id'];

        $getResponse = $client->request('GET', '/api/users/' . $userId, [
            'headers' => [
                'accept' => 'application/json',
            ],
        ]);

        self::assertResponseStatusCodeSame(200);

        $headers = $getResponse->getHeaders();
        $cacheControl = $headers['cache-control'][0] ?? '';
        $etag = $headers['etag'][0] ?? '';
        $vary = strtolower($headers['vary'][0] ?? '');

        self::assertNotSame('', $etag);
        self::assertStringContainsString('max-age=600', $cacheControl);
        self::assertStringContainsString('s-maxage=600', $cacheControl);
        self::assertStringContainsString('accept', $vary);
        self::assertStringContainsString('accept-language', $vary);
        self::assertStringContainsString('authorization', $vary);

        $newInitials = $faker->name();
        $newPassword = 'B' . $faker->lexify('????????') . '2';
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

        $updatedResponse = $client->request('GET', '/api/users/' . $userId, [
            'headers' => [
                'accept' => 'application/json',
            ],
        ]);

        self::assertResponseStatusCodeSame(200);

        $updatedHeaders = $updatedResponse->getHeaders();
        $updatedEtag = $updatedHeaders['etag'][0] ?? '';

        self::assertNotSame($etag, $updatedEtag);
    }
}
