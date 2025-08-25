<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Factory;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SendPasswordResetEmailCommand;
use App\User\Application\Factory\SendPasswordResetEmailCommandFactory;
use App\User\Domain\Aggregate\PasswordResetEmailInterface;

final class SendPasswordResetEmailCommandFactoryTest extends UnitTestCase
{
    private SendPasswordResetEmailCommandFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new SendPasswordResetEmailCommandFactory();
    }

    public function testCreate(): void
    {
        $passwordResetEmail = $this->createMock(PasswordResetEmailInterface::class);

        $command = $this->factory->create($passwordResetEmail);

        $this->assertInstanceOf(SendPasswordResetEmailCommand::class, $command);
        $this->assertSame($passwordResetEmail, $command->passwordResetEmail);
    }
}
