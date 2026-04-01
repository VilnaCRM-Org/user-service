<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Application\Command;

use App\OAuth\Application\Command\InitiateOAuthCommand;
use App\OAuth\Application\DTO\InitiateOAuthResponse;
use App\Tests\Unit\UnitTestCase;

final class InitiateOAuthCommandTest extends UnitTestCase
{
    public function testConstructSetsProperties(): void
    {
        $provider = $this->faker->word();
        $redirectUri = $this->faker->url();

        $command = new InitiateOAuthCommand($provider, $redirectUri);

        $this->assertSame($provider, $command->provider);
        $this->assertSame($redirectUri, $command->redirectUri);
    }

    public function testSetAndGetResponse(): void
    {
        $command = new InitiateOAuthCommand(
            $this->faker->word(),
            $this->faker->url()
        );

        $response = new InitiateOAuthResponse(
            $this->faker->url(),
            $this->faker->sha256(),
            $this->faker->sha256(),
        );

        $command->setResponse($response);

        $this->assertSame($response, $command->getResponse());
    }
}
