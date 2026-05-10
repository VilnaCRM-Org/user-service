<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\CompleteTwoFactorCommandResponse;

final class CompleteTwoFactorCommandResponseTest extends UnitTestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $accessToken = $this->faker->sha256();
        $refreshToken = $this->faker->sha256();
        $recoveryCodesRemaining = $this->faker->numberBetween(1, 10);
        $warningMessage = $this->faker->sentence();

        $response = new CompleteTwoFactorCommandResponse(
            $accessToken,
            $refreshToken,
            $recoveryCodesRemaining,
            $warningMessage
        );

        $this->assertSame($accessToken, $response->getAccessToken());
        $this->assertSame($refreshToken, $response->getRefreshToken());
        $this->assertSame($recoveryCodesRemaining, $response->getRecoveryCodesRemaining());
        $this->assertSame($warningMessage, $response->getWarningMessage());
    }

    public function testDefaultRememberMeIsFalse(): void
    {
        $response = new CompleteTwoFactorCommandResponse(
            $this->faker->sha256(),
            $this->faker->sha256()
        );

        $this->assertFalse($response->isRememberMe());
    }

    public function testNullableFieldsDefaultToNull(): void
    {
        $response = new CompleteTwoFactorCommandResponse(
            $this->faker->sha256(),
            $this->faker->sha256()
        );

        $this->assertNull($response->getRecoveryCodesRemaining());
        $this->assertNull($response->getWarningMessage());
    }

    public function testWithRememberMeReturnsTrueOnClone(): void
    {
        $response = new CompleteTwoFactorCommandResponse(
            $this->faker->sha256(),
            $this->faker->sha256()
        );

        $clone = $response->withRememberMe();

        $this->assertTrue($clone->isRememberMe());
    }

    public function testWithRememberMeDoesNotMutateOriginal(): void
    {
        $response = new CompleteTwoFactorCommandResponse(
            $this->faker->sha256(),
            $this->faker->sha256()
        );

        $clone = $response->withRememberMe();

        $this->assertFalse($response->isRememberMe());
        $this->assertTrue($clone->isRememberMe());
        $this->assertNotSame($response, $clone);
    }

    public function testWithRememberMePreservesOtherProperties(): void
    {
        $accessToken = $this->faker->sha256();
        $refreshToken = $this->faker->sha256();
        $recoveryCodesRemaining = $this->faker->numberBetween(1, 10);
        $warningMessage = $this->faker->sentence();

        $response = new CompleteTwoFactorCommandResponse(
            $accessToken,
            $refreshToken,
            $recoveryCodesRemaining,
            $warningMessage
        );

        $clone = $response->withRememberMe();

        $this->assertSame($accessToken, $clone->getAccessToken());
        $this->assertSame($refreshToken, $clone->getRefreshToken());
        $this->assertSame($recoveryCodesRemaining, $clone->getRecoveryCodesRemaining());
        $this->assertSame($warningMessage, $clone->getWarningMessage());
    }
}
