<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SignInCommandResponse;

final class SignInCommandResponseTest extends UnitTestCase
{
    public function testAccessorsReturnProvidedValues(): void
    {
        $response = new SignInCommandResponse(
            true,
            $this->faker->sha256(),
            $this->faker->sha256(),
            $this->faker->uuid()
        );

        $this->assertTrue($response->isTwoFactorEnabled());
        $this->assertNotNull($response->getAccessToken());
        $this->assertNotNull($response->getRefreshToken());
        $this->assertNotNull($response->getPendingSessionId());
    }

    public function testAccessorsReturnNullsByDefault(): void
    {
        $response = new SignInCommandResponse(false);

        $this->assertFalse($response->isTwoFactorEnabled());
        $this->assertNull($response->getAccessToken());
        $this->assertNull($response->getRefreshToken());
        $this->assertNull($response->getPendingSessionId());
    }
}
