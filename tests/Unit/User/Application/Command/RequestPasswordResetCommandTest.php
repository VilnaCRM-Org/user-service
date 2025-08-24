<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RequestPasswordResetCommand;
use App\User\Application\Command\RequestPasswordResetCommandResponse;

final class RequestPasswordResetCommandTest extends UnitTestCase
{
    public function testConstructor(): void
    {
        $email = $this->faker->safeEmail();

        $command = new RequestPasswordResetCommand($email);

        $this->assertInstanceOf(RequestPasswordResetCommand::class, $command);
        $this->assertSame($email, $command->email);
    }

    public function testSetAndGetResponse(): void
    {
        $email = $this->faker->safeEmail();
        $message = $this->faker->sentence();

        $command = new RequestPasswordResetCommand($email);
        $response = new RequestPasswordResetCommandResponse($message);

        $command->setResponse($response);

        $this->assertSame($response, $command->getResponse());
        $this->assertSame($message, $command->getResponse()->message);
    }
}
