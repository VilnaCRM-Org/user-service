<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Factory;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RequestPasswordResetCommand;
use App\User\Application\Factory\RequestPasswordResetCommandFactory;

final class RequestPasswordResetCommandFactoryTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $email = $this->faker->email();
        $factory = new RequestPasswordResetCommandFactory();

        $requestPasswordResetCommand = $factory->create($email);

        $this->assertInstanceOf(RequestPasswordResetCommand::class, $requestPasswordResetCommand);
    }
}
