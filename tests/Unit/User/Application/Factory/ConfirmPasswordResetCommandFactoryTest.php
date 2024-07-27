<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Factory;

use App\Tests\Builders\ConfirmationTokenBuilder;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\Factory\ConfirmPasswordResetCommandFactory;

final class ConfirmPasswordResetCommandFactoryTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $factory = new ConfirmPasswordResetCommandFactory();
        $token = (new ConfirmationTokenBuilder())->build();
        $password = $this->faker->password();

        $command = $factory->create($token, $password);

        $this->assertInstanceOf(ConfirmPasswordResetCommand::class, $command);
    }
}
