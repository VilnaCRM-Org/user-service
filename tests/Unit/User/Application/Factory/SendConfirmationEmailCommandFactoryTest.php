<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Factory;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SendConfirmationEmailCommand;
use App\User\Application\Factory\SendConfirmationEmailCommandFactory;
use App\User\Application\Factory\SendConfirmationEmailCommandFactoryInterface;
use App\User\Domain\Aggregate\ConfirmationEmailInterface;

final class SendConfirmationEmailCommandFactoryTest extends UnitTestCase
{
    private SendConfirmationEmailCommandFactoryInterface $factory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new SendConfirmationEmailCommandFactory();
    }

    public function testCreate(): void
    {
        $confirmationEmail =
            $this->createMock(ConfirmationEmailInterface::class);

        $command = $this->factory->create($confirmationEmail);

        $this->assertInstanceOf(
            SendConfirmationEmailCommand::class,
            $command
        );
    }
}
