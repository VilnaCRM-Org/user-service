<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Application\Command;

use App\OAuth\Application\Command\HandleOAuthCallbackCommand;
use App\OAuth\Application\DTO\HandleOAuthCallbackResponse;
use App\Tests\Unit\UnitTestCase;

final class HandleOAuthCallbackCommandTest extends UnitTestCase
{
    public function testConstructSetsProperties(): void
    {
        $provider = $this->faker->word();
        $code = $this->faker->sha256();
        $state = $this->faker->sha256();
        $flowBindingToken = $this->faker->sha256();
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();

        $command = new HandleOAuthCallbackCommand(
            $provider,
            $code,
            $state,
            $flowBindingToken,
            $ipAddress,
            $userAgent,
        );

        $this->assertSame($provider, $command->provider);
        $this->assertSame($code, $command->code);
        $this->assertSame($state, $command->state);
        $this->assertSame($flowBindingToken, $command->flowBindingToken);
        $this->assertSame($ipAddress, $command->ipAddress);
        $this->assertSame($userAgent, $command->userAgent);
    }

    public function testSetAndGetResponse(): void
    {
        $command = new HandleOAuthCallbackCommand(
            $this->faker->word(),
            $this->faker->sha256(),
            $this->faker->sha256(),
            $this->faker->sha256(),
            $this->faker->ipv4(),
            $this->faker->userAgent(),
        );

        $response = new HandleOAuthCallbackResponse(
            false,
            $this->faker->sha256(),
            $this->faker->sha256(),
        );

        $command->setResponse($response);

        $this->assertSame($response, $command->getResponse());
    }
}
