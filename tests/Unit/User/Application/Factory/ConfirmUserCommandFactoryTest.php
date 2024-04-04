<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Factory;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\ConfirmUserCommand;
use App\User\Application\Factory\ConfirmUserCommandFactory;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;

final class ConfirmUserCommandFactoryTest extends UnitTestCase
{
    private ConfirmationTokenFactoryInterface $confirmationTokenFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->confirmationTokenFactory = new ConfirmationTokenFactory($this->faker->numberBetween(1, 10));
    }

    public function testCreate(): void
    {
        $factory = new ConfirmUserCommandFactory();
        $token = $this->confirmationTokenFactory->create($this->faker->uuid());

        $command = $factory->create($token);

        $this->assertInstanceOf(ConfirmUserCommand::class, $command);
    }
}
