<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Application\DTO;

use App\OAuth\Application\DTO\InitiateOAuthResponse;
use App\Tests\Unit\UnitTestCase;

final class InitiateOAuthResponseTest extends UnitTestCase
{
    public function testConstructSetsProperties(): void
    {
        $authorizationUrl = $this->faker->url();
        $state = $this->faker->sha256();
        $flowBindingToken = $this->faker->sha256();

        $response = new InitiateOAuthResponse(
            $authorizationUrl,
            $state,
            $flowBindingToken,
        );

        $this->assertSame($authorizationUrl, $response->authorizationUrl);
        $this->assertSame($state, $response->state);
        $this->assertSame($flowBindingToken, $response->flowBindingToken);
    }
}
