<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Application\DTO;

use App\OAuth\Application\DTO\HandleOAuthCallbackResponse;
use App\Tests\Unit\UnitTestCase;

final class HandleOAuthCallbackResponseTest extends UnitTestCase
{
    public function testDirectSignInResponse(): void
    {
        $accessToken = $this->faker->sha256();
        $refreshToken = $this->faker->sha256();

        $response = new HandleOAuthCallbackResponse(
            false,
            $accessToken,
            $refreshToken,
        );

        $this->assertFalse($response->isTwoFactorEnabled());
        $this->assertSame($accessToken, $response->getAccessToken());
        $this->assertSame($refreshToken, $response->getRefreshToken());
        $this->assertNull($response->getPendingSessionId());
    }

    public function testTwoFactorResponse(): void
    {
        $pendingId = $this->faker->uuid();

        $response = new HandleOAuthCallbackResponse(
            true,
            null,
            null,
            $pendingId,
        );

        $this->assertTrue($response->isTwoFactorEnabled());
        $this->assertNull($response->getAccessToken());
        $this->assertNull($response->getRefreshToken());
        $this->assertSame($pendingId, $response->getPendingSessionId());
    }
}
